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
use Gzhegow\Di\Demo\MyClassFive;
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
use function Gzhegow\Di\_php_throw;
use function Gzhegow\Di\_di_autowire;
use function Gzhegow\Di\_di_get_lazy;
use function Gzhegow\Di\_di_make_lazy;
use function Gzhegow\Di\_di_bind_singleton;


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
set_exception_handler(static function ($e) {
    var_dump($e);
    die();
});


// >>> Создаем контейнер
$di = _di();
// > Или можно передать уже существующий экземпляр, чтобы все функции _di_{action}() работали через него
// $di = _di(new Di());
// >>> Для примера я буду использовать процедурные вызовы \Gzhegow\Di\_di() (для простоты применения), но те же методы доступны и на ООП стиле $di->bind()/$di->get() и тд.
// $di = $di::getInstance();
// $di::setInstance(new Di());

// >>> Ставим режим работы контейнера
$di->setSettings([
    'injectorResolveUseTake' => true, // > будет использовать take(), то есть при незарегистированных зависимостях создаст новые экземпляры и передаст их в конструктор (test/staging)
    // 'injectorResolveUseTake' => false, // > будет использовать get(), а значит при незарегистированных зависимостях выбросит исключение (production)
]);

// >>> Настраиваем кеш для рефлексии функций и конструкторов
$cacheDir = __DIR__ . '/var/cache';
$cacheNamespace = 'app.di';

// >>> Можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
$cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
$cacheFilename = "reflector.cache";
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
print_r('Clearing cache...' . PHP_EOL);
$di->clearCache();
print_r('Cleared.' . PHP_EOL);
print_r(PHP_EOL);


// >>> А тут при создании класса будет использоваться фабричный метод
$fnNewMyClassOne = static function () {
    $object = _di_make(MyClassOneOne::class, [ 123 ]);

    return $object;
};
// _di_bind(MyClassOneInterface::class, $fnNewMyClassOne);

// >>> И его результат будет сохранен как одиночка, то есть при втором вызове get()/ask() вернется тот же экземпляр
// >>> А также зарегистрируем алиас на наш интерфейс по имени (это позволяет нам использовать сервис-локатор не создавая интерфейсы под каждую настройку зависимости)

// >>> Можно зарегистрировать класс в контейнере (смысл только в том, что метод get() не будет выбрасывать исключение)
// _di_bind(MyClassOneOne::class);
// >>> Можно привязать на интерфейс (а значит объект сможет пройти проверки зависимостей на входе конструктора)
// _di_bind(MyClassOneInterface::class, MyClassOneOne::class, $isSingleton = false);
// >>> Можно сразу указать, что созданный экземпляр будет одиночкой
// _di_bind_singleton(MyClassOneInterface::class, MyClassOneOne::class);

_di_bind_singleton(MyClassOneInterface::class, $fnNewMyClassOne);
_di_bind('one', '\Gzhegow\Di\Demo\MyClassOneInterface'); // > Если мы задаем конфигурацию в виде строк, а не в виде Class::class, мы избегаем подгрузки классов через autoloader, точнее откладываем её

// >>> Мы знаем, что сервис MyClassTwo долго выполняет __construct(), например, соединяется по сети, и нам нужно отложить его запуск до первого вызова. Регистриуем как обычно, а дальше запросим через _di_get_lazy()
_di_bind_singleton(MyClassTwoInterface::class, MyClassTwo::class);
_di_bind('two', '\Gzhegow\Di\Demo\MyClassTwoInterface');

// >>> Зарегистрируем класс как синглтон (первый вызов создаст объект, второй - вернет созданный)
_di_bind_singleton(MyClassThree::class);
_di_bind('three', '\Gzhegow\Di\Demo\MyClassThree');

// >>> MyClassThree требует сервисов One и Two, а чтобы не фиксировать сигнатуру конструктора, мы добавим их с помощью Интерфейсов и Трейтов
_di_extend(MyClassOneAwareInterface::class, static function (MyClassOneAwareInterface $aware) {
    $aware->setOne($one = _di_get(MyClassOneInterface::class));
});
_di_extend(MyClassTwoAwareInterface::class, static function (MyClassTwoAwareInterface $aware) {
    $aware->setTwo($two = _di_get_lazy('two'));
});


// >>> Пример. "Дай сервис c заполненными зависимостями"
print_r('Case1:' . PHP_EOL);
$three = _di_get(MyClassThree::class);
var_dump(get_class($three));                          // string(28) "Gzhegow\Di\Demo\MyClassThree"
_assert_true(get_class($three) === 'Gzhegow\Di\Demo\MyClassThree');
//
// >>> Если класс помечен как сиглтон, запросы его вернут один и тот же экземпляр
$three1 = _di_get(MyClassThree::class);
$three2 = _di_get(MyClassThree::class);
$threeByAlias = _di_get('three');
_assert_true($three1 === $three2);
_assert_true($three1 === $threeByAlias);
//
// >>> Еще можно использовать синтаксис указывая выходной тип, чтобы PHPStorm корректно работал с подсказками ("генерики")
// $two = _di_get(MyClassTwoInterface::class, MyClassTwo::class); // > без параметров, бросит исключение, если не зарегистрировано в контейнере
// $two = _di_ask(MyClassTwoInterface::class, MyClassTwo::class); // > get() если зарегистрировано, NULL если не зарегистрировано
// $two = _di_make(MyClassTwoInterface::class, [], MyClassTwo::class); // > всегда новый экземпляр с параметрами
// $two = _di_take(MyClassTwoInterface::class, [], MyClassTwo::class); // > get() если зарегистрировано, make() если не зарегистрировано
print_r(PHP_EOL);


// >>> Ранее мы говорили, что этот сервис слишком долго выполняет конструктор. Запросим его как ленивый. При этом подстановка в аргументы конструктора конечно будет невозможна, но как сервис-локатор - удобная вещь!
// >>> В PHP к сожалению нет возможности создать анонимный класс, который расширяет ("extend") имя класса, который лежит в переменной. Поэтому, к сожалению, только такие LazyService...
print_r('Case2:' . PHP_EOL);
//
// $two = _di_get_lazy(MyClassTwoInterface::class, MyClassTwo::class);
// $two = _di_make_lazy(MyClassTwoInterface::class, [], MyClassTwo::class);
//
$two = _di_get_lazy(MyClassTwoInterface::class, MyClassTwo::class, [ 'hello' => 'User' ]);
$two2 = _di_get_lazy(MyClassTwoInterface::class, MyClassTwo::class);
$two3 = _di_make_lazy(MyClassTwoInterface::class, [ 'hello' => 'User2' ], MyClassTwo::class);
var_dump(get_class($two));                             // string(27) "Gzhegow\Di\Lazy\LazyService"
var_dump(get_class($two2));                            // string(27) "Gzhegow\Di\Lazy\LazyService"
_assert_true(get_class($two) === 'Gzhegow\Di\Lazy\LazyService');
_assert_true(get_class($two2) === 'Gzhegow\Di\Lazy\LazyService');
//
// >>> При вызове первого метода объект внутри LazyService будет создан с аргументами, что указали в __configure() или без них (только зависимости), если не указали
echo 'MyClassB загружается (3 секунды)...' . PHP_EOL;  // MyClassB загружается (3 секунды)...
$two->do();                                            // Hello, [ User ] !
$two2->do();                                           // Hello, [ User2 ] !
_assert_true($two !== $two2);
_assert_true($two->instance === $two2->instance);
echo 'MyClassB загружается (3 секунды)...' . PHP_EOL;  // MyClassB загружается (3 секунды)...
$two3->do();                                           // Hello, [ User2 ] !
_assert_true($two !== $two3);
_assert_true($two->instance !== $two3->instance);
print_r(PHP_EOL);


// >>> Еще пример. "Дозаполним аргументы уже существующего объекта, который мы не регистрировали"
print_r('Case3:' . PHP_EOL);
$four = new MyClassFour();
//
// > вызовет функцию на уже существующем объекте
// _di_autowire($four, $customArgs = [], $customMethod = '__myCustomAutowire'); // > поддерживает несколько дополнительных аргументов
_di_autowire($four);
//
var_dump(get_class($four));                            // string(27) "Gzhegow\Di\Demo\MyClassFour"
var_dump(get_class($four->one));                       // string(29) "Gzhegow\Di\Demo\MyClassOneOne"
_assert_true(get_class($four) === 'Gzhegow\Di\Demo\MyClassFour');
_assert_true(get_class($four->one) === 'Gzhegow\Di\Demo\MyClassOneOne');
$four2 = new MyClassFour();
_di_autowire($four2);
_assert_true($four->one === $four2->one);              // > зависимость по интерфейсу, зарегистрированная как одиночка, будет равна в двух разных экземплярах
print_r(PHP_EOL);


// >>> Еще пример. "Дозаполним аргументы уже существующего объекта, который мы не регистрировали, и который имеет зависимости, которые мы тоже не регистрировали"
print_r('Case4:' . PHP_EOL);
$five = new MyClassFive();
//
// _di_autowire($four, $customArgs = [], $customMethod = '__myCustomAutowire'); // > поддерживает несколько дополнительных аргументов
_di_autowire($five);
//
var_dump(get_class($five));                            // string(27) "Gzhegow\Di\Demo\MyClassFive"
var_dump(get_class($five->four));                      // string(27) "Gzhegow\Di\Demo\MyClassFour"
_assert_true(get_class($five) === 'Gzhegow\Di\Demo\MyClassFive');
_assert_true(get_class($five->four) === 'Gzhegow\Di\Demo\MyClassFour');
$five2 = new MyClassFive();
_di_autowire($five2);
_assert_true($five->four !== $five2->four); // > зависимость по классу, ранее не зарегистрированная, получит два разных экземпляра
print_r(PHP_EOL);


// >>> Еще пример. "Вызовем функцию, подбросив в неё зависимости"
print_r('Case5:' . PHP_EOL);
$result = _di_call(static function (MyClassThree $three) {
    return get_class($three);
});
var_dump($result); // string(28) "Gzhegow\Di\Demo\MyClassThree"
_assert_true($result === 'Gzhegow\Di\Demo\MyClassThree');
print_r(PHP_EOL);


// >>> Теперь сохраним кеш сделанной за скрипт рефлексии для следующего раза
print_r('Saving cache...' . PHP_EOL);
$di->flushCache();
print_r('Saved.');
```