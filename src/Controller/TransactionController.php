<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;
use App\Service\Upload;
use App\Model\Transaction;
use App\Model\Category;
use App\Model\Item;
use App\Model\Account;
use PDO;

class TransactionController
{
    private function requireLogin(): int
    {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
			header('Location: /public/index.php?route=login');
            exit;
        }
        return $uid;
    }

    /**
     * 将表单提交的记账时间统一转换为 "Y-m-d H:i:s" 格式
     * 支持来自 datetime-local 控件的值（例如 2025-12-30T06:43 或 2025-12-30T06:43:13）
     * 以及原有的 "Y-m-d H:i:s" 文本输入格式。
     */
    private function normalizeTransTime(?string $input): string
    {
        $input = trim((string)$input);
        if ($input === '') {
            return date('Y-m-d H:i:s');
        }

        // 来自 datetime-local 时中间是 "T"
        if (strpos($input, 'T') !== false) {
            $input = str_replace('T', ' ', $input);
        }

        // 若没有秒，补全为 :00
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $input)) {
            $input .= ':00';
        }

        $dt = date_create($input);
        if ($dt === false) {
            return date('Y-m-d H:i:s');
        }
        return $dt->format('Y-m-d H:i:s');
    }

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    public function index(): void
    {
        $userId = $this->requireLogin();

        $filters = [
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'remark' => $_GET['remark'] ?? '',
        ];

        // 当前用户全部记账数量（不受筛选条件影响），用于调试/统计
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM transactions WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $totalCount = (int)($stmt->fetchColumn() ?: 0);

        $transactions = Transaction::search($userId, $filters);
        $summary = Transaction::summarize($userId, $filters);
        $categories = Category::allByUser($userId);
        $items = Item::allByUser($userId);
        $accounts = Account::allByUser($userId);

        $this->render('transactions/index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => $filters,
            'categories' => $categories,
            'items' => $items,
            'accounts' => $accounts,
            'totalCount' => $totalCount,
        ]);
    }

    public function create(): void
    {
        $userId = $this->requireLogin();
        $categories = Category::allByUser($userId);
        $items = Item::allByUser($userId);
        $accounts = Account::allByUser($userId);
        // 今日记录用于在记账页面下方快速回看
        $today = date('Y-m-d');
        $todayFilters = [
            'type' => '',
            'category_id' => '',
            'item_id' => '',
            'account_id' => '',
            'date_from' => $today,
            'date_to' => $today,
            'amount_min' => '',
            'amount_max' => '',
            'remark' => '',
        ];
        $todayTransactions = Transaction::search($userId, $todayFilters);

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['saved'] ?? '') === '1') {
            $success = '记账已保存，可继续新增下一条。';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? 'expense';
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $itemId = (int)($_POST['item_id'] ?? 0);
            $fromAccountId = (int)($_POST['from_account_id'] ?? 0);
            $toAccountId = (int)($_POST['to_account_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $remark = trim($_POST['remark'] ?? '');
            $transTime = $this->normalizeTransTime($_POST['trans_time'] ?? '');

            if ($amount <= 0) {
                $error = '金额必须大于0';
            } elseif (!$categoryId) {
                $error = '请选择分类';
            } else {
                if ($type === 'expense') {
                    if (!$fromAccountId) {
                        $error = '支出需要选择支出账户';
                    }
                } else { // income
                    if (!$toAccountId) {
                        $error = '收入需要选择收入账户';
                    }
                }
            }

            $attachmentPath = null;
            if (!$error && isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $saved = Upload::saveAttachment($userId, $_FILES['attachment']);
                if ($saved === null) {
                    $error = '图片上传失败或超过10MB限制';
                } else {
                    $attachmentPath = $saved;
                }
            }

            if (!$error) {
                $data = [
                    'user_id' => $userId,
                    'type' => $type,
                    'category_id' => $categoryId,
                    'item_id' => $itemId ?: null,
                    'from_account_id' => $fromAccountId ?: null,
                    'to_account_id' => $toAccountId ?: null,
                    'amount' => $amount,
                    'trans_time' => $transTime,
                    'remark' => $remark,
                    'attachment_path' => $attachmentPath,
                ];

                // 新增前先调整账户余额
                $this->applyBalanceChange($type, $fromAccountId, $toAccountId, $amount, 1);
                Transaction::create($data);
                // 保存成功后回到新增记账页，避免打断连续记账
                header('Location: /public/index.php?route=transaction-create&saved=1');
                exit;
            }
        }

        $this->render('transactions/form', [
            'mode' => 'create',
            'error' => $error,
            'success' => $success,
            'categories' => $categories,
            'items' => $items,
            'accounts' => $accounts,
            'todayTransactions' => $todayTransactions,
        ]);
    }

    public function edit(): void
    {
        $userId = $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $transaction = Transaction::findById($id, $userId);
        if (!$transaction) {
            http_response_code(404);
            echo '记录不存在';
            return;
        }

        $categories = Category::allByUser($userId);
        $items = Item::allByUser($userId);
        $accounts = Account::allByUser($userId);

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? $transaction['type'];
            $categoryId = (int)($_POST['category_id'] ?? $transaction['category_id']);
            $itemId = (int)($_POST['item_id'] ?? ($transaction['item_id'] ?? 0));
            $fromAccountId = (int)($_POST['from_account_id'] ?? ($transaction['from_account_id'] ?? 0));
            $toAccountId = (int)($_POST['to_account_id'] ?? ($transaction['to_account_id'] ?? 0));
            $amount = (float)($_POST['amount'] ?? $transaction['amount']);
            $remark = trim($_POST['remark'] ?? ($transaction['remark'] ?? ''));
            $transTime = $this->normalizeTransTime($_POST['trans_time'] ?? $transaction['trans_time']);
            $removeAttachment = !empty($_POST['remove_attachment']);

            if ($amount <= 0) {
                $error = '金额必须大于0';
            } elseif (!$categoryId) {
                $error = '请选择分类';
            } else {
                if ($type === 'expense') {
                    if (!$fromAccountId) {
                        $error = '支出需要选择支出账户';
                    }
                } else { // income
                    if (!$toAccountId) {
                        $error = '收入需要选择收入账户';
                    }
                }
            }

            $attachmentPath = $transaction['attachment_path'];
            if (!$error) {
                $baseDir = Config::get('app.upload_dir');

                // 仅删除当前图片（不上传新图）
                if ($removeAttachment && $attachmentPath && $baseDir) {
                    $fullOld = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $attachmentPath;
                    if (is_file($fullOld)) {
                        @unlink($fullOld);
                    }
                    $attachmentPath = null;
                }

                // 上传新图片（无论是否勾选删除，都会覆盖并删除旧图）
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $saved = Upload::saveAttachment($userId, $_FILES['attachment']);
                    if ($saved === null) {
                        $error = '图片上传失败或超过10MB限制';
                    } else {
                        if ($attachmentPath && $baseDir) {
                            $fullOld = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $attachmentPath;
                            if (is_file($fullOld)) {
                                @unlink($fullOld);
                            }
                        }
                        $attachmentPath = $saved;
                    }
                }
            }

            if (!$error) {
                // 先撤销旧记录对余额的影响
                $this->applyBalanceChange($transaction['type'], (int)($transaction['from_account_id'] ?? 0), (int)($transaction['to_account_id'] ?? 0), (float)$transaction['amount'], -1);
                // 再应用新记录
                $this->applyBalanceChange($type, $fromAccountId, $toAccountId, $amount, 1);

                $data = [
                    'type' => $type,
                    'category_id' => $categoryId,
                    'item_id' => $itemId ?: null,
                    'from_account_id' => $fromAccountId ?: null,
                    'to_account_id' => $toAccountId ?: null,
                    'amount' => $amount,
                    'trans_time' => $transTime,
                    'remark' => $remark,
                    'attachment_path' => $attachmentPath,
                ];
                Transaction::update($id, $userId, $data);

                $from = $_GET['from'] ?? '';
                if ($from === 'create') {
                    header('Location: /public/index.php?route=transaction-create');
                } else {
                    header('Location: /public/index.php?route=transactions');
                }
                exit;
            }
        }

        $this->render('transactions/form', [
            'mode' => 'edit',
            'error' => $error,
            'transaction' => $transaction,
            'categories' => $categories,
            'items' => $items,
            'accounts' => $accounts,
            'todayTransactions' => [],
        ]);
    }

    public function delete(): void
    {
        $userId = $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = isset($_POST['ids']) ? (array)$_POST['ids'] : [];
            $pdo = Database::getConnection();
            foreach ($ids as $id) {
                $id = (int)$id;
                $tx = Transaction::findById($id, $userId);
                if ($tx) {
                    $this->applyBalanceChange($tx['type'], (int)($tx['from_account_id'] ?? 0), (int)($tx['to_account_id'] ?? 0), (float)$tx['amount'], -1);
                }
            }
            Transaction::deleteMany($userId, array_map('intval', $ids));
        }

        $from = $_POST['from'] ?? ($_GET['from'] ?? '');
        if ($from === 'create') {
            header('Location: /public/index.php?route=transaction-create');
        } else {
            header('Location: /public/index.php?route=transactions');
        }
        exit;
    }

    public function export(): void
    {
        $userId = $this->requireLogin();
        $filters = [
            'type' => $_GET['type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'item_id' => $_GET['item_id'] ?? '',
            'account_id' => $_GET['account_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'remark' => $_GET['remark'] ?? '',
        ];
        $rows = Transaction::search($userId, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['类型', '分类', '项目', '账户', '金额', '时间', '备注']);
        foreach ($rows as $r) {
            $typeLabel = $r['type'] === 'expense' ? '支出' : '收入';
            $accountLabel = '';
            if ($r['type'] === 'expense') {
                $accountLabel = $r['from_account_name'] ?? '';
            } else {
                $accountLabel = $r['to_account_name'] ?? '';
            }
            fputcsv($out, [
                $typeLabel,
                $r['category_name'] ?? '',
                $r['item_name'] ?? '',
                $accountLabel,
                $r['amount'],
                $r['trans_time'],
                $r['remark'],
            ]);
        }
        fclose($out);
        exit;
    }

    private function applyBalanceChange(string $type, int $fromAccountId, int $toAccountId, float $amount, int $direction): void
    {
        if ($amount <= 0) {
            return;
        }
        $delta = $amount * $direction;
        if ($type === 'expense') {
            if ($fromAccountId) {
                Account::adjustBalance($fromAccountId, -$delta);
            }
        } else { // income
            if ($toAccountId) {
                Account::adjustBalance($toAccountId, $delta);
            }
        }
    }
}
