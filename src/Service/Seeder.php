<?php
namespace App\Service;

use PDO;

class Seeder
{
    public static function seedIfEmpty(int $userId): void
    {
        $pdo = Database::getConnection();

        // 统计现有数据量
        $counts = [
            'categories' => self::countBy($pdo, 'categories', $userId),
            'items' => self::countBy($pdo, 'items', $userId),
            'accounts' => self::countBy($pdo, 'accounts', $userId),
        ];

        // 若已有任一数据，跳过（避免重复注入）
        if ($counts['categories'] > 0 || $counts['items'] > 0 || $counts['accounts'] > 0) {
            return;
        }

        $pdo->beginTransaction();
        try {
            // 1) 注入分类（含 SVG 简单图标）
            $categoryMap = [
                'expense' => [
                    ['name' => '网络购物', 'sort' => 1, 'svg' => self::dot('#4C6EF5')],
                    ['name' => '线下消费', 'sort' => 2, 'svg' => self::dot('#228BE6')],
                    ['name' => '交通出行', 'sort' => 3, 'svg' => self::dot('#15AABF')],
                    ['name' => '餐饮美食', 'sort' => 4, 'svg' => self::dot('#12B886')],
                    ['name' => '居家生活', 'sort' => 5, 'svg' => self::dot('#FAB005')],
                    ['name' => '通讯网费', 'sort' => 6, 'svg' => self::dot('#FD7E14')],
                    ['name' => '医疗健康', 'sort' => 7, 'svg' => self::dot('#E64980')],
                    ['name' => '教育培训', 'sort' => 8, 'svg' => self::dot('#7950F2')],
                    ['name' => '金融业务', 'sort' => 9, 'svg' => self::dot('#E8590C')],
                    ['name' => '其它支出', 'sort' => 99, 'svg' => self::dot('#868E96')],
                ],
                'income' => [
                    ['name' => '薪资福利', 'sort' => 1, 'svg' => self::dot('#40C057')],
                    ['name' => '理财收益', 'sort' => 2, 'svg' => self::dot('#37B24D')],
                    ['name' => '红包转账', 'sort' => 3, 'svg' => self::dot('#F03E3E')],
                    ['name' => '其它收入', 'sort' => 99, 'svg' => self::dot('#868E96')],
                ],
            ];

            $categoryIds = [
                'expense' => [],
                'income' => [],
            ];

            $stmtCat = $pdo->prepare('INSERT INTO categories (user_id, type, name, sort_order, icon_type, icon_value) VALUES (:uid,:type,:name,:sort,:it,:iv)');
            foreach ($categoryMap as $type => $rows) {
                foreach ($rows as $c) {
                    $stmtCat->execute([
                        ':uid' => $userId,
                        ':type' => $type,
                        ':name' => $c['name'],
                        ':sort' => $c['sort'],
                        ':it' => 'svg',
                        ':iv' => $c['svg'],
                    ]);
                    $categoryIds[$type][$c['name']] = (int)$pdo->lastInsertId();
                }
            }

            // 2) 注入项目（按常见分类）
            $itemsMap = [
                // expense items
                ['type' => 'expense', 'cat' => '网络购物', 'items' => ['日用百货', '服饰鞋帽', '数码家电']],
                ['type' => 'expense', 'cat' => '线下消费', 'items' => ['超市', '便利店', '商场/专柜']],
                ['type' => 'expense', 'cat' => '交通出行', 'items' => ['地铁/公交', '打车', '加油/充电']],
                ['type' => 'expense', 'cat' => '餐饮美食', 'items' => ['早餐', '午餐', '晚餐', '饮品/零食']],
                ['type' => 'expense', 'cat' => '居家生活', 'items' => ['房租/房贷', '水电煤', '物业/维修']],
                ['type' => 'expense', 'cat' => '通讯网费', 'items' => ['手机话费', '宽带/流量']],
                ['type' => 'expense', 'cat' => '医疗健康', 'items' => ['药品', '门诊']],
                ['type' => 'expense', 'cat' => '教育培训', 'items' => ['书籍', '考试/培训']],
                ['type' => 'expense', 'cat' => '金融业务', 'items' => ['还款', '利息/手续费']],
                ['type' => 'expense', 'cat' => '其它支出', 'items' => ['其它']],
                // income items
                ['type' => 'income', 'cat' => '薪资福利', 'items' => ['工资', '奖金/补贴']],
                ['type' => 'income', 'cat' => '理财收益', 'items' => ['利息', '分红/收益']],
                ['type' => 'income', 'cat' => '红包转账', 'items' => ['转入', '收红包']],
                ['type' => 'income', 'cat' => '其它收入', 'items' => ['其它']],
            ];

            $stmtItem = $pdo->prepare('INSERT INTO items (user_id, category_id, name, sort_order, icon_type, icon_value) VALUES (:uid,:cid,:name,:sort,:it,:iv)');
            foreach ($itemsMap as $im) {
                $type = $im['type'];
                $catName = $im['cat'];
                $cid = $categoryIds[$type][$catName] ?? null;
                if (!$cid) continue;
                $order = 0;
                foreach ($im['items'] as $name) {
                    $stmtItem->execute([
                        ':uid' => $userId,
                        ':cid' => $cid,
                        ':name' => $name,
                        ':sort' => $order++,
                        ':it' => null,
                        ':iv' => null,
                    ]);
                }
            }

            // 3) 注入账户（基于 account_groups 映射）
            $groupIds = self::loadAccountGroupIds($pdo);
            $stmtAcc = $pdo->prepare('INSERT INTO accounts (user_id, group_id, name, account_no, initial_balance, current_balance, is_default, icon_type, icon_value) VALUES (:uid,:gid,:name,:no,:init,:curr,:def,:it,:iv)');

            $defaults = [
                ['group' => 'saving', 'name' => '现金', 'no' => null],
                ['group' => 'financial', 'name' => '微信余额', 'no' => null],
                ['group' => 'financial', 'name' => '支付宝余额', 'no' => null],
            ];
            $idx = 0;
            foreach ($defaults as $acc) {
                $gid = $groupIds[$acc['group']] ?? null;
                if (!$gid) continue;
                $stmtAcc->execute([
                    ':uid' => $userId,
                    ':gid' => $gid,
                    ':name' => $acc['name'],
                    ':no' => $acc['no'],
                    ':init' => 0,
                    ':curr' => 0,
                    ':def' => $idx === 0 ? 1 : 0,
                    ':it' => 'svg',
                    ':iv' => self::dot('#ADB5BD'),
                ]);
                $idx++;
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            // 出错忽略（不影响用户正常使用），也可写入日志
        }
    }

    private static function countBy(PDO $pdo, string $table, int $userId): int
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    private static function loadAccountGroupIds(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, code FROM account_groups')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(string)$r['code']] = (int)$r['id'];
        }
        return $map;
    }

    private static function dot(string $color): string
    {
        // 简单的圆点 SVG 图标（24x24）
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="' . htmlspecialchars($color, ENT_QUOTES) . '"/></svg>';
    }
}
