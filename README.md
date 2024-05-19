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
use function Gzhegow\Di\_di_bind_lazy;
use function Gzhegow\Di\_php_reflect_cache;
use function Gzhegow\Di\_di_get_generic_lazy;
use function Gzhegow\Di\_php_reflect_cache_settings;


require_once __DIR__ . '/vendor/autoload.php';


// > configure php
ini_set('memory_limit', '32M');

// > configure environment
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


// >>> configure cache
$cacheDir = __DIR__ . '/var/cache';
$cacheNamespace = 'php.reflect_cache';

// > gzhegow, you can use filepath (cache will be made with `file_put_contents` + `serialize`)
$cacheFilepath = "{$cacheDir}/{$cacheNamespace}/latest.cache";
_php_reflect_cache_settings([
    'mode'     => REFLECT_CACHE_MODE_STORAGE_CACHE,
    'filepath' => $cacheFilepath,
]);

// > `composer require symfony/cache`
// $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
//     $cacheNamespace, 0, $cacheDir
// );
// _php_reflect_cache_settings([
//     'mode'    => REFLECT_CACHE_MODE_STORAGE_CACHE,
//     'adapter' => $symfonyCacheAdapter,
// ]);


// >>> clear cache (if you needed it; usually - console command)
$cacheNew = (object) [];
_php_reflect_cache($cacheNew); // > ask cache (it will be cleared cause of mode set to clear)


// >>> set up the container
// > set current instance
$di = _di();
// > or you can set existing $di to global scope
// $di = _di(new Di());


// >>> factory binding MyClassAInterface to be resolved as object MyClassAA with passed arguments (config reading, for example)
_di_bind(MyClassOneInterface::class, function () {
    $object = _di_make(MyClassOneOne::class, [ 123 ]);

    return $object;
});

// >>> lazy binding MyClassBInterface to be resolved as object MyClassB after any method of class MyClassB been called
_di_bind_lazy(MyClassTwoInterface::class, MyClassTwo::class);

// >>> normal binding MyClassC to be resolved as object MyClassC
_di_bind(MyClassThree::class);

// >>> extend binding that will be called after object creation to allow to give to service more dependencies / aware
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


// >>> use container
// > get service
$instance = _di_get(MyClassThree::class);
// > or you can use similar getter but with @template/generic PHPDoc support for PHPStorm
// $instance = _di_generic(MyClassC::class, MyClassC::class);

var_dump(get_class($instance));
// string(28) "Gzhegow\Di\Demo\MyClassThree"

// > get lazy service
$instance = _di_get_generic_lazy(MyClassTwoInterface::class, MyClassTwo::class);
var_dump(get_class($instance));
// string(27) "Gzhegow\Di\Lazy\LazyService"
$result = $instance->do();
// > ...MyClassB is initialising for 3 seconds...
// Hello, World
var_dump($result);
// int(1)


// >>> save cache in the end of the script for future performance
$cacheCurrent = _php_reflect_cache();
// > ask cache (it will be saved)
_php_reflect_cache($cacheCurrent);
```