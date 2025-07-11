# MVCore Light Structure / Описание структуры и ядра модели проекта
### Developer / Разработчик

    *Andre Moore*

## Requirements / Требования
- PHP 7.3 or higher / PHP 7.3 или выше
- MySQL database / База данных MySQL

## Configuration Setup / Настройка конфигурации

#### Description / Описание:
Using the Config Class / Использование класса Config
The framework provides a simple configuration system through the App\Core\Config class.
Фреймворк предоставляет простую систему конфигурации через класс App\Core\Config

```php
Config::init([
    'DB_HOST' => '127.0.0.1',
    'DB_NAME' => 'app_db',
    'DB_USER' => 'app_user',
    'DB_PASS' => 'secure_password'
]);
```

## Base Classes / Базовые классы

### app\Core\Model:

#### Description / Описание:
The Model. Base class inherited by all models /  
Модель. Базовый класс, который наследуют все модели

#### Properties / Свойства:
- `protected static $table` - representation or table associated with the model / представление или таблица, с которой связана модель
- `protected $fillable` - array of table fields mapped to the model / массив полей таблицы, отображенный в модели
- `protected $relations` - array of related model data obtained through relationships / полученный массив данных моделей связанных отношениями
- `protected $joined` - array of data obtained through table joins / полученный массив данных полученных с помощью **join** присоединения таблиц
- `protected $exists` - indicates whether model data exists in the database / отображение существования данных модели в БД
- `protected static $columnTypes` - array of data types for `$fillable` fields / массив типов данных полей `$fillable` в таблице модели

#### Methods / Методы:
- `getColumnTypes()` - get `$columnTypes` / получение `$columnTypes`
- `getColumnType($column)` - get data type for specific field / получение типа данных для определенного поля
- `query()` - execute database query via `QueryBuilder` / выполнение запроса к бд через `QueryBuilder`
- `getFillable()` - get all `$fillable` / получение всех `$fillable`
- `fill(array $values)` - mass attribute assignment / массовое заполнение атрибутов
- `save()` - save new model to database / сохранение новой модели в бд
- `update(array $attributes)` - update model in database by **id** / обновление модели в бд по **id**
- `delete()` - delete model from database by **id** / удаление модели из бд по **id**
- `all()` - get all class instances from database / получение всех экземпляров класса из бд
- `find($value, $name = 'id')` - get specific class instance from database / получение конкретного экземпляра класса из бд
- `hasMany($related, $foreignKey = null, $localKey = 'id')` - basic model relationship implementation / базовая реализация отношений моделей
- `belongsTo($related, $foreignKey = null, $ownerKey = 'id')` - basic model relationship implementation / базовая реализация отношений моделей

### app\Core\QueryBuilder:

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
- `whereNull($column)` - `WHERE NULL` condition / условие `WHERE NULL`
- `whereNotNull($column)` - `WHERE NOT NULL` condition / условие `WHERE NOT NULL`
- `get()` - compile query and get data / компиляция запроса и получение данных
- `join($table, $first, $operator = null, $second = null, $type = 'INNER')` - `INNER JOIN`
- `leftJoin($table, $first, $operator = null, $second = null)` - `LEFT JOIN`
- `rightJoin($table, $first, $operator = null, $second = null)` - `RIGHT JOIN`
- `first()` - get first row of query / получение первой строки запроса
- `limit($limit)` - `LIMIT` clause / условие `LIMIT`
- `offset($offset)` - `OFFSET` clause / условие `OFFSET`
- `insert(array $data)` - insert data into database / добавление данных в бд
- `update(array $data)` - update data in database / обновление данных в бд
- `delete()` - delete from database / удаление из бд
- `orderBy($column, $direction = 'ASC')` - sorting / сортировка
- `toRawSql()` - compile query to SQL string / компиляция запроса и вывод в sql строку
- `toRawSqlData()` - compile query to SQL string with data / компиляция запроса и вывод в sql строку с подставленными данными
- `selectRaw($expression, $bindings = [])` - raw SQL for custom field in `SELECT` / sql строка для произвольного поля в `SELECT`
- `whereRaw($sql, $bindings = [])` - raw SQL for custom `WHERE` condition / sql строка для произвольного условия `WHERE`

### app\Core\DB:
#### Description / Описание:
DB Facade. Base class for database queries without model, returns array /  
Фасад DB. Базовый класс, построение запросов к бд без указания модели, ответ выводится в массив

#### Properties / Свойства:
- `protected static $connection` - database connection (default - main DB) / подключение к бд (базовое - основная бд)

#### Methods / Методы:
- `setConnection(PDO $connection)` - establish database connection / установление подключения к бд, если нужно подключиться к другой бд
- `table($table)` - select table for query / выбор таблицы для запроса
- `raw($value)` - raw SQL query string / строка sql запроса
- `select($sql, $bindings = [])` - `SELECT` query string and parameters / строка `SELECT` запроса и массив параметров
- `insert($sql, $bindings = [])` - `INSERT` query string and parameters / строка `INSERT` запроса и массив параметров
- `update($sql, $bindings = [])` - `UPDATE` query string and parameters / строка `UPDATE` запроса и массив параметров
- `delete($sql, $bindings = [])` - `DELETE` query string and parameters / строка `DELETE` запроса и массив параметров

### app\Core\Controller:

#### Description / Описание:
Controller. Base class inherited by all controllers /  
Контроллер. Базовый класс, наследуемый всеми контроллерами

#### Methods / Методы:
- `view($viewName, $data = [])` - render `$data` in `$viewName` view / вывод данных `$data` в представление `$viewName`
- `json($data)` - output data in **json** format / вывод данных в **json** формате


### app\Core\Collection:

#### Description / Описание:
Коллекция моделей. Обеспечивает удобную работу с наборами моделей

#### Methods / Методы:
- `all()` - получить все модели коллекции
- `first()` - получить первую модель
- `last()` - получить последнюю модель
- `filter(callable $callback)` - фильтрация коллекции
- `where(string $key, $value, bool $strict = true)` - фильтрация по атрибуту
- `firstWhere(string $key, $value)` - возвращает первый найденный элемент коллекции
- `sortBy(string $key, bool $ascending = true)` - сортировка по атрибуту
- `keyBy(string $key)` - установка значения как ключа
- `chunk(int $size)` - забирает часть коллекции
- `pluck(string $key)` - получить массив значений атрибута
- `map(callable $callback)` - преобразование коллекции
- `each(callable $callback)` - итерация по коллекции
- `toArray()` - преобразовать в массив
- `groupBy(string $key)` - группировка по ключу
- `toJson()` - преобразовать в JSON
- `save()` - сохранить все модели коллекции
- `delete()` - удалить все модели коллекции

---

## Migrations / Миграции
```php
//Migration example / Пример миграции
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
### Migration Commands / Команды миграций
Executed within the app directory /  
Выполняется внутри каталога app
Usage / Использование:  
php migrate.php [command] [parameters]  
php migrate.php [команда] [параметры]

Available commands / Доступные команды:  
migrate             - Run all pending migrations / Выполнить все новые миграции  
rollback [steps]    - Rollback migrations (default: 1) / Откатить миграции (по умолчанию 1)  
create <name>       - Create new migration file / Создать новый файл миграции  
help                - Show this help message / Показать эту справку
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
