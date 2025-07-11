<?php

namespace App\Core;

Use PDO;

abstract class Model {
    protected static $table;
    protected $fillable = [];
    protected $relations = [];
    protected $joined = [];
    protected static $connection;
    protected $exists = false;
    protected static $columnTypes = [];

    public function __construct(array $fillable = [])
    {
        if ($fillable) {
            $fillable_keys = $this->getFillable();
            $this->fillable = array_intersect_key($fillable, array_flip($fillable_keys));
            $this->joined = array_diff_key($fillable, array_flip($fillable_keys));
        }
        static::connect();
    }

    protected static function connect()
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
    }

    protected static function boot()
    {
        if (empty(static::$columnTypes)) {
            $sth = static::$connection->query("
                SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '". static::getTable() ."'
            ");
            $columns = $sth->fetchAll(PDO::FETCH_ASSOC);
            foreach($columns as $column) {
                static::$columnTypes[$column['COLUMN_NAME']] = [
                    'type' =>  strtolower($column['COLUMN_TYPE']),
                    'data_type' => strtolower($column['DATA_TYPE']),
                ];
            }
        }
    }

    public static function getColumnTypes()
    {
        static::boot();
        return static::$columnTypes;
    }

    public static function getColumnType($column)
    {
        static::boot();
        return static::$columnTypes[$column] ?? null;
    }

    public static function query()
    {
        return (new static)->newQuery();
    }

    protected function newQuery()
    {
        $query = new QueryBuilder(static::$connection, static::getTable());
        $query->model(get_called_class());
        return $query;
    }

    protected static function getTable()
    {
        return static::$table ?:strtolower(basename(str_replace('\\', '/', static::class))) . 's';
    }

    public function __get($name)
    {
        
        if (method_exists($this, $name)) {
            if (!array_key_exists($name, $this->relations)) {
                $relation = $this->$name();
                if ($relation instanceof QueryBuilder) {
                    $this->relations[$name] = $relation->get();
                } else {
                    $this->relations[$name] = $relation;
                }
            }
            return $this->relations[$name];
        }

        if (array_key_exists($name, $this->joined)) {
            return $this->joined[$name];
        }
        return $this->fillable[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->fillable[$name] = $value;
    }

    public function fillable($name)
    {
        return $this->fillable[$name] ?? null;
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public function fill(array $values)
    {
        foreach ($values as $key => $value) {
            if (in_array($key, $this->getFillable())) {
                $this->__set($key, $value);
                $this->fillable[$key] = $value;
            }
        }
        return $this;
    }

    public function save()
    {
        $table = static::getTable();
        $columns = implode(', ', array_keys($this->fillable));
        $placeholders = implode(', ', array_fill(0, count($this->fillable), '?'));
        $sth = static::$connection->prepare(
            "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})"
        );
        $result = $sth->execute(array_values($this->fillable));

        if ($result && !$this->exists) {
            $this->id = static::$connection->lastInsertId();
            $this->exists = true;
        }

        return $result;
    }

    public function update(array $attributes)
    {
        $fillableAttr = [];
        foreach ($attributes as $key => $value) {
            if ($this->fillable[$key] !== $value) {
                $fillableAttr[$key] = $value;
                $this->fillable[$key] = $value;
            }
        }
        if (!$fillableAttr) {
            return true;
        }
        $table = static::getTable();
        $setClause = implode('`, `', array_map(
            function ($col) {return "$col = ?";},
            array_keys($fillableAttr)));
        $sth  = static::$connection->prepare(
            "UPDATE `{$table}` SET `{$setClause}` WHERE id = ?"
        );
        return $sth->execute(array_merge(array_values($fillableAttr), [$this->id]));
    }

    public function delete()
    {
        $table = static::getTable();
        $sth = static::$connection->prepare(
            "DELETE FROM {$table} WHERE id = ?"
        );
        $result = $sth->execute([$this->id]);
        if ($result) {
            $this->exists = false;
        }
        return $result;
    }

    public static function all()
    {
        return static::query()->get();
    }

    public static function find($value, $name = 'id')
    {
        $result = static::query()->where($name, '=', $value)->first();
        return $result ?? null;
    }

    public function hasMany($related, $foreignKey = null, $localKey = 'id')
    {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_id';
        return (new $related)->newQuery()->where($foreignKey, $this->{$localKey});
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = 'id')
    {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', $related))) . '_id';
        return (new $related)->newQuery()->where($ownerKey, $this->{$foreignKey})->first();
    }
}