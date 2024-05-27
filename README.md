# Dependency Injection / Container

Контейнер внедрения зависимостей с поддержкой кеша, ленивых сервисов и фабрик.

Поддерживает файловый кеш для рефлексии, однако сохраняет в рефлексию в рантайме.  
Если вы хотите сохранить её в хранилище - вызовите метод ->flushCache() в конце работы скрипта.  
Функция разогрева не имеет смысла, потому что это требует "создать все возможные обьекты во всех возможных комбинациях".

## Установка

```
composer require gzhegow/di;
```

## Пример

```php
<?php

use Gzhegow\Di\Demo\MyClassTwo;
use Gzhegow\Di\Demo\MyClassFour;
use Gzhegow\Di\Demo\MyClassThree;
use Gzhegow\Di\Demo\MyClassOneOne;
use Gzhegow\Di\Reflector\Reflector;
use Gzhegow\Di\Demo\MyClassOneInterface;
use Gzhegow\Di\Demo\MyClassTwoInterface;
use Gzhegow\Di\Demo\MyClassOneAwareInterface;
use Gzhegow\Di\Demo\MyClassTwoAwareInterface;
use function Gzhegow\Di\_di;
use function Gzhegow\Di\_di_get;
use function Gzhegow\Di\_di_bind;
use function Gzhegow\Di\_di_make;
use function Gzhegow\Di\_di_call;
use function Gzhegow\Di\_di_extend;
use function Gzhegow\Di\_di_autowire;
use function Gzhegow\Di\_di_get_lazy;
use function Gzhegow\Di\_di_make_lazy;
use function Gzhegow\Di\_di_bind_singleton;
use function Gzhegow\Di\_php_throw;


require_once __DIR__ . '/vendor/autoload.php';

function _assert_true(bool $bool, ...$errors)
{
    if (! $bool) {
        throw _php_throw(...$errors);
    }
}


// >>> Настраиваем PHP
ini_set('memory_limit', '32M');

// >>> Настраиваем отлов ошибок
error_reporting(E_ALL);
set_error_handler(static function ($severity, $err, $file, $line) {
    if (error_reporting() & $severity) {
        throw new \ErrorException($err, -1, $severity, $file, $line);
    }
});
set_exception_handler('dd');
// set_exception_handler(static function ($e) {
//     var_dump($e);
//     die();
// });


// >>> Создаем контейнер
$di = _di();
// > Или можно передать уже существующий экземпляр, чтобы все функции _di_{action}() работали через него
// $di = _di(new Di());
// >>> Для примера я буду использовать процедурные вызовы \Gzhegow\Di\_di() (для простоты применения), но те же методы доступны и на ООП стиле $di->bind()/$di->get() и тд.
// $di = $di::getInstance();
// $di::setInstance(new Di());


// >>> Настраиваем кеш для рефлексии функций и конструкторов
$cacheDir = __DIR__ . '/var/cache';
$cacheNamespace = 'php.reflect_cache';

// >>> Можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
$cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
$cacheFilename = "latest.cache";
$di->setCacheSettings([
    // 'reflectorCacheMode'     => Reflector::CACHE_MODE_NO_CACHE, // > не использовать кеш совсем
    // 'reflectorCacheMode'     => Reflector::CACHE_MODE_RUNTIME, // > использовать только кеш памяти на время текущего скрипта
    'reflectorCacheMode'     => Reflector::CACHE_MODE_STORAGE, // > использовать файловую систему или адаптер (хранилище)
    //
    'reflectorCacheDirpath'  => $cacheDirpath,
    'reflectorCacheFilename' => $cacheFilename,
]);

// >>> Либо можно установить пакет `composer require symfony/cache` и использовать адаптер, чтобы запихивать в Редис например
// $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
//     $cacheNamespace, $defaultLifetime = 0, $cacheDir
// );
// $redisClient = \Symfony\Component\Cache\Adapter\RedisAdapter::createConnection('redis://localhost');
// $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\RedisAdapter(
//     $redisClient,
//     $cacheNamespace = '',
//     $defaultLifetime = 0
// );
// $di->setCacheSettings([
//     'reflectorCacheMode'    => Reflector::CACHE_MODE_STORAGE,
//     'reflectorCacheAdapter' => $symfonyCacheAdapter,
// ]);


// >>> Так можно очистить кеш принудительно (обычно для этого делают консольный скрипт и запускают вручную или кроном, но если использовать symfony/cache можно и просто установить TTL - время устаревания)
$di->clearCache();


// >>> Можно зарегистрировать класс в контейнере (смысл только в том, что метод get() не будет выбрасывать исключение)
// _di_bind(MyClassOneOne::class);

// >>> Можно привязать на интерфейс (а значит объект сможет пройти проверки зависимостей на входе конструктора)
// _di_bind(MyClassOneInterface::class, MyClassOneOne::class);

// >>> А тут при создании класса будет использоваться фабричный метод
$fnNewMyClassOne = static function () {
    $object = _di_make(MyClassOneOne::class, [ 123 ]);

    return $object;
};
// _di_bind(MyClassOneInterface::class, $fnNewMyClassOne);

// >>> И его результат будет сохранен как одиночка, то есть при втором вызове get()/ask() вернется тот же экземпляр
_di_bind_singleton(MyClassOneInterface::class, $fnNewMyClassOne);

// >>> Зарегистрируем алиас на наш интерфейс по имени (если мы задаем конфигурацию в виде строк, а не в виде Class::class, мы избегаем подгрузки классов через autoloader, точнее откладываем её)
_di_bind('one', '\Gzhegow\Di\Demo\MyClassOneInterface');

// >>> Мы знаем, что сервис MyClassTwo долго выполняет __construct(), например, соединяется по сети, и нам нужно отложить его запуск до первого вызова. Регистриуем как обычно, а дальше запросим через _di_get_lazy()
_di_bind(MyClassTwoInterface::class, MyClassTwo::class);
_di_bind('two', '\Gzhegow\Di\Demo\MyClassTwoInterface');

// >>> Зарегистрируем класс как синглтон (первый вызов создаст объект, второй - вернет созданный)
_di_bind_singleton(MyClassThree::class);
_di_bind('three', '\Gzhegow\Di\Demo\MyClassThree');

// >>> MyClassThree требует сервисов One и Two, а чтобы не фиксировать сигнатуру конструктора, мы добавим их с помощью Интерфейсов и Трейтов
_di_extend(MyClassOneAwareInterface::class, static function (MyClassOneAwareInterface $aware) {
    $one = _di_get(MyClassOneInterface::class);

    $aware->setOne($one);

    return $aware;
});
_di_extend(MyClassTwoAwareInterface::class, static function (MyClassTwoAwareInterface $aware) {
    $two = _di_get_lazy('two');

    $aware->setTwo($two);

    return $aware;
});


// >>> Пример. "Дай сервис c заполненными зависимостями"
$three = _di_get(MyClassThree::class);
var_dump(get_class($three));                          // string(28) "Gzhegow\Di\Demo\MyClassThree"
_assert_true(get_class($three) === 'Gzhegow\Di\Demo\MyClassThree');


// >>> Если класс помечен как сиглтон, запросы его вернут один и тот же экземпляр
$three1 = _di_get(MyClassThree::class);
$three2 = _di_get(MyClassThree::class);
$threeByAlias = _di_get('three');
_assert_true($three1 === $three2);
_assert_true($three1 === $threeByAlias);

// >>> Еще можно использовать синтаксис указывая выходной тип, чтобы PHPStorm корректно работал с подсказками ("генерики")
// $two = _di_get(MyClassTwoInterface::class, MyClassTwo::class); // > без параметров, бросит исключение, если не зарегистрировано в контейнере
// $two = _di_ask(MyClassTwoInterface::class, MyClassTwo::class); // > get() если зарегистрировано, NULL если не зарегистрировано
// $two = _di_make(MyClassTwoInterface::class, [], MyClassTwo::class); // > всегда новый экземпляр с параметрами
// $two = _di_take(MyClassTwoInterface::class, [], MyClassTwo::class); // > get() если зарегистрировано, make() если не зарегистрировано


// >>> Ранее мы говорили, что этот сервис слишком долго выполняет конструктор. Запросим его как ленивый. При этом подстановка в аргументы конструктора конечно будет невозможна, но как сервис-локатор - удобная вещь!
// >>> В PHP к сожалению нет возможности создать анонимный класс, который расширяет ("extend") имя класса, который лежит в переменной. Поэтому, к сожалению, только такие LazyService...
// $two = _di_get_lazy(MyClassTwoInterface::class, MyClassTwo::class);
// $two = _di_make_lazy(MyClassTwoInterface::class, [], MyClassTwo::class);
$two = _di_make_lazy(MyClassTwoInterface::class, [ 'hello' => 'User' ], MyClassTwo::class);
var_dump(get_class($two));                            // string(27) "Gzhegow\Di\Lazy\LazyService"
_assert_true(get_class($two) === 'Gzhegow\Di\Lazy\LazyService');

// >>> При вызове первого метода объект внутри LazyService будет создан с аргументами, что указали в __configure() или без них (только зависимости), если не указали
echo 'MyClassB загружается (3 секунды)...' . PHP_EOL; // MyClassB загружается (3 секунды)...
$two->do();                                           // Hello, [ User ] !


// >>> Еще пример. "Дозаполним аргументы уже существующего объекта, который мы не регистрировали" - вызовет функцию на уже существующем объекте
$four = new MyClassFour();
// _di_autowire($four, $customArgs = [], $customMethod = '__myCustomAutowire'); // > поддерживает несколько дополнительных аргументов
_di_autowire($four);
var_dump(get_class($four));                           // string(27) "Gzhegow\Di\Demo\MyClassFour"
var_dump(get_class($four->one));                      // string(29) "Gzhegow\Di\Demo\MyClassOneOne"
_assert_true(get_class($four) === 'Gzhegow\Di\Demo\MyClassFour');
_assert_true(get_class($four->one) === 'Gzhegow\Di\Demo\MyClassOneOne');


// >>> Еще пример. "Вызовем функцию, подбросив в неё зависимости"
$result = _di_call(static function (MyClassThree $three) {
    return get_class($three);
});
var_dump($result); // string(28) "Gzhegow\Di\Demo\MyClassThree"
_assert_true($result === 'Gzhegow\Di\Demo\MyClassThree');


// >>> Теперь сохраним кеш сделанной за скрипт рефлексии для следующего раза
$di->flushCache();
```