<?php

namespace App\Core;

class Config
{
    private static $loaded = false;
    public static $DB_HOST;
    public static $DB_NAME;
    public static $DB_USER;
    public static $DB_PASS;

    public static function init(array $settings = [])
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
}