<?php

require_once __DIR__ . '/autoloader.php';

use App\Core\Database\Migrator;
use App\Core\Config;

Config::init();
$command = $argv[1] ?? 'help';
$param = $argv[2] ?? null;

$migrationsPath = __DIR__ . '/app/Database/migrations/';
try {
    $migrator = new Migrator($migrationsPath);

    switch ($command) {
        case 'migrate':
            echo "Запуск миграций...\n";
            $migrator->migrate();
            echo "Миграции успешно выполнены!\n";
            break;

        case 'rollback':
            $steps = $param ?? 1;
            echo "Откат {$steps} миграции(й)...\n";

            for ($i = 0; $i < $steps; $i++) {
                $migrator->rollback();
            }
            echo "Откат завершен!\n";
            break;

        case 'create':
            if (empty($param)) {
                throw new Exception("Укажите имя миграции");
            }
            createMigrationFile($param);
            break;

        case 'help':
        default:
            showHelp();
    }
} catch (Exception $e) {
    echo "Ошибка: ".$e->getMessage()."\n";
    exit(1);
}

function showHelp() {
    echo <<<HELP
Usage / Использование:
php migrate.php [command] [parameters]
php migrate.php [команда] [параметры]

Available commands / Доступные команды: 
    migrate             - Run all pending migrations / Выполнить все новые миграции 
    rollback [steps]    - Rollback migrations (default: 1) / Откатить миграции (по умолчанию 1)
    create <name>       - Create new migration file / Создать новый файл миграции
    help                - Show this help message / Показать эту справку

HELP;
}

function createMigrationFile($name) {
    $timestamp = date('Y_m_d_His');
    $filename = __DIR__."/app/Database/migrations/{$timestamp}_{$name}.php";
    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

    $content = <<<PHP
<?php
namespace App\Database\migrations;

use App\Core\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        // Код миграции
        \$this->createTable('{$name}', function(\$table) {
            \$table->increments('id');
            \$table->timestamps();
        });
    }
    
    public function down()
    {
        \$this->dropTable('{$name}');
    }
}
PHP;

    file_put_contents($filename, $content);
    echo "Создан файл миграции: {$filename}\n";
}