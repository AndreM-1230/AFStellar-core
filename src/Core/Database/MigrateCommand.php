<?php

namespace App\Core\Database;

class MigrateCommand
{
    public function run(array $args)
    {
        $action = $args[1] ?? 'migrate';
        $migrationsPath = __DIR__ . '/app/migrations/';
        $migrator = new Migrator($migrationsPath);

        switch ($action) {
            case 'migrate':
                $migrator->migrate();
                break;
            case 'rollback':
                $migrator->rollback();
                break;
            default:
                echo "Usage: php migrate.php [migrate|rollback]\n";
        }
    }
}