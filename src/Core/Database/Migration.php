<?php

namespace App\Core\Database;

use App\Core\Config;
use PDO;
use PDOException;

abstract class Migration
{
    protected $connection;
    protected $table = 'migrations';

    public function __construct()
    {
        $this->connection = new PDO(
            "mysql:host=".Config::$DB_HOST.";dbname=".Config::$DB_NAME,
            Config::$DB_USER,
            Config::$DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => true
            ]
        );
    }

    abstract public function up();

    abstract public function down();

    public function getTable(): string
    {
        return $this->table;
    }

    protected function createTable(string $table, callable $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sql = $blueprint->compileCreate();
        try {
            $this->connection->exec($sql);
            echo "Table {$table} created successfully.\n";
        } catch (PDOException $e) {
            echo "Error creating table {$table}: " . $e->getMessage() . "\n";
        }
    }

    protected function dropTable(string $table)
    {
        $sql = "DROP TABLE IF EXISTS {$table}";

        try {
            $this->connection->exec($sql);
            echo "Table {$table} dropped successfully.\n";
        } catch (PDOException $e) {
            echo "Error dropping table {$table}: " . $e->getMessage() . "\n";
        }
    }

    protected function addColumn(string $table, string $column, string $type)
    {
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$type}";

        try {
            $this->connection->exec($sql);
            echo "Column {$column} added to {$table} successfully.\n";
        } catch (PDOException $e) {
            echo "Error adding column: " . $e->getMessage() . "\n";
        }
    }
}