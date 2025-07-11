<?php

namespace App\Core;

use PDO;

class DB
{
    protected static $connection;

    public static function setConnection(PDO $connection)
    {
        static::$connection = $connection;
    }

    public static function table($table)
    {
        if (!static::$connection) {
            static::$connection = new PDO(
                "mysql:host=".Config::$DB_HOST.";dbname=".Config::$DB_NAME,
                    Config::$DB_USER,
                    Config::$DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => true
                ]
            );
            static::$connection->exec("set names utf8");
        }
        return new QueryBuilder(static::$connection, $table);
    }

    public static function raw($value)
    {
        return new RawExpression($value);
    }
    
    public static function select($sql, $bindings = [])
    {
        $sth = static::$connection->prepare($sql);
        $sth->execute($bindings);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insert($sql, $bindings = [])
    {
        $sth = static::$connection->prepare($sql);
        return $sth->execute($bindings);
    }

    public static function update($sql, $bindings = [])
    {
        return self::insert($sql, $bindings);
    }

    public static function delete($sql, $bindings = [])
    {
        return self::insert($sql, $bindings);
    }
}
