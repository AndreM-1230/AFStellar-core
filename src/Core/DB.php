<?php

namespace App\Core;

use PDO;

class DB
{
    protected static $connection;
    public static $DB_TYPE = 'mysql';

    public static function setConnection(string $connectionName = 'default')
    {
        static::$connection = Config::connection($connectionName);
        static::$DB_TYPE = Config::connectionType($connectionName);
        return self::class;
    }

    public static function table($table)
    {
        $connection = static::$connection;
        $db_type = static::$DB_TYPE;
        if (!$connection) {
            $connection = Config::connection();
        }
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
        return new QueryBuilder($connection, $table, $db_type);
    }

    public static function raw($value)
    {
        return new RawExpression($value);
    }
    
    public static function select($sql, $bindings = [])
    {
        $connection = static::$connection;
        if (!$connection) {
            $connection = Config::connection();
        }
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
        $sth = $connection->prepare($sql);
        $sth->execute($bindings);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insert($sql, $bindings = [])
    {
        $connection = static::$connection;
        if (!$connection) {
            $connection = Config::connection();
        }
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
        $sth = $connection->prepare($sql);
        return $sth->execute($bindings);
    }

    public static function quote($sql)
    {
        $connection = static::$connection;
        if (!$connection) {
            $connection = Config::connection();
        }
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
        return $connection->quote($sql);
    }

    public static function exec($sql)
    {
        $connection = static::$connection;
        if (!$connection) {
            $connection = Config::connection();
        }
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
        $connection->exec($sql);
    }

    public static function update($sql, $bindings = [])
    {
        return self::insert($sql, $bindings);
    }

    public static function delete($sql, $bindings = [])
    {
        return self::insert($sql, $bindings);
    }

    public static function beginTransaction()
    {
        if (!static::$connection) {
            static::$connection = Config::connection();
        }
        static::$connection->beginTransaction();
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
    }

    public static function rollBack()
    {
        if (!static::$connection) {
            static::$connection = Config::connection();
        }
        if (static::$connection->inTransaction()) {
            static::$connection->rollBack();
        }
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
    }

    public static function commit()
    {
        if (!static::$connection) {
            static::$connection = Config::connection();
        }
        if (static::$connection->inTransaction()) {
            static::$connection->commit();
        }
        static::$connection = null;
        static::$DB_TYPE = 'mysql';
    }
}
