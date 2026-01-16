<?php
namespace App\Service;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $host = Config::get('db.host');
            $dbname = Config::get('db.dbname');
            $user = Config::get('db.user');
            $pass = Config::get('db.pass');
            $charset = Config::get('db.charset', 'utf8mb4');

            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }
}
