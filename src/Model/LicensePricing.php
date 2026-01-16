<?php
namespace App\Model;

use App\Service\Database;

class LicensePricing
{
    public static function getDefault(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM license_pricing ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }
        return $row;
    }

    public static function saveFromPost(array $data): void
    {
        $pdo = Database::getConnection();

        $fields = [
            'first_month_price',
            'first_month_price_promo',
            'first_year_price',
            'first_year_price_promo',
            'first_lifetime_price',
            'first_lifetime_price_promo',
            'change_price',
            'change_price_promo',
            'is_promo_active',
        ];

        $values = [];
        foreach ($fields as $f) {
            if ($f === 'is_promo_active') {
                $values[$f] = isset($data[$f]) ? 1 : 0;
            } else {
                $values[$f] = isset($data[$f]) && $data[$f] !== '' ? (float)$data[$f] : null;
            }
        }

        $existing = self::getDefault();
        if ($existing) {
            $sql = 'UPDATE license_pricing SET
                first_month_price = :first_month_price,
                first_month_price_promo = :first_month_price_promo,
                first_year_price = :first_year_price,
                first_year_price_promo = :first_year_price_promo,
                first_lifetime_price = :first_lifetime_price,
                first_lifetime_price_promo = :first_lifetime_price_promo,
                change_price = :change_price,
                change_price_promo = :change_price_promo,
                is_promo_active = :is_promo_active
                WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $values['id'] = (int)$existing['id'];
            $stmt->execute($values);
        } else {
            $sql = 'INSERT INTO license_pricing (
                first_month_price, first_month_price_promo,
                first_year_price, first_year_price_promo,
                first_lifetime_price, first_lifetime_price_promo,
                change_price, change_price_promo,
                is_promo_active
            ) VALUES (
                :first_month_price, :first_month_price_promo,
                :first_year_price, :first_year_price_promo,
                :first_lifetime_price, :first_lifetime_price_promo,
                :change_price, :change_price_promo,
                :is_promo_active
            )';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
    }
}
