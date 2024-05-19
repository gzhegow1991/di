# Dependency Injection / Container

Контейнер внедрения зависимостей с функциональностью как у Laravel, только написанный в виде 5 классов и без рекурсивных вызовов callable

Также поддерживает файловый кеш для рефлексии, однако сохраняет в рефлексию в рантайме. Если вы хотите сохранить её в кеш-адаптер - вызовите метод в конце работы скрипта.

Функция разогрева не имеет смысла, потому что это требует "создать все возможные обьекты во всех возможных комбинациях".

```php
<?php

use Gzhegow\Di\Demo\MyClassTwo;
use Gzhegow\Di\Demo\MyClassThree;
use Gzhegow\Di\Demo\MyClassOneOne;
use Gzhegow\Di\Demo\MyClassOneInterface;
use Gzhegow\Di\Demo\MyClassTwoInterface;
use Gzhegow\Di\Demo\MyClassOneAwareInterface;
use Gzhegow\Di\Demo\MyClassTwoAwareInterface;
use function Gzhegow\Di\_di;
use function Gzhegow\Di\_di_get;
use function Gzhegow\Di\_di_bind;
use function Gzhegow\Di\_di_make;
use function Gzhegow\Di\_di_extend;
use function Gzhegow\Di\_di_autowire;
use function Gzhegow\Di\_di_bind_lazy;
use function Gzhegow\Di\_php_reflect_cache;
use function Gzhegow\Di\_di_get_generic_lazy;
use function Gzhegow\Di\_php_reflect_cache_settings;


require_once __DIR__ . '/vendor/autoload.php';


// >>> Настраиваем PHP
ini_set('memory_limit', '32M');

// >>> Настраиваем отлов ошибок
error_reporting(E_ALL);
set_error_handler(static function ($severity, $err, $file, $line) {
    if (error_reporting() & $severity) {
        throw new \ErrorException($err, -1, $severity, $file, $line);
    }
});
set_exception_handler(static function ($e) {
    var_dump($e);
    die();
});


// >>> Настраиваем кеш
$cacheDir = __DIR__ . '/var/cache';
$cacheNamespace = 'php.reflect_cache';

// > Можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
$cacheFilepath = "{$cacheDir}/{$cacheNamespace}/latest.cache";
_php_reflect_cache_settings([
    'mode'     => REFLECT_CACHE_MODE_STORAGE_CACHE,
    'filepath' => $cacheFilepath,
]);

// > Либо можно установить пакет `composer require symfony/cache` и использовать адаптер, чтобы запихивать в Редис например
// $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
//     $cacheNamespace, 0, $cacheDir
// );
// _php_reflect_cache_settings([
//     'mode'    => REFLECT_CACHE_MODE_STORAGE_CACHE,
//     'adapter' => $symfonyCacheAdapter,
// ]);


// >>> Так можно очистить кеш. Создаем пустой объект и передаем в функцию рефлексии кеша. Увидев аргумент, она его запишет в кеш поверху.
$cacheNew = (object) [];
_php_reflect_cache($cacheNew); // > ask cache (it will be cleared cause of mode set to clear)


// >>> Настраиваем сам контейнер
$di = _di();
// > Или можно передать уже существующий экземпляр, чтобы все функции _di_{action}() работали через него
// $di = _di(new Di());


// >>> Назначаем чтобы вызов MyClassOneInterface был распознан как объект MyClassOneOne с указанными аргументами (например, после считывания конфига из файла)
_di_bind(MyClassOneInterface::class, function () {
    $object = _di_make(MyClassOneOne::class, [ 123 ]);

    return $object;
});

// >>> Сервис MyClassTwo очень долго инициализируется и тормозит программу. Мы сделаем его ленивым, чтобы он создавался только тогда, когда мы вызовем на нем какой-то метод
_di_bind_lazy(MyClassTwoInterface::class, MyClassTwo::class);

// >>> А этот сервис мы зарегистрируем сам на себя. Попросил класс - получил. Метод ->has() будет возвращать TRUE
_di_bind(MyClassThree::class);

// >>> После того как класс создан, нам может пригодится наполнить его зависимостями помимо тех, что переданы в конструктор
_di_extend(MyClassOneAwareInterface::class, static function (MyClassOneAwareInterface $aware) {
    $one = _di_get(MyClassOneInterface::class);

    $aware->setOne($one);

    return $aware;
});
_di_extend(MyClassTwoAwareInterface::class, static function (MyClassTwoAwareInterface $aware) {
    $two = _di_get(MyClassTwoInterface::class);

    $aware->setTwo($two);

    return $aware;
});


// >>> Пользуемся!

// > Пример. "Дай сервис":
$three = _di_get(MyClassThree::class);

// > Если мы хотим создать не регистрируя или получить клон объекта, когда он зарегистрирован как синглтон:
// $instance = _di_make(MyClassC::class);

// > Еще можно использовать синтаксис указывая выходной тип, чтобы PHPStorm корректно работал с подсказками
// $instance = _di_generic(MyClassC::class, MyClassC::class);

var_dump(get_class($three));
// string(28) "Gzhegow\Di\Demo\MyClassThree"


// > Еще пример. "Дай ленивый сервис":
$two = _di_get_generic_lazy(MyClassTwoInterface::class, MyClassTwo::class);
var_dump(get_class($two));
// string(27) "Gzhegow\Di\Lazy\LazyService"

// > За счет генериков PHPStorm будет давать подсказки на этот экземпляр, как будто он MyClassTwo, а не LazyService
echo 'MyClassB загружается...' . PHP_EOL;
// > MyClassB загружается...
$two->do();
// > Hello, World


// >>> Еще пример. "Дозаполним аргументы уже существующего объекта, который мы не регистрировали" - вызовет функцию на уже существующем объекте
$four = new \Gzhegow\Di\Demo\MyClassFour();
_di_autowire($four);
// _di_autowire($four, $customArgs = [], $customMethod = '__myCustomAutowire');
var_dump($four);


// >>> Теперь сохраним кеш сделанной за скрипт рефлексии для следующего раза
$cacheCurrent = _php_reflect_cache();
_php_reflect_cache($cacheCurrent);
```