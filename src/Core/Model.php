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
        } else {
            $fillable_keys = $this->getFillable();
            $this->fillable = array_flip($fillable_keys);
        }
        static::connect();
    }

    protected static function connect()
    {
        static::$connection = Config::connection();
    }

    protected static function boot()
    {
        static::$columnTypes = null;
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
        if (in_array($name, $this->getFillable())) {
            $this->fillable[$name] = $value;
        } elseif (array_key_exists($name, $this->relations) || method_exists($this, $name)) {
            $this->relations[$name] = $value;
        } else {
            $this->joined[$name] = $value;
        }
    }

    public function fillable($name)
    {
        return $this->fillable[$name] ?? null;
    }

    public function getFillable()
    {
        return $this->fillable;
    }

    public function getItems()
    {
        if (count($this->fillable)) {
            return $this->fillable;
        }
        return $this->joined;
    }

    public function fill(array $values)
    {
        foreach ($this->getFillable() as $key => $value) {
            if (in_array($key, array_keys($values))) {
                $this->fillable[$key] = $values[$key];
            } else {
                $this->fillable[$key] = null;
            }
        }
        return $this;
    }

    public function save()
    {
        $fillable = [];
        foreach ($this->fillable as $key => $value) {
            if ($value !== null) {
                $fillable[$key] = $value;
            }
        }
        $table = static::getTable();
        $columns = implode(', ', array_keys($fillable));
        $placeholders = array_fill(0, count($fillable), '?');
        foreach (array_keys($fillable) as $key => $data_value) {
            $type = static::getColumnType($data_value);
            if ($type && $type['data_type'] === 'bit' && $type['type'] === 'bit(1)') {
                $placeholders[$key] = 'b?';
            }
        }
        $placeholders = implode(', ', $placeholders);
        $sth = static::$connection->prepare(
            "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})"
        );
        $result = $sth->execute(array_values($fillable));

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
        $setClause = implode(', ', array_map(
            function ($col) {
                $type = static::getColumnType($col);
                if ($type && $type['data_type'] === 'bit' && $type['type'] === 'bit(1)') {
                    return "$col = b?";
                }
                return "$col = ?";
            },
            array_keys($fillableAttr)));
        $sth  = static::$connection->prepare(
            "UPDATE `{$table}` SET {$setClause} WHERE id = ?"
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
        if (is_null($this->{$localKey})) {
            return null;
        }
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_id';
        return (new $related)->newQuery()->where($foreignKey, $this->{$localKey});
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = 'id')
    {
        if (is_null($this->{$foreignKey})) {
            return null;
        }
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', $related))) . '_id';
        return (new $related)->newQuery()->where($ownerKey, $this->{$foreignKey})->first();
    }

    /**
     * @param Model $related Целевая модель
     * @param mixed $through Связующая таблица
     * @param mixed $firstKey Ключ в $through ссылающийся на эту $this модель
     * @param mixed $secondKey Ключ в $related
     * @param mixed $localKey Ключ в этой $this модели
     * @param mixed $secondaryKey Ключ в $through ссылающийся на целевую $related модель
     */
    public function hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondaryKey = null)
    {
        if (is_null($this->{$localKey})) {
            return null;
        }
        $relatedModel = new $related;
        $relatedTable = $relatedModel->getTable();
        return (new $related)->newQuery()
            ->leftJoin($through, "{$through}.{$secondaryKey}", '=', "{$relatedTable}.{$secondKey}")
            ->where("{$through}.{$firstKey}", $this->{$localKey});
    }

    /**
     * @param Model $related Целевая модель
     * @param mixed $through Связующая таблица
     * @param mixed $firstKey Ключ в $through ссылающийся на эту $this модель
     * @param mixed $secondKey Ключ в $related
     * @param mixed $localKey Ключ в этой $this модели
     * @param mixed $secondaryKey Ключ в $through ссылающийся на целевую $related модель
     */
    public function belongsToThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondaryKey = null)
    {
        if (is_null($this->{$localKey})) {
            return null;
        }
        $relatedModel = new $related;
        $relatedTable = $relatedModel->getTable();
        return (new $related)->newQuery()
            ->leftJoin($through, "{$through}.{$secondaryKey}", '=', "{$relatedTable}.{$secondKey}")
            ->where("{$through}.{$firstKey}", $this->{$localKey})->first();
    }
}