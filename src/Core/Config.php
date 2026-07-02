<?php

namespace App\Core;

class Config
{
    private static $instances = [];
    public static $DB_HOST;
    public static $DB_NAME;
    public static $DB_USER;
    public static $DB_PASS;
    public static $DB_TYPE = 'mysql';

    public static function connection($connection_name = 'default')
    {
        if (!static::$instances[$connection_name]['connection']) {
            self::$DB_HOST = $_ENV['DATABASE_HOST'] ?? 'localhost';
            self::$DB_NAME = $_ENV['DATABASE_NAME'] ?? 'mvcore';
            self::$DB_USER = $_ENV['DATABASE_USER'] ?? 'root';
            self::$DB_PASS = $_ENV['DATABASE_PASSWORD'] ?? '';
            self::$loaded = true;
            switch ($DB_TYPE) {
                case 'mysql':
                    $connection_data = "mysql:host=".$DB_HOST.";dbname=".$DB_NAME;
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => true
                    ];
                    break;
                case 'sql':
                    $connection_data = "sqlsrv:Server=".$DB_HOST.";Database=".$DB_NAME.";TrustServerCertificate=true";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
                    ];
                    break;
            }
            static::$instances[$connection_name]['connection'] = new PDO(
                $connection_data,
                $DB_USER,
                $DB_PASS,
                $options
            );
            static::$instances[$connection_name]['type'] = $DB_TYPE;
            if ($DB_TYPE == 'mysql')
                static::$instances[$connection_name]['connection']->exec("set names utf8");
        }
         return static::$instances[$connection_name]['connection'];
    }

    public static function connectionType($connection_name = 'default')
    {
        if (!static::$instances[$connection_name]['connection']) {
            return false;
        }
        return static::$instances[$connection_name]['type'];
    }
}