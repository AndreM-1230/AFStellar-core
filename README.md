# MVCore Light Structure / Описание структуры и ядра модели проекта
### Developer / Разработчик

    *Andre Moore*

## Requirements / Требования
- PHP 7.3 or higher / PHP 7.3 или выше
- MySQL database (partial MSSQL/sqlsrv support) / База данных MySQL (частичная поддержка MSSQL/sqlsrv)

## Configuration Setup / Настройка конфигурации

#### Description / Описание:
Configuration is read from environment variables through the `App\Core\Config` class. Connections are lazily created and cached per connection name / 
Конфигурация считывается из переменных окружения через класс `App\Core\Config`. Подключения создаются лениво и кэшируются по имени подключения.

Environment variables used / Используемые переменные окружения:
- `DATABASE_HOST` (default `localhost`)
- `DATABASE_NAME` (default `mvcore`)
- `DATABASE_USER` (default `root`)
- `DATABASE_PASSWORD` (default `''`)

```php
use App\Core\Config;

// Получить подключение (по умолчанию 'default') / Get connection (default is 'default')
$pdo = Config::connection('default');

// Получить тип подключения (mysql / sql) / Get connection type (mysql / sql)
$type = Config::connectionType('default');
```

`Config::$DB_TYPE` определяет драйвер (`mysql` или `sql` для MSSQL) / `Config::$DB_TYPE` defines the driver (`mysql` or `sql` for MSSQL).

## Base Classes / Базовые классы

### App\Core\Model:

#### Description / Описание:
The Model. Base abstract class inherited by all models / 
Модель. Базовый абстрактный класс, который наследуют все модели

#### Properties / Свойства:
- `protected static $table` - representation or table associated with the model / представление или таблица, с которой связана модель
- `protected $fillable` - array of table fields mapped to the model / массив полей таблицы, отображенный в модели
- `protected $relations` - array of related model data obtained through relationships / полученный массив данных моделей связанных отношениями
- `protected $joined` - array of data obtained through table joins / полученный массив данных полученных с помощью **join** присоединения таблиц
- `protected $exists` - indicates whether model data exists in the database / отображение существования данных модели в БД
- `protected static $columnTypes` - array of data types for `$fillable` fields / массив типов данных полей `$fillable` в таблице модели

#### Methods / Методы:
- `getColumnTypes()` - get `$columnTypes` (loads column metadata from `INFORMATION_SCHEMA.COLUMNS`) / получение `$columnTypes` (загружает метаданные колонок из `INFORMATION_SCHEMA.COLUMNS`)
- `getColumnType($column)` - get data type for specific field / получение типа данных для определенного поля
- `query()` - execute database query via `QueryBuilder` / выполнение запроса к бд через `QueryBuilder`
- `getFillable()` - get all `$fillable` / получение всех `$fillable`
- `fillable($name)` - get value of a single fillable attribute / получение значения одного заполняемого атрибута
- `getItems()` - get `$fillable`, or `$joined` if `$fillable` is empty / получение `$fillable`, либо `$joined`, если `$fillable` пуст
- `fill(array $values)` - mass attribute assignment / массовое заполнение атрибутов
- `save(bool $addLog = false)` - save new model to database (auto-detects `bit(1)` columns) / сохранение новой модели в бд (автоопределение колонок типа `bit(1)`)
- `update(array $attributes, bool $addLog = false)` - update model in database by **id**, updating only changed fields / обновление модели в бд по **id**, обновляются только изменённые поля
- `delete(bool $addLog = false)` - delete model from database by **id** / удаление модели из бд по **id**
- `all()` - get all class instances from database / получение всех экземпляров класса из бд
- `find($value, $name = 'id')` - get specific class instance from database / получение конкретного экземпляра класса из бд
- `hasMany($related, $foreignKey = null, $localKey = 'id')` - one-to-many relationship / отношение "один-ко-многим"
- `belongsTo($related, $foreignKey = null, $ownerKey = 'id')` - inverse relationship / обратное отношение
- `hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondaryKey = null)` - one-to-many through an intermediate table / отношение "один-ко-многим" через промежуточную таблицу
- `belongsToThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $secondaryKey = null)` - inverse relationship through an intermediate table / обратное отношение через промежуточную таблицу

### App\Core\QueryBuilder:

#### Description / Описание:
Query Builder. Base class used in model objects and DB facade / 
Построитель запросов. Базовый класс, используется в объектах модели и фасаде DB

#### Methods / Методы:
- `select($columns = ['*'])` - specify fields to select / указание полей получаемых в `SELECT` запросе
- `where($column, $operator, $value = null)` - `WHERE` condition / условие `WHERE`
- `orWhere($column, $operator, $value = null)` - `OR WHERE` condition / условие `OR WHERE`
- `whereGroup(\Closure $callback)` - condition grouping function / использование функции группировки условий
- `orWhereGroup(\Closure $callback)` - condition grouping function / использование функции группировки условий
- `whereIn($column, array $values)` - `WHERE IN` condition / условие `WHERE IN`
- `whereNotIn($column, array $values)` - `WHERE NOT IN` condition / условие `WHERE NOT IN`
- `whereNull($column)` - `WHERE NULL` condition / условие `WHERE NULL`
- `whereNotNull($column)` - `WHERE NOT NULL` condition / условие `WHERE NOT NULL`
- `having($raw)` - raw `HAVING` clause / произвольное условие `HAVING`
- `union($query, $all = false)` - `UNION` / `UNION ALL` with another query / объединение с другим запросом через `UNION` / `UNION ALL`
- `get()` - compile query and get data as a `Collection` / компиляция запроса и получение данных в виде `Collection`
- `join($table, $first, $operator = null, $second = null, $type = 'INNER')` - `INNER JOIN` (also accepts a `\Closure` for complex conditions) / `INNER JOIN` (также поддерживает `\Closure` для сложных условий)
- `leftJoin($table, $first, $operator = null, $second = null)` - `LEFT JOIN`
- `rightJoin($table, $first, $operator = null, $second = null)` - `RIGHT JOIN`
- `first()` - get first row of query / получение первой строки запроса
- `limit($limit)` - `LIMIT` clause / условие `LIMIT`
- `offset($offset)` - `OFFSET` clause / условие `OFFSET`
- `insert(array $data)` - insert data into database / добавление данных в бд
- `update(array $data)` - update data in database / обновление данных в бд
- `delete()` - delete from database / удаление из бд
- `orderBy($column, $direction = 'ASC')` - sorting / сортировка
- `orderByRaw($column)` - raw `ORDER BY` expression / произвольное выражение `ORDER BY`
- `groupBy($column)` - `GROUP BY` clause / условие `GROUP BY`
- `toRawSql()` - compile query to SQL string / компиляция запроса и вывод в sql строку
- `toRawSqlData()` - compile query to SQL string with data substituted / компиляция запроса и вывод в sql строку с подставленными данными
- `selectRaw($expression, $bindings = [])` - raw SQL for custom field in `SELECT` / sql строка для произвольного поля в `SELECT`
- `whereRaw($sql, $type = 'AND', $bindings = [])` - raw SQL for custom `WHERE` condition / sql строка для произвольного условия `WHERE`

### App\Core\DB:
#### Description / Описание:
DB Facade. Base class for database queries without model, returns array / 
Фасад DB. Базовый класс, построение запросов к бд без указания модели, ответ выводится в массив

#### Properties / Свойства:
- `protected static $connection` - database connection (default - main DB) / подключение к бд (базовое - основная бд)

#### Methods / Методы:
- `setConnection(string $connectionName = 'default')` - select database connection by name (from `Config`) for the next query / выбор подключения к бд по имени (из `Config`) для следующего запроса
- `table($table)` - select table for query, returns `QueryBuilder` / выбор таблицы для запроса, возвращает `QueryBuilder`
- `raw($value)` - raw SQL expression, returns `RawExpression` / произвольное sql выражение, возвращает `RawExpression`
- `select($sql, $bindings = [])` - `SELECT` query string and parameters / строка `SELECT` запроса и массив параметров
- `insert($sql, $bindings = [])` - `INSERT` query string and parameters / строка `INSERT` запроса и массив параметров
- `update($sql, $bindings = [])` - `UPDATE` query string and parameters / строка `UPDATE` запроса и массив параметров
- `delete($sql, $bindings = [])` - `DELETE` query string and parameters / строка `DELETE` запроса и массив параметров
- `quote($sql)` - quote a string for use in a query / экранирование строки для использования в запросе
- `exec($sql)` - execute a raw SQL statement / выполнение произвольного sql запроса
- `beginTransaction()` / `commit()` / `rollBack()` - transaction control / управление транзакциями

### App\Core\Controller:

#### Description / Описание:
Controller. Base class inherited by all controllers / 
Контроллер. Базовый класс, наследуемый всеми контроллерами

#### Methods / Методы:
- `view($viewName, $data = [])` - render `$data` in `$viewName` view via `SimpleBlade` / вывод данных `$data` в представление `$viewName` через `SimpleBlade`
- `json($data)` - output data in **json** format and terminate execution / вывод данных в **json** формате с завершением выполнения


### App\Core\Collection:

#### Description / Описание:
Коллекция моделей. Обеспечивает удобную работу с наборами моделей

#### Methods / Методы:
- `make(array $items = [])` - создать коллекцию (статический конструктор)
- `all()` - получить все модели коллекции
- `add($item)` - добавить элемент в коллекцию
- `first()` - получить первую модель
- `last()` - получить последнюю модель
- `filter(callable $callback)` - фильтрация коллекции
- `where(string $key, $value, bool $strict = true)` - фильтрация по атрибуту
- `firstWhere(string $key, $value)` - возвращает первый найденный элемент коллекции
- `sortBy(string $key, bool $ascending = true)` - сортировка по атрибуту
- `keyBy(string $key)` - установка значения как ключа
- `merge($items)` - объединение с другой коллекцией или массивом
- `chunk(int $size)` - разбить коллекцию на части
- `pluck(string $key)` - получить массив значений атрибута
- `map(callable $callback)` - преобразование коллекции
- `each(callable $callback)` - итерация по коллекции (возврат `false` из колбэка прерывает итерацию)
- `reduce(callable $callback, $initial = null)` - свёртка коллекции в единое значение
- `toArray()` - преобразовать в массив
- `groupBy(string $key)` - группировка по ключу
- `toJson()` - преобразовать в JSON
- `save()` - сохранить все модели коллекции
- `delete()` - удалить все модели коллекции

Коллекция реализует `ArrayAccess` (`$collection[0]`) и `IteratorAggregate` (`foreach`) / The collection implements `ArrayAccess` and `IteratorAggregate`.

### App\Core\RawExpression:

#### Description / Описание:
Обёртка для произвольных sql-выражений, используемых в `DB::raw()` и `QueryBuilder::selectRaw()` / 
Wrapper for raw SQL expressions used by `DB::raw()` and `QueryBuilder::selectRaw()`.

#### Methods / Методы:
- `getValue()` - получить исходное значение выражения
- `__toString()` - привести выражение к строке

### App\Core\Request:

#### Description / Описание:
Обёртка над HTTP запросом (`$_GET`, `$_POST`, `$_FILES`, `$_SERVER`, `$_COOKIE`, тело запроса) / 
Wrapper around the HTTP request (`$_GET`, `$_POST`, `$_FILES`, `$_SERVER`, `$_COOKIE`, request body).

#### Methods / Методы:
- `createFromGlobals()` - создать объект `Request` из глобальных переменных PHP (статический), автоматически парсит JSON body / creates a `Request` from PHP globals, auto-parses JSON body
- `all()` - все GET и POST параметры / all GET and POST parameters
- `input($key, $default = null)` - получить значение из POST либо GET / get a value from POST or GET
- `query($key = null, $default = null)` - получить GET параметр(ы) / get GET parameter(s)
- `post($key = null, $default = null)` - получить POST параметр(ы) / get POST parameter(s)
- `has($key)` / `filled($key)` - проверка наличия / непустого значения параметра
- `only(array $keys)` / `except(array $keys)` - выборка параметров
- `file($key)` / `hasFile($key)` - работа с загруженными файлами
- `method()` / `isMethod($method)` - HTTP метод запроса
- `path()` - путь запроса (без query string)
- `header($key = null, $default = null)` - HTTP заголовки
- `isJson()` / `json($key = null, $default = null)` - работа с JSON телом запроса
- `getContent()` - сырое тело запроса
- `cookie($key = null, $default = null)` - куки
- `setRouteParams($params)` / `attributes()` / `getAttribute($key, $default = null)` / `setAttribute($key, $value)` - параметры маршрута и произвольные атрибуты

### App\Core\Route:

#### Description / Описание:
Простой роутер с поддержкой параметров маршрута и middleware / 
Simple router supporting route parameters and middleware.

#### Methods / Методы:
- `init($path = '')` - инициализация роутера, `$path` - базовый префикс приложения / initializes the router, `$path` is the app's base prefix
- `get($uri, $action, $middleware = [])`
- `post($uri, $action, $middleware = [])`
- `put($uri, $action, $middleware = [])`
- `patch($uri, $action, $middleware = [])`
- `delete($uri, $action, $middleware = [])`
- `dispatch()` - определяет текущий маршрут и выполняет его / matches and executes the current route

`$action` может быть замыканием (`\Closure`) или строкой вида `'ИмяКонтроллера@метод'` (класс ищется в `App\Controllers`) / `$action` can be a `\Closure` or a `'ControllerName@method'` string (resolved from `App\Controllers`).
Маршруты поддерживают параметры `{param}` и опциональные параметры `{?param}` / Routes support `{param}` and optional `{?param}` segments.
`$middleware` — массив классов, реализующих интерфейс `Middleware` (метод `handle()`) / `$middleware` is an array of classes implementing the `Middleware` interface (`handle()` method).

### App\Core\Log:

#### Description / Описание:
System Logger. Base class for application logging with support for log levels and data context /
Системный логгер. Базовый класс для логирования приложения с поддержкой уровней логирования и контекста данных.

#### Properties / Свойства:
- `protected static $logPath` - path to the log file (defaults to _ROOT/logs/app.log) / путь к файлу логов (по умолчанию _ROOT/logs/app.log)

- `protected static $logLevel` - minimum logging level (INFO, WARNING, DEBUG) / минимальный уровень логирования

#### Methods / Методы:
- `setPath(string $logFileName = 'app')` - set custom log file name / установка пользовательского имени файла логов

- `setLogLevel(string $logLevel = 'INFO')` - set global logging level / установка глобального уровня логирования

- `write(string $message, string $logLevel = 'INFO', array $data = [], bool $subData = false)` - write a log entry. If $subData is true, automatically appends request IP, method, and user ID / запись лога. Если $subData равно true, автоматически добавляет IP, метод запроса и ID пользователя.

- `read($lenght = false, $type = false, $logFileName = "app")` - read log entries with optional filtering by type and limit / чтение записей лога с возможностью фильтрации по типу и ограничением количества строк.

- `info($message, $data = [], $subData = false)` / `error(...)` / `warning(...)` / `debug(...)` / `fatal(...)` - helper methods for writing logs at specific levels / вспомогательные методы для записи логов на определенных уровнях.

### App\Core\SimpleBlade:

#### Description / Описание:
Упрощённый шаблонизатор в стиле Blade с кэшированием скомпилированных шаблонов / 
Simplified Blade-style template engine with compiled template caching.

Поддерживаемые директивы / Supported directives:
- `@extends('layout')`, `@section('name')` ... `@endsection`, `@yield('name')` - наследование шаблонов / template inheritance
- `@include('view')` - подключение другого шаблона / including another view
- `@if(...)`, `@elseif(...)`, `@else`, `@endif` - условия / conditionals
- `@foreach(...)`, `@endforeach`, `@for(...)`, `@endfor` - циклы / loops
- `@php` ... `@endphp` - произвольный PHP код / raw PHP code
- `@push('stack')` ... `@endpush`, `@stack('stack')` - именованные стеки контента (например, для скриптов) / named content stacks (e.g. for scripts)
- `{{ $var }}` - вывод с экранированием `htmlspecialchars` / output with `htmlspecialchars` escaping
- `<x-component attr="value" />` - подключение компонента из `app/Views/components/` / includes a component from `app/Views/components/`

Представления хранятся в `app/Views/*.view.php`, скомпилированные шаблоны кэшируются в `app/cache/` / Views live in `app/Views/*.view.php`, compiled templates are cached in `app/cache/`.

---

## Migrations / Миграции

### App\Core\Database\Blueprint:

#### Description / Описание:
Используется внутри `up()` миграции для описания структуры таблицы / 
Used inside a migration's `up()` method to describe a table's structure.

#### Methods / Методы:
- `increments($column)` - авто-инкрементный первичный ключ `BIGINT`
- `string($column, $length = 255)` - `VARCHAR`
- `varchar($column, $length = 1)` - `VARCHAR` с произвольной длиной
- `text($column)` - `TEXT`
- `integer($column, $length = 11)` - `INT`
- `bigInt($column)` - `BIGINT`
- `float($column, $precision = 8, $scale = 2)` - `FLOAT`
- `double($column)` - `DOUBLE`
- `bit($column, $length = 1)` - `BIT`
- `date($column)` - `DATE`
- `enum($column, array $values)` - `ENUM`
- `timestamps()` - добавляет `created_at` и `updated_at`
- `index(array $column, $name = null)` - индекс по колонке(ам)
- `notNull()` - модификатор `NOT NULL` для последней добавленной колонки
- `defaultValue($value)` - модификатор `DEFAULT` для последней добавленной колонки
- `comment($comment)` - модификатор `COMMENT` для последней добавленной колонки
- `foreign($column)` - создать внешний ключ, возвращает `ForeignKey`
- `compileCreate()` - скомпилировать `CREATE TABLE` запрос

### App\Core\Database\ForeignKey:

#### Description / Описание:
Описание внешнего ключа, создаётся через `Blueprint::foreign()` / 
Describes a foreign key, created via `Blueprint::foreign()`.

#### Methods / Методы:
- `references($column)` - колонка в связанной таблице
- `on($table)` - связанная таблица
- `onDelete($action)` - действие `ON DELETE` (по умолчанию `NO ACTION`)
- `onUpdate($action)` - действие `ON UPDATE` (по умолчанию `NO ACTION`)
- `compile()` - скомпилировать `CONSTRAINT ... FOREIGN KEY ...`

### App\Core\Database\Migration:

#### Description / Описание:
Абстрактный базовый класс, который наследуют все миграции / 
Abstract base class inherited by all migrations.

#### Methods / Методы:
- `up()` / `down()` - абстрактные методы применения / отката миграции
- `getTable()` - имя таблицы миграций (`migrations`)
- `createTable(string $table, callable $callback)` - создать таблицу через `Blueprint`
- `dropTable(string $table)` - удалить таблицу
- `create(string $name, string $sql)` / `drop(string $name, string $sql)` - выполнить произвольный SQL для создания/удаления сущности
- `addColumn(string $table, string $column, string $type)` - добавить колонку
- `changeColumn(string $table, string $old_name, string $new_name, string $type, $value = [])` - переименовать/изменить колонку (для `ENUM` передаётся `$value` со списком значений)

### App\Core\Database\Migrator:

#### Description / Описание:
Отвечает за поиск, выполнение и откат файлов миграций, хранит историю в таблице `migrations` / 
Discovers, runs, and rolls back migration files; tracks history in the `migrations` table.

#### Methods / Методы:
- `__construct(string $migrationsPath)` - создаёт подключение к бд по данным `Config` и таблицу `migrations`, если её нет
- `migrate()` - выполняет все ещё не применённые миграции в рамках транзакции
- `rollback(int $steps = 1)` - откатывает миграции последнего батча (батчей)
- `getMigrationFiles()` - список файлов миграций, отсортированных по имени

### Migration Commands / Команды миграций
Выполняется из корня проекта / Run from the project root:

```
php migrate.php [command] [parameters]
php migrate.php [команда] [параметры]
```

Available commands / Доступные команды:

| Command | Description / Описание |
|---|---|
| `migrate` | Run all pending migrations / Выполнить все новые миграции |
| `rollback [steps]` | Rollback migrations, default: 1 / Откатить миграции, по умолчанию 1 |
| `create <name>` | Create new migration file in `app/Database/migrations/` / Создать новый файл миграции в `app/Database/migrations/` |
| `help` | Show this help message / Показать эту справку |

Файлы миграций именуются `{timestamp}_{name}.php` и содержат класс в namespace `App\Database\migrations` / Migration files are named `{timestamp}_{name}.php` and contain a class in the `App\Database\migrations` namespace:

```php
namespace App\Database\migrations;

use App\Core\Database\Migration;

class UserMigration extends Migration
{
    public function up()
    {
        // Код миграции
        $this->createTable('users', function($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropTable('users');
    }
}
```

---

## Examples / Примеры
```php
//all()
User::all();

//first()
User::first();

//find()
User::find(10);

//where()
User::query()->where('name', 'John')->where('login', 'like', 'John%')->first();
//Generates query response / Сформирует ответ на запрос:
"SELECT * FROM `users` WHERE `name` = 'John' AND `login` like 'John%' LIMIT 1"

//whereIn()
User::query()->whereIn('login', ['John', 'Doe'])->get();
//Generates query response / Сформирует ответ на запрос:
"SELECT * FROM `users` WHERE `login` IN ('John', 'Doe')"

//whereGroup()
User::query()->whereGroup(function($query) {
    $query->where('name', 'John')->orWhere('login', 'John');
})->get();
//Generates query response / Сформирует ответ на запрос:
"SELECT * FROM `users` WHERE (`name` = 'John' OR `login` = 'John')"

//фасад DB
DB::table('users')->where('id', '<', 5)->limit(2)->get();
//Returns array response for query / Сформирует ответ в виде массива на запрос:
"SELECT * FROM `users` WHERE `id` < 5 LIMIT 2"

//join()
DB::table('users')
    ->leftJoin('posts as p', 'users.id', '=', 'p.user_id')
    ->where('users.id', 5)
    ->select(['users.*', 'p.code as post_code'])
    ->get();
//Returns array response for query / Сформирует ответ в виде массива на запрос:
"SELECT `users`.*,
`p`.`code` as `post_code`
FROM `users`
LEFT JOIN `posts` as `p` ON `users`.`id` = p.user_id 
WHERE `users`.`id` = 5"

//update()
DB::table('users')->where('id', 5)
->update(['login' => 'John']);
//или
User::find(5)->update(['login' => 'John']);

// Фильтрация
$activeUsers = User::all()->where('active', true);

// Массовое обновление
User::all()
    ->where('role', 'user')
    ->each(function($user) {
        $user->last_login = now();
    })
    ->save();

// Маршруты / Routes
use App\Core\Route;

Route::get('/users', 'UserController@index');
Route::get('/users/{id}', 'UserController@show');
Route::post('/users', 'UserController@store', [AuthMiddleware::class]);

// Логи / Log
use App\Core\Log;

// Простая запись лога / Simple log entry
Log::info('User login successful');

// Запись с контекстом и данными запроса / Log with context and request data
Log::warning('Suspicious activity detected', ['attempt' => 3], true);

// Чтение последних 10 ошибок / Read last 10 errors
$errors = Log::read(10, 'ERROR');

//view() - in controller / в контроллере
namespace App\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return $this->view('users', compact(['users']));
    }
    //Other class methods / Другие методы класса
}

//Usage in code / Использование в коде:
$userController = new UserController();
$userController->index();
```

Will render view `Views\users.php` / Выведет представление `Views\users.php`

If view is stored in `Views\Users\index.php`, specify in view: `$this->view('users.index', compact(['users']))` /
Если представление хранится в `Views\Users\index.php`, то во view указывается `$this->view('users.index', compact(['users']))`