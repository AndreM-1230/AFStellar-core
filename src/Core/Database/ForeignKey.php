<?php

namespace App\Core\Database;

class ForeignKey
{
    protected $column;
    protected $references;
    protected $on;
    protected $onDelete = '';
    protected $onUpdate = '';

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->on = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = " ON DELETE {$action}";
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = " ON UPDATE {$action}";
        return $this;
    }

    public function compile(): string
    {
        return "CONSTRAINT fk_{$this->column} FOREIGN KEY ({$this->column}) 
                REFERENCES {$this->on} ({$this->references}){$this->onDelete}{$this->onUpdate}";
    }
}