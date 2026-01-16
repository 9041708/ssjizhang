<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;
use App\Model\Budget;
use App\Model\Category;
use App\Model\Item;
use PDO;

class BudgetController
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

    private function render(string $view, array $params = []): void
    {
        extract($params);
        $appName = Config::get('app.name');
        include __DIR__ . '/../../templates/layout_main.php';
    }

    public function index(): void
    {
        $userId = $this->requireLogin();
        // 支持前端使用 <input type="month"> 传入 ym=YYYY-MM
        if (!empty($_GET['ym']) && preg_match('/^(\d{4})-(\d{2})$/', $_GET['ym'], $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
        } else {
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        }

        // 去年同月，用于对比展示和一键复制
        $prevYear = $year - 1;
        $prevMonth = $month;

        // Ajax 查询：获取某一项目在去年同月的预算金额
        if (isset($_GET['action']) && $_GET['action'] === 'get_prev_budget') {
            $type = $_GET['type'] ?? 'expense';
            $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
            $itemId = isset($_GET['item_id']) && $_GET['item_id'] !== '' ? (int)$_GET['item_id'] : null;

            $row = Budget::findOneByUserYearMonthKey($userId, $prevYear, $prevMonth, $type, $categoryId, $itemId);

            header('Content-Type: application/json; charset=utf-8');
            if ($row) {
                echo json_encode([
                    'success' => true,
                    'amount' => (float)$row['amount'],
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => '暂无去年同月预算，请手动填写。',
                ], JSON_UNESCAPED_UNICODE);
            }
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 一键复制去年同月全部预算到本月
            if (isset($_POST['copy_prev']) && $_POST['copy_prev'] === '1') {
                $prevBudgetsForCopy = Budget::listByUserMonth($userId, $prevYear, $prevMonth);
                foreach ($prevBudgetsForCopy as $pb) {
                    $type = $pb['type'];
                    $categoryId = !empty($pb['category_id']) ? (int)$pb['category_id'] : null;
                    $itemId = !empty($pb['item_id']) ? (int)$pb['item_id'] : null;
                    $amount = (float)$pb['amount'];
                    if ($amount > 0) {
                        Budget::upsert($userId, $year, $month, $type, $categoryId, $itemId, $amount);
                    }
                }
            // 复制单条：将本行预算金额更新为去年同月预算
            } elseif (isset($_POST['copy_prev_single']) && $_POST['copy_prev_single'] === '1') {
                $type = $_POST['type'] ?? 'expense';
                $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                $itemId = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null;
                $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
                if ($amount > 0) {
                    Budget::upsert($userId, $year, $month, $type, $categoryId, $itemId, $amount);
                }
            // 编辑已有预算：仅更新金额
            } elseif (isset($_POST['edit_id'])) {
                $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
                if ($amount > 0) {
                    Budget::updateAmount($userId, (int)$_POST['edit_id'], $amount);
                }
            } elseif (isset($_POST['delete_id'])) {
                Budget::delete($userId, (int)$_POST['delete_id']);
            } else {
                $type = $_POST['type'] ?? 'expense';
                $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                $itemId = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null;
                $amount = (float)($_POST['amount'] ?? 0);
                if ($amount > 0) {
                    Budget::upsert($userId, $year, $month, $type, $categoryId, $itemId, $amount);
                }
            }
            // 使用前端路由参数，确保保存后仍然回到预算管理页面
            header('Location: /public/index.php?route=budget&year=' . $year . '&month=' . $month);
            exit;
        }

        $budgets = Budget::listByUserMonth($userId, $year, $month);

        $pdo = Database::getConnection();

        // 统计去年同月预算与实际
        $prevBudgets = Budget::listByUserMonth($userId, $prevYear, $prevMonth);
        $prevBudgetMap = [];
        $totalPrevBudgetExpense = 0.0;
        $totalPrevUsedExpense = 0.0;
        foreach ($prevBudgets as &$pb) {
            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
            $params = [
                ':uid' => $userId,
                ':type' => $pb['type'],
                ':y' => $prevYear,
                ':m' => $prevMonth,
            ];
            if (!empty($pb['category_id'])) {
                $sql .= ' AND category_id = :cid';
                $params[':cid'] = $pb['category_id'];
            }
            if (!empty($pb['item_id'])) {
                $sql .= ' AND item_id = :iid';
                $params[':iid'] = $pb['item_id'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
            $pb['used_amount'] = (float)$row['used_amount'];
            $pb['remain_amount'] = (float)$pb['amount'] - $pb['used_amount'];

            if ($pb['type'] === 'expense') {
                $totalPrevBudgetExpense += (float)$pb['amount'];
                $totalPrevUsedExpense += (float)$pb['used_amount'];
            }

            $key = $pb['type'] . '|' . ((int)($pb['category_id'] ?? 0)) . '|' . ((int)($pb['item_id'] ?? 0));
            $prevBudgetMap[$key] = $pb;
        }
        unset($pb);

        // 统计每条预算对应的实际支出/收入（本月），并附加去年同月信息
        foreach ($budgets as &$b) {
            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
            $params = [
                ':uid' => $userId,
                ':type' => $b['type'],
                ':y' => $year,
                ':m' => $month,
            ];
            if (!empty($b['category_id'])) {
                $sql .= ' AND category_id = :cid';
                $params[':cid'] = $b['category_id'];
            }
            if (!empty($b['item_id'])) {
                $sql .= ' AND item_id = :iid';
                $params[':iid'] = $b['item_id'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['used_amount' => 0];
            $b['used_amount'] = (float)$row['used_amount'];
            $b['remain_amount'] = (float)$b['amount'] - $b['used_amount'];

            $key = $b['type'] . '|' . ((int)($b['category_id'] ?? 0)) . '|' . ((int)($b['item_id'] ?? 0));
            if (isset($prevBudgetMap[$key])) {
                $b['prev_budget_amount'] = (float)$prevBudgetMap[$key]['amount'];
                $b['prev_used_amount'] = (float)$prevBudgetMap[$key]['used_amount'];
            } else {
                $b['prev_budget_amount'] = 0.0;
                $b['prev_used_amount'] = 0.0;
            }
        }
        unset($b);

        // 汇总当月预算与实际
        $totalBudgetExpense = 0.0;
        $totalUsedExpense = 0.0;
        foreach ($budgets as $b) {
            if ($b['type'] === 'expense') {
                $totalBudgetExpense += (float)$b['amount'];
                $totalUsedExpense += (float)$b['used_amount'];
            }
        }

        $categories = Category::allByUser($userId);
        $items = Item::allByUser($userId);

        $this->render('budget/index', [
            'year' => $year,
            'month' => $month,
            'budgets' => $budgets,
            'categories' => $categories,
            'items' => $items,
            'totalBudgetExpense' => $totalBudgetExpense,
            'totalUsedExpense' => $totalUsedExpense,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'totalPrevBudgetExpense' => $totalPrevBudgetExpense,
            'totalPrevUsedExpense' => $totalPrevUsedExpense,
        ]);
    }
}
