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
        static::$connection = Config::connection();
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

    public static function beginTransaction()
    {
        static::$connection = Config::connection();
        static::$connection->beginTransaction();
    }

    public static function rollBack()
    {
        static::$connection->rollBack();
    }

    public static function commit()
    {
        static::$connection->commit();
    }
}
