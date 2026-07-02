<?php

namespace App\Core\Database;

class Blueprint
{
    protected $table;
    protected $columns = [];
    protected $primaryKey = null;
    protected $foreignKeys = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function increments(string $column): self
    {
        $this->columns[] = "{$column} BIGINT AUTO_INCREMENT PRIMARY KEY";
        $this->primaryKey = $column;
        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = "{$column} VARCHAR({$length})";
        return $this;
    }

    public function text(string $column): self
    {
        $this->columns[] = "{$column} TEXT";
        return $this;
    }

    public function integer(string $column, int $length = 11): self
    {
        $this->columns[] = "{$column} INT({$length})";
        return $this;
    }

    public function date(string $column): self
    {
        $this->columns[] = "{$column} date";
        return $this;
    }

    public function enum(string $column, array $values): self
    {
        $list = implode(',', array_map(function($v){return "'$v'";}, $values));
        $this->columns[] = "{$column} ENUM({$list})";
        return $this;
    }

    public function index(array $column, string $name = null): self
    {
        if (is_null($name)) {
            $name = implode('_', $column);
        }
        $column = '`' . implode('`, `', $column) .'`';
        $this->columns[] = "INDEX `{$name}` (`{$column}`)";
        return $this;
    }

    public function notNull()
    {
        $this->columns[array_key_last($this->columns)] .= ' NOT NULL';
        return $this;
    }

    public function defaultValue($value)
    {
        $this->columns[array_key_last($this->columns)] .= " DEFAULT '{$value}'";
        return $this;
    }

    public function comment($comemnt)
    {
        $this->columns[array_key_last($this->columns)] .= " COMMENT '{$comemnt}'";
        return $this;
    }

    public function bigInt(string $column): self
    {
        $this->columns[] = "{$column} BIGINT";
        return $this;
    }

    public function float(string $column, int $precision = 8, int $scale = 2)
    {
        $this->columns[] = "{$column} FLOAT({$precision},{$scale})";
        return $this;
    }

    public function double(string $column)
    {
        $this->columns[] = "{$column} DOUBLE";
        return $this;
    }

    public function bit(string $column, int $length = 1)
    {
        $length = max(1, min(64, $length));
        $this->columns[] = "{$column} BIT({$length})";
        return $this;
    }

    public function varchar(string $column, int $length = 1)
    {
        $length = max(1, $length);
        $this->columns[] = "{$column} VARCHAR({$length})";
        return $this;
    }

    public function timestamps(): self
    {
        $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    public function foreign(string $column): ForeignKey
    {
        $foreignKey = new ForeignKey($column);
        $this->foreignKeys[] = $foreignKey;
        return $foreignKey;
    }

    public function compileCreate(): string
    {
        $columns =  $this->columns ? implode(",\n", $this->columns) : '';

        $foreignKeys = array_map(function($fk) {
            return $fk->compile();
        }, $this->foreignKeys);

        $foreignKeysSql = $foreignKeys ? ($columns ? ",\n" : '') . implode(",\n", $foreignKeys) : '';
        return "CREATE TABLE {$this->table} (\n{$columns}{$foreignKeysSql}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}