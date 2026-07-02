<?php

namespace App\Core\Database;

use App\Core\Config;
use PDO;
use PDOException;

class Migrator
{
    protected $connection;
    protected $migrationsPath;
    protected $migrationsTable = 'migrations';
    protected $migrationsNamespace = 'App\\Database\\migrations\\';

    public function __construct(string $migrationsPath)
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
        $this->migrationsPath = rtrim($migrationsPath, '/') . '/';
        $this->createMigrationsTable();
    }

    protected function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->connection->exec($sql);
    }

    public function migrate(): void
    {
        $this->connection->beginTransaction();
        try {
            $files = $this->getMigrationFiles();
            $ranMigrations = $this->getRanMigrations();
            $batch = $this->getNextBatchNumber();

            foreach ($files as $file) {
                $migrationName = $this->getMigrationName($file);
                if (!in_array($migrationName, $ranMigrations)) {
                    $migration = $this->resolveMigration($file);
                    $migration->up();
                    $this->recordMigration($migrationName, $batch);
                    echo "Migrated: {$migrationName}\n";
                }
            }

            $this->connection->commit();
            echo "Migrations completed successfully.\n";
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw new \RuntimeException("Migration failed: " . $e->getMessage());
        }
    }

    public function rollback(int $steps = 1): void
    {
        $this->connection->beginTransaction();

        try {
            $lastBatch = $this->getLastBatchNumber();
            $targetBatch = max(0, $lastBatch - $steps + 1);

            $migrations = $this->connection
                ->prepare("SELECT migration FROM {$this->migrationsTable} WHERE batch >= ? ORDER BY batch DESC, id DESC");
            $migrations->execute([$targetBatch]);

            foreach ($migrations->fetchAll(PDO::FETCH_COLUMN) as $migration) {
                $this->resolveMigrationByName($migration)->down();
                $this->deleteMigration($migration);
                echo "Rolled back: {$migration}\n";
            }

            $this->connection->commit();
            echo "Rollback completed successfully.\n";
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw new \RuntimeException("Rollback failed: " . $e->getMessage());
        }
    }

    public function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '*.php');
        sort($files);
        return $files;
    }

    protected function getRanMigrations(): array
    {
        $stmt = $this->connection->query("SELECT migration FROM {$this->migrationsTable} ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query("SELECT MAX(batch) FROM {$this->migrationsTable}");
        return (int)$stmt->fetchColumn() + 1;
    }

    protected function getLastBatchNumber(): int
    {
        $stmt = $this->connection->query("SELECT MAX(batch) FROM {$this->migrationsTable}");
        return (int)$stmt->fetchColumn();
    }

    protected function recordMigration(string $migration, int $batch): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)"
        );
        $stmt->execute([$migration, $batch]);
    }

    protected function deleteMigration(string $migration): void
    {
        $stmt = $this->connection->prepare(
            "DELETE FROM {$this->migrationsTable} WHERE migration = ?"
        );
        $stmt->execute([$migration]);
    }

    protected function resolveMigration(string $file): object
    {
        require_once $file;
        $className = $this->getMigrationClass($file);

        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class {$className} not found in {$file}");
        }

        return new $className($this->connection);
    }

    protected function resolveMigrationByName(string $migration): object
    {
        $file = $this->migrationsPath . $migration . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }

        return $this->resolveMigration($file);
    }

    protected function getMigrationName(string $file): string
    {
        return basename($file, '.php');
    }

    protected function getMigrationClass(string $file): string
    {
        $name = $this->getMigrationName($file);
        $className = preg_replace('/[0-9]+_/', '', $name);
        $className = str_replace('_', '', ucwords($className, '_'));

        return $this->migrationsNamespace . $className;
    }
}