<?php

namespace App\Core;

Use PDO;

class QueryBuilder
{
    protected $connection;
    protected string $table;
    protected $model;
    protected array $wheres = [];
    protected array$bindings = [];
    protected $limit;
    protected $offset;
    protected $orWhere = false;
    protected array $columns = ['*'];
    protected array $orders = [];
    protected array $group = [];
    protected array $having = [];
    protected array $joins = [];
    protected array $unions = [];

    public function __construct(PDO $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function model($model): static
    {
        $this->model = $model;
        return $this;
    }

    public function select($columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where($column, $operator, $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $type = 'AND';
        $this->wheres[] = compact('type', 'column', 'operator', 'value');
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere($column, $operator, $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $type = 'OR';
        $this->wheres[] = compact('type', 'column', 'operator', 'value');
        $this->bindings[] = $value;
        return $this;
    }

    public function whereGroup(\Closure $callback): static
    {
        $subQuery = new self($this->connection, $this->table);
        $callback($subQuery);
        if (!count($subQuery->wheres)) {
            return $this;
        }
        $this->wheres[] = [
            'type' => 'AND',
            'group' => $subQuery->wheres
        ];
        $this->bindings = array_merge($this->bindings, $subQuery->bindings);
        return $this;
    }

    public function orWhereGroup(\Closure $callback): static
    {
        $subQuery = new self($this->connection, $this->table);
        $callback($subQuery);
        if (!count($subQuery->wheres)) {
            return $this;
        }
        $this->wheres[] = [
            'type' => 'OR',
            'group' => $subQuery->wheres
        ];
        $this->bindings = array_merge($this->bindings, $subQuery->bindings);
        return $this;
    }

    public function whereIn($column, array $values, $type = 'AND'): static
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'operator' => 'IN',
            'value' => '(' . $placeholders . ')'
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNotIn($column, array $values): static
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => '(' . $placeholders . ')'
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNull($column, $type = 'AND'): static
    {
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'operator' => 'IS NULL',
            'value' => null
        ];
        return $this;
    }

    public function whereNotNull($column): static
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT NULL',
            'value' => null
        ];
        return $this;
    }

    public function having($raw): static
    {
        $this->having[] = $raw;
        return $this;
    }

    public function union($query, $all = false): static
    {
        $this->unions[] = [
            'query' => $query,
            'all' => $all
        ];
        return $this;
    }

    protected function compileUnions(): string
    {
        if (empty($this->unions)) {
            return '';
        }
        $unionSql = '';
        foreach ($this->unions as $union) {
            $this->bindings = array_merge($this->bindings, $union['query']->bindings);
            $key = $union['all'] ? 'UNION ALL' : 'UNION';
            $unionSql .= " {$key} (" . $union['query']->compileSelect() . ")";
        }
        return $unionSql;
    }

    public function get(): Collection
    {
        $sql = $this->compileSelect();
        $sth = $this->connection->prepare($sql);
        foreach ($this->bindings as $key => $bind) {
            $sth->bindParam($key+1, $bind);
        }
        $sth->execute($this->bindings);
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $items = $this->model
            ? array_map(function ($item) {
                return new $this->model($item);
            }, $result)
            : $result;
        return new Collection($items);
    }

    public function join($table, $first, $operator = null, $second = null, $type = 'INNER'): static
    {
        $join = new \stdClass();
        $join->table = $table;
        $join->type = strtoupper($type);

        if ($first instanceof \Closure) {
            $join->closure= $first;
        } else {
            $join->first = $first;
            $join->operator = $operator;
            $join->second = $second;
        }
        $this->joins[] = $join;
        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin($table, $first, $operator = null, $second = null): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    protected function compileJoins(): string
    {
        return implode('', array_map(function($join) {
            if (isset($join->closure)) {
                $query = new self($this->connection, $join->table);
                $join->closure($query);
                return "{$join->type} JOIN {$join->table} ON {$query->buildWhereClause($query->wheres)}";
            }
            $join->table =  implode('` as `', explode(' as ', $join->table));
            $join->table = '`' . implode('`.`', explode('.', $join->table)) . '`';
            $join->first = '`' . implode('`.`', explode('.', $join->first)) . '`';
            return " {$join->type} JOIN {$join->table} ON {$join->first} {$join->operator} {$join->second} ";
        }, $this->joins));
    }

    protected function compileSelect(): string
    {
        $sql = [
            'SELECT',
            $this->compileColumns(),
            'FROM',
            "`{$this->table}`"
        ];
        $sql = implode(' ', $sql);
        if (!empty($this->joins)) {
            $sql .= $this->compileJoins();
        }
        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $sql .= $this->buildWhereClause($this->wheres);
        }
        $sql .= $this->compileGroup();
        $sql .= $this->compileHaving();
        $sql .= $this->compileOrders();
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset) {
                $sql .= " OFFSET {$this->offset}";
            }
        }
        $sql .= $this->compileUnions();

        return $sql;
    }

    protected function compileColumns(): string
    {
        $columns = [];
        if (!empty($this->columns)) {
            foreach ($this->columns as $column) {
                $col = implode(' as ', explode(' as ', $column));
                $col = implode('.', explode('.', $col));
                $columns[] = str_replace('*', '*', $col);
            }
        }
        return !empty($columns) ?
            implode(', ', $columns) :
            '*';
    }

    public function buildWhereClause($wheres): string
    {
        $clauses = [];
        foreach ($wheres as $key => $where) {
            if (is_null($where['value'])) {
                $value = '';
            } else {
                $value = in_array($where['operator'], ['IN', 'NOT IN']) ? $where['value'] : '?';
            }
            $prefix = $key === 0 ? '' : $where['type'] . ' ';
            if (isset($where['group'])) {
                $groupSql = $this->buildWhereClause($where['group']);
                $clauses[] = $prefix . '(' . $groupSql . ')';
            } else {
                if ($where['sql']) {
                    $clauses[] = $prefix . "{$where['sql']}";
                } else {
                    if ($this->model) {
                        $type = $this->model::getColumnType($where['column']);
                        if ($type && $type['data_type'] === 'bit' && $type['type'] === 'bit(1)') {
                            $value = 'b?';
                        }
                    }
                    $where['column'] = implode('`.`', explode('.', $where['column']));
                    $clauses[] = $prefix . "`{$where['column']}` {$where['operator']} {$value}";
                }

            }
        }
        return implode(' ', $clauses);
    }

    public function first()
    {
        $result = $this->limit(1)->get();
        return $result[0] ?? null;
    }

    public function limit($limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function insert(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = array_fill(0, count($data), '?');
        foreach (array_keys($data) as $key => $data_value) {
            $type = $this->model::getColumnType($data_value);
            if ($type && $type['data_type'] === 'bit' && $type['type'] === 'bit(1)') {
                $placeholders[$key] = 'b?';
            }
        }
        $placeholders = implode(', ', $placeholders);
        $sql  = "INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})";
        $sth = $this->connection->prepare($sql);
        return $sth->execute(array_values($data));
    }

    public function update(array $data): bool
    {
        $setClause = implode(', ', array_map(function($column) {
            $value = '?';
            $type = $this->model::getColumnType($column);
            if ($type && $type['data_type'] === 'bit' && $type['type'] === 'bit(1)') {
                $value = 'b?';
            }
            return "{$column} = {$value}";
        }, array_keys($data)));
        $sql = "UPDATE `{$this->table}` SET {$setClause}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            foreach ($this->wheres as $where) {
                $where_value = '?';
                $type = $this->model::getColumnType($where['column']);
                if ($type && $type['data_type'] === 'bit' && $type['type'] === 'bit(1)') {
                    $where_value = 'b?';
                }
                $whereClauses[] = "{$where['column']} {$where['operator']} {$where_value}";
            }
            $sql .= implode(' AND ', $whereClauses);
        }
        $sth = $this->connection->prepare($sql);
        $bindings = array_merge(array_values($data), $this->bindings);
        return $sth->execute($bindings);
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM `{$this->table}`";
        $value = '?';
        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            foreach ($this->wheres as $where) {
                $type = $this->model::getColumnType($where['column']);
                if ($type && $type['data_type'] === 'bit' && $type['type'] === 'bit(1)') {
                    $value = 'b?';
                }
                $whereClauses[] = "{$where['column']} {$where['operator']} {$value}";
            }
            $sql .= implode(' AND ', $whereClauses);
        }
        $sth = $this->connection->prepare($sql);
        return $sth->execute($this->bindings);
    }

    public function orderBy($column, $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    public function groupBy($column): static
    {
        $this->group[] = $column;
        return $this;
    }

    public function compileOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }
        $clauses = array_map(
            function($order) {
                $order['column'] = implode('`.`', explode('.', $order['column']));
                return "`{$order['column']}` {$order['direction']}";
            },
            $this->orders
        );
        return ' ORDER BY ' . implode(', ', $clauses);
    }

    public function compileGroup(): string
    {
        if (empty($this->group)) {
            return '';
        }
        return ' GROUP BY ' . implode(', ', $this->group);
    }

    public function compileHaving(): string
    {
        if (empty($this->having)) {
            return '';
        }
        return ' HAVING ' . implode(' AND ', $this->having);
    }

    public function toRawSql(): string
    {
        return $this->compileSelect();
    }

    public function toRawSqlData()
    {
        $sql = $this->toRawSql();
        $bindings = $this->bindings;

        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'$binding'" : $binding;
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        return $sql;
    }

    public function selectRaw($expression, $bindings = []): static
    {
        if (is_array($this->columns) && count($this->columns) === 1 && $this->columns[0] === '*') {
            $this->columns = [DB::raw($expression)->getValue()];
        } else {
            $this->columns[] = DB::raw($expression)->getValue();
        }
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    public function whereRaw($sql, $type = 'AND', $bindings = []): static
    {
        if (is_array($type)) {
            $bindings = $type;
            $type = 'AND';
        }
        $this->wheres[] = [
            'type' => $type,
            'sql' => $sql,
        ];
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }
}