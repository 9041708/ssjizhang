<?php
namespace App\Service;

class Config
{
    private static array $config = [];

    public static function init(string $file): void
    {
        if (empty(self::$config)) {
            self::$config = require $file;
        }
    }

    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = self::$config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
