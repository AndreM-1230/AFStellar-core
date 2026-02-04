<?php

namespace App\Core;

use PDO;

class DB
{
    protected static PDO $connection;

    public static function setConnection(PDO $connection): void
    {
        static::$connection = $connection;
    }

    public static function table($table): QueryBuilder
    {
        static::$connection = Config::connection();
        return new QueryBuilder(static::$connection, $table);
    }

    public static function raw($value): RawExpression
    {
        return new RawExpression($value);
    }
    
    public static function select($sql, $bindings = [])
    {
        $sth = static::$connection->prepare($sql);
        $sth->execute($bindings);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function insert($sql, $bindings = []): bool
    {
        $sth = static::$connection->prepare($sql);
        return $sth->execute($bindings);
    }

    public static function exec($sql): void
    {
        static::$connection = Config::connection();
        static::$connection->exec($sql);
    }

    public static function update($sql, $bindings = [])
    {
        return self::insert($sql, $bindings);
    }

    public static function delete($sql, $bindings = [])
    {
        return self::insert($sql, $bindings);
    }

    public static function beginTransaction(): void
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
