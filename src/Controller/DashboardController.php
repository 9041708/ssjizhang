<?php
namespace App\Controller;

use App\Service\Database;
use App\Service\Config;
use App\Model\Account;
use App\Model\Budget;
use App\Model\User;
use App\Model\Announcement;
use PDO;

class DashboardController
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
        $pdo = Database::getConnection();

        $currentUser = User::findById($userId);
        $budgetReminderEnabled = isset($currentUser['budget_reminder_enabled'])
            ? (int)$currentUser['budget_reminder_enabled'] === 1
            : true;

        // 各大类账户余额
        $stmt = $pdo->prepare('SELECT ag.code, ag.name, SUM(a.current_balance) AS total
            FROM accounts a
            JOIN account_groups ag ON a.group_id = ag.id
            WHERE a.user_id = :uid
            GROUP BY ag.id, ag.code, ag.name');
        $stmt->execute([':uid' => $userId]);
        $balances = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [
            'financial' => 0.0,
            'saving' => 0.0,
            'receivable' => 0.0,
            'debt' => 0.0,
            'other' => 0.0,
        ];
        foreach ($balances as $row) {
            $code = $row['code'] ?? null;
            if ($code !== null && array_key_exists($code, $map)) {
                $map[$code] = (float)$row['total'];
            }
        }

        // 各分类账户明细（用于弹窗）
        $accounts = Account::allByUser($userId);
        $accountsByGroup = [];
        foreach ($accounts as $acc) {
            $code = $acc['group_code'] ?? '';
            if ($code === '') {
                continue;
            }
            if (!isset($accountsByGroup[$code])) {
                $accountsByGroup[$code] = [];
            }
            $accountsByGroup[$code][] = $acc;
        }

        // 当月预算总额（支出）及“已用预算”（仅对已设置预算的支出分类/项目统计）
        $year = (int)date('Y');
        $month = (int)date('n');

        $budgets = Budget::listByUserMonth($userId, $year, $month);
        $totalBudgetExpense = 0.0;
        $totalUsedExpense = 0.0;

        foreach ($budgets as $b) {
            if (($b['type'] ?? '') !== 'expense') {
                continue;
            }

            $totalBudgetExpense += (float)($b['amount'] ?? 0);

            $sql = 'SELECT COALESCE(SUM(amount),0) AS used_amount
                    FROM transactions
                    WHERE user_id = :uid
                      AND type = :type
                      AND YEAR(trans_time) = :y
                      AND MONTH(trans_time) = :m';
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
            $totalUsedExpense += (float)$row['used_amount'];
        }

        $monthBudget = $totalBudgetExpense;
        $monthBudgetUsed = $totalUsedExpense;

        // 当月实际收入 / 支出（全部支出，不仅限于预算）
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total
            FROM transactions
            WHERE user_id = :uid
              AND type = "expense"
              AND YEAR(trans_time) = :y
              AND MONTH(trans_time) = :m');
        $stmt->execute([':uid' => $userId, ':y' => $year, ':m' => $month]);
        $expenseRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
        $monthExpense = (float)$expenseRow['total'];

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total
            FROM transactions
            WHERE user_id = :uid
              AND type = "income"
              AND YEAR(trans_time) = :y
              AND MONTH(trans_time) = :m');
        $stmt->execute([':uid' => $userId, ':y' => $year, ':m' => $month]);
        $incomeRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
        $monthIncome = (float)$incomeRow['total'];
        $monthNet = $monthIncome - $monthExpense;

        // 预算使用情况（支出）
        $monthBudgetRemain = $monthBudget - $monthBudgetUsed;
        if ($monthBudgetRemain < 0) {
            $monthBudgetRemain = 0.0;
        }

        $monthBudgetRate = $monthBudget > 0 ? ($monthBudgetUsed / $monthBudget) : 0.0;
        $monthBudgetRatePercent = (int)round(min(999, $monthBudgetRate * 100));
        $monthBudgetOver = $monthBudget > 0 && $monthBudgetUsed > $monthBudget;
        $monthBudgetWarn = !$monthBudgetOver && $monthBudgetRate >= 0.8;

        // 最近 7 天收入 / 支出趋势（含今天，共 7 天）
        $labels7 = [];
        $income7 = [];
        $expense7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} day"));
            $labels7[] = date('m-d', strtotime($date));

            $stmt = $pdo->prepare('SELECT type, COALESCE(SUM(amount),0) AS total
                FROM transactions
                WHERE user_id = :uid
                  AND DATE(trans_time) = :d
                  AND type IN ("income","expense")
                GROUP BY type');
            $stmt->execute([':uid' => $userId, ':d' => $date]);

            $income = 0.0;
            $expense = 0.0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['type'] === 'income') {
                    $income = (float)$row['total'];
                } elseif ($row['type'] === 'expense') {
                    $expense = (float)$row['total'];
                }
            }

            $income7[] = $income;
            $expense7[] = $expense;
        }

        // PC 端首页不再展示公告，统一由小程序端处理公告弹窗
        $latestAnnouncement = null;

        $this->render('dashboard/index', [
            'balances' => $map,
            'accountsByGroup' => $accountsByGroup,
            'monthBudget' => $monthBudget,
            'monthBudgetUsed' => $monthBudgetUsed,
            'monthExpense' => $monthExpense,
            'monthIncome' => $monthIncome,
            'monthNet' => $monthNet,
            'monthBudgetRemain' => $monthBudgetRemain,
            'monthBudgetRatePercent' => $monthBudgetRatePercent,
            'monthBudgetOver' => $monthBudgetOver,
            'monthBudgetWarn' => $monthBudgetWarn,
            'budgetReminderEnabled' => $budgetReminderEnabled,
            'trendLabels7' => $labels7,
            'trendIncome7' => $income7,
            'trendExpense7' => $expense7,
            'latestAnnouncement' => $latestAnnouncement,
        ]);
    }
}

