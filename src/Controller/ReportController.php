<?php
namespace App\Controller;

use App\Service\Config;
use App\Service\Database;
use App\Model\Budget;
use PDO;

class ReportController
{
    /**
     * 汇总某一月份的预算总额（支出）和已用金额，算法与预算管理页保持一致。
     */
    private function summarizeBudgetByMonth(int $userId, int $year, int $month): array
    {
        $budgets = Budget::listByUserMonth($userId, $year, $month);
        if (empty($budgets)) {
            return [0.0, 0.0];
        }

        $pdo = Database::getConnection();
        $totalBudgetExpense = 0.0;
        $totalUsedExpense   = 0.0;

        foreach ($budgets as $b) {
            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount FROM transactions WHERE user_id = :uid AND type = :type AND YEAR(trans_time) = :y AND MONTH(trans_time) = :m';
            $params = [
                ':uid' => $userId,
                ':type' => $b['type'],
                ':y'   => $year,
                ':m'   => $month,
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
            $usedAmount = (float)$row['used_amount'];

            if ($b['type'] === 'expense') {
                $totalBudgetExpense += (float)$b['amount'];
                $totalUsedExpense   += $usedAmount;
            }
        }

        return [$totalBudgetExpense, $totalUsedExpense];
    }

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
        $mode = $_GET['mode'] ?? 'month';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : (int)ceil($month / 3);

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $start = new \DateTime('today');
        $end = new \DateTime('today');

        switch ($mode) {
            case 'year':
                $start = new \DateTime($year . '-01-01');
                $end = new \DateTime($year . '-12-31');
                break;
            case 'quarter':
                $startMonth = ($quarter - 1) * 3 + 1;
                $start = new \DateTime(sprintf('%d-%02d-01', $year, $startMonth));
                $end = clone $start;
                $end->modify('+2 months')->modify('last day of this month');
                break;
            case 'day':
                $start = new \DateTime();
                $end = new \DateTime();
                break;
            case 'yesterday':
                $start = new \DateTime('yesterday');
                $end = new \DateTime('yesterday');
                break;
            case 'custom':
                if ($dateFrom && $dateTo) {
                    $start = new \DateTime($dateFrom);
                    $end = new \DateTime($dateTo);
                }
                break;
            case 'month':
            default:
                $start = new \DateTime(sprintf('%d-%02d-01', $year, $month));
                $end = clone $start;
                $end->modify('last day of this month');
                break;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT DATE(trans_time) AS d, type, COALESCE(SUM(amount),0) AS total
            FROM transactions
            WHERE user_id = :uid AND trans_time BETWEEN :from AND :to
            GROUP BY DATE(trans_time), type
            ORDER BY d');
        $stmt->execute([
            ':uid' => $userId,
            ':from' => $start->format('Y-m-d 00:00:00'),
            ':to' => $end->format('Y-m-d 23:59:59'),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $byDate = [];
        foreach ($rows as $r) {
            $d = $r['d'];
            if (!isset($byDate[$d])) {
                $byDate[$d] = ['income' => 0.0, 'expense' => 0.0];
            }
            if ($r['type'] === 'income') {
                $byDate[$d]['income'] += (float)$r['total'];
            } elseif ($r['type'] === 'expense') {
                $byDate[$d]['expense'] += (float)$r['total'];
            }
        }
        foreach ($byDate as $d => $v) {
            $labels[] = $d;
            $incomeData[] = $v['income'];
            $expenseData[] = $v['expense'];
        }

        $totalIncome = array_sum($incomeData);
        $totalExpense = array_sum($expenseData);

        // 预算汇总：支持年度 / 季度 / 月度，沿用预算管理页的月度口径然后按月份累加
        $totalBudgetExpense = 0.0;
        $totalUsedExpense   = 0.0;
        if (in_array($mode, ['year', 'quarter', 'month'], true)) {
            if ($mode === 'year') {
                $startMonth = 1;
                $endMonth   = 12;
            } elseif ($mode === 'quarter') {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth   = $startMonth + 2;
            } else { // month
                $startMonth = $month;
                $endMonth   = $month;
            }

            for ($m = $startMonth; $m <= $endMonth; $m++) {
                [$bTotal, $uTotal] = $this->summarizeBudgetByMonth($userId, $year, $m);
                $totalBudgetExpense += $bTotal;
                $totalUsedExpense   += $uTotal;
            }
        }

        // 按分类/项目汇总支出，用于柱状图并可联动流水明细
        $categoryLabels = [];
        $categoryData = [];
        $categoryLinks = [];

        $stmt = $pdo->prepare('SELECT
                COALESCE(c.id, 0) AS category_id,
                COALESCE(c.name, "未分类") AS category_name,
                COALESCE(i.id, 0) AS item_id,
                COALESCE(i.name, "未指定") AS item_name,
                COALESCE(SUM(t.amount), 0) AS total
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN items i ON t.item_id = i.id
            WHERE t.user_id = :uid
              AND t.type = "expense"
              AND t.trans_time BETWEEN :from AND :to
            GROUP BY category_id, item_id
            HAVING total > 0
            ORDER BY total DESC
            LIMIT 20');
        $stmt->execute([
            ':uid' => $userId,
            ':from' => $start->format('Y-m-d 00:00:00'),
            ':to' => $end->format('Y-m-d 23:59:59'),
        ]);
        $rowsByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rowsByCategory as $row) {
            $catId = (int)($row['category_id'] ?? 0);
            $itemId = (int)($row['item_id'] ?? 0);
            $label = (string)($row['category_name'] ?? '未分类');
            if ($itemId > 0 && !empty($row['item_name'])) {
                $label .= ' / ' . (string)$row['item_name'];
            }

            $categoryLabels[] = $label;
            $total = (float)$row['total'];
            $categoryData[] = $total;

            $query = [
                'route' => 'transactions',
                'type' => 'expense',
                'date_from' => $start->format('Y-m-d'),
                'date_to' => $end->format('Y-m-d'),
            ];
            if ($catId > 0) {
                $query['category_id'] = (string)$catId;
            }
            if ($itemId > 0) {
                $query['item_id'] = (string)$itemId;
            }
            $categoryLinks[] = '/public/index.php?' . http_build_query($query);
        }

        $this->render('reports/index', [
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'dateFrom' => $start->format('Y-m-d'),
            'dateTo' => $end->format('Y-m-d'),
            'labels' => $labels,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalBudgetExpense' => $totalBudgetExpense,
            'totalUsedExpense' => $totalUsedExpense,
            'categoryLabels' => $categoryLabels,
            'categoryData' => $categoryData,
            'categoryLinks' => $categoryLinks,
        ]);
    }
}
