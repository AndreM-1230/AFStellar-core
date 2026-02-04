<?php

namespace App\Core;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use App\Core\Model;

class Collection implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public static function make(array $items = []): self
    {
        return new static($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function add($item): void
    {
        $this->items[] = $item;
    }

    public function first(): ?Model
    {
        return $this->items[0] ?? null;
    }

    public function last(): ?Model
    {
        if (empty($this->items)) {
            return null;
        }
        return $this->items[count($this->items) - 1];
    }

    public function where(string $key, $value, bool $strict = true): self
    {
        return $this->filter(function ($model) use ($key, $value, $strict) {
            if (!array_key_exists($key, $model->getFillable())) {
                return false;
            }

            $attribute = $model->$key;
            return $strict ? $attribute === $value : $attribute == $value;
        });
    }

    public function sortBy(string $key, bool $ascending = true): self
    {
        $items = $this->items;

        usort($items, function (Model $a, Model $b) use ($key, $ascending) {
            $aValue = $a->$key;
            $bValue = $b->$key;

            if ($aValue == $bValue) return 0;

            $result = $aValue < $bValue ? -1 : 1;
            return $ascending ? $result : -$result;
        });

        return new static($items);
    }

    public function keyBy(string $key): self
    {
        $result = [];

        foreach ($this->items as $item) {
            $result[$item->__get($key)] = $item;
        }

        return new static($result);
    }

    public function merge($items): self
    {
        $mergeable = $items instanceof self ? $items->all() : $items;
        return new static(array_merge($this->items, $mergeable));
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    public function reduce(callable $callback, $initial = null)
    {
        $result = $initial;

        foreach ($this->items as $key => $item) {
            $result = $callback($result, $item, $key);
        }

        return $result;
    }

    public function toArray(): array
    {
        return array_map(function ($item) {
            if ($item instanceof Model) {
                return $item->getItems();
            } else {
                return $item;
            }
        }, $this->items);
    }

    public function chunk(int $size): self
    {
        if ($size <= 0) {
            return new static([]);
        }

        $chunks = [];
        $items = array_values($this->items);

        foreach (array_chunk($items, $size) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    public function pluck(string $key): self
    {
        return $this->map(function (Model $item) use ($key) {
            return $item->__get($key);
        });
    }

    public function groupBy(string $key): self
    {
        $groups = [];

        foreach ($this->items as $item) {
            $groupKey = $item->__get($key);
            $groups[$groupKey][] = $item;
        }

        return new static($groups);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->jsonSerialize());
    }

    public function save(): bool
    {
        return $this->reduce(function (bool $result, Model $item) {
            return $result && $item->save();
        }, true);
    }

    public function delete(): bool
    {
        return $this->reduce(function (bool $result, Model $item) {
            return $result && $item->delete();
        }, true);
    }

    public function firstWhere(string $key, $value): ?Model
    {
        return $this->where($key, $value)->first();
    }
}