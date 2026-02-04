<?php

namespace App\Core;

use PDO;

class Config
{
    private static $loaded = false;
    public static string $DB_HOST;
    public static string $DB_NAME;
    public static string $DB_USER;
    public static string $DB_PASS;

    private static PDO $connection;

    public static function init(array $settings = []): void
    {
        if (!self::$loaded) {
            self::$DB_HOST = $_ENV['DATABASE_HOST'] ?? 'localhost';
            self::$DB_NAME = $_ENV['DATABASE_NAME'] ?? 'mvcore';
            self::$DB_USER = $_ENV['DATABASE_USER'] ?? 'root';
            self::$DB_PASS = $_ENV['DATABASE_PASSWORD'] ?? '';
            self::$loaded = true;
        }
        foreach ($settings as $key => $value) {
            if (property_exists(static::class, $key)) {
                static::$$key = $value;
            }
        }
    }

    public static function connection($udp_config = false): PDO
    {
        if (!static::$connection || $udp_config) {
            static::$connection = new PDO(
                "mysql:host=".static::$DB_HOST.";dbname=".static::$DB_NAME,
                static::$DB_USER,
                static::$DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => true
                ]
            );
            static::$connection->exec("set names utf8");
        }
        return static::$connection;
    }
}