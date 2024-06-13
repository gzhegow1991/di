<?php

use Gzhegow\Di\Lib;
use Gzhegow\Di\Demo\MyClassFour;
use Gzhegow\Di\Demo\MyClassFive;
use Gzhegow\Di\Demo\MyClassThree;
use Gzhegow\Di\Reflector\ReflectorCache;
use Gzhegow\Di\Demo\MyClassOneAwareInterface;
use Gzhegow\Di\Demo\MyClassTwoAwareInterface;


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
    var_dump(Lib::php_dump($e));
    var_dump($e->getMessage());
    var_dump(($e->getFile() ?? '{file}') . ': ' . ($e->getLine() ?? '{line}'));

    die();
});


// >>> INITIALIZE & CONFIGURE

// >>> Создаем контейнер
$di = (new \Gzhegow\Di\DiFactory())->newDi();
$di::setInstance($di);


// >>> Ставим режим работы контейнера
$di->setInjectorSettings([
    'injectorResolveUseTake' => true, // > будет использовать take() вместо get(), то есть при незарегистированных зависимостях создаст новые экземпляры и передаст их в конструктор (условно это "test/staging")
    // 'injectorResolveUseTake' => false, // > будет использовать get() вместо take(), а значит при незарегистированных зависимостях выбросит исключение (условно это "production")
]);


// >>> Настраиваем кеш для рефлексии функций и конструкторов
$cacheDir = __DIR__ . '/var/cache';
$cacheNamespace = 'app.di';

// >>> Можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
$cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
$di->setCacheSettings([
    // 'reflectorCacheMode'     => ReflectorCache::CACHE_MODE_NO_CACHE, // > не использовать кеш совсем
    // 'reflectorCacheMode'     => ReflectorCache::CACHE_MODE_RUNTIME, // > использовать только кеш памяти на время текущего скрипта
    'reflectorCacheMode'    => ReflectorCache::CACHE_MODE_STORAGE, // > использовать файловую систему или адаптер (хранилище)
    //
    'reflectorCacheDirpath' => $cacheDirpath,
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
//     'reflectorCacheMode'    => ReflectorCache::CACHE_MODE_STORAGE,
//     //
//     'reflectorCacheAdapter' => $symfonyCacheAdapter,
// ]);

// >>> Так можно очистить кеш принудительно (обычно для этого делают консольный скрипт и запускают вручную или кроном, но если использовать symfony/cache можно и просто установить TTL - время устаревания)
print_r('Clearing cache...' . PHP_EOL);
$di->clearCache();
print_r('Cleared.' . PHP_EOL);
print_r(PHP_EOL);


// >>> SERVICE REGISTRATION

// >>> Можно зарегистрировать класс в контейнере (смысл только в том, что метод get() не будет выбрасывать исключение)
// $di->bind(MyClassOneOne::class);
// >>> Можно привязать на интерфейс (а значит объект сможет пройти проверки зависимостей на входе конструктора)
// $di->bind(MyClassOneInterface::class, MyClassOneOne::class, $isSingleton = false);
// >>> Можно сразу указать, что созданный экземпляр будет одиночкой (все запросы к нему вернут тот же объект)
// $di->bindSingleton(MyClassOneInterface::class, MyClassOneOne::class);

// >>> При создании класса будет использоваться фабричный метод
$fnNewMyClassOne = static function () use ($di) {
    $object = $di->make('\Gzhegow\Di\Demo\MyClassOneOne', [ 123 ]);

    return $object;
};
// >>> И его результат будет сохранен как одиночка, то есть при втором вызове get()/ask() вернется тот же экземпляр
$di->bindSingleton('\Gzhegow\Di\Demo\MyClassOneInterface', $fnNewMyClassOne);
// >>> А также зарегистрируем алиас на наш интерфейс по имени (это позволяет нам использовать сервис-локатор не создавая интерфейсы под каждую настройку зависимости)
// > Если мы задаем конфигурацию в виде строк, а не в виде MyClass::class, мы избегаем подгрузки классов через autoloader (откладывая её до того момента, как мы запросим зависимость), а значит ускоряем запуск программы
$di->bind('one', '\Gzhegow\Di\Demo\MyClassOneInterface');

// >>> Мы знаем, что сервис MyClassTwo долго выполняет __construct(), например, соединяется по сети, и нам нужно отложить его запуск до первого вызова. Регистриуем как обычно, а дальше запросим через $di->getLazy()
$di->bindSingleton('\Gzhegow\Di\Demo\MyClassTwoInterface', '\Gzhegow\Di\Demo\MyClassTwo');
$di->bind('two', '\Gzhegow\Di\Demo\MyClassTwoInterface');

// >>> Зарегистрируем класс как синглтон (первый вызов создаст объект, второй - вернет созданный)
$di->bindSingleton('\Gzhegow\Di\Demo\MyClassThree');
$di->bind('three', '\Gzhegow\Di\Demo\MyClassThree');

// >>> MyClassThree требует сервисов One и Two, а чтобы не фиксировать сигнатуру конструктора, мы добавим их с помощью Интерфейсов и Трейтов
// > Некоторые контейнеры зависимостей в интернетах позволяют это делать не только по интерфейсам, но и по тегам, как например в symfony. Теги штука удобная, однако по сути своей теги это интерфейсы
$di->extend(MyClassOneAwareInterface::class, static function (MyClassOneAwareInterface $aware) use ($di) {
    $one = $di->get('one');

    $aware->setOne($one);
});
$di->extend(MyClassTwoAwareInterface::class, static function (MyClassTwoAwareInterface $aware) use ($di) {
    $two = $di->getLazy('two');

    $aware->setTwo($two);
});


// >>> RUNTIME

// >>> Пример. "Дай сервис c заполненными зависимостями"
print_r('Case1:' . PHP_EOL);
//
// > Используя параметр $contractT можно задавать имя класса, который поймет PHPStorm как генерик и будет давать подсказки
// $object = $di->get(MyInterface::class, $contractT = MyClass::class, $forceInstanceOf = false, $parametersWhenNew = []); // > get() бросит исключение, если не зарегистрировано в контейнере
// $object = $di->ask(MyInterface::class, $contractT = MyClass::class, $forceInstanceOf = false, $parametersWhenNew = []); // > использует get() если зарегистрировано, NULL если не зарегистрировано
// $object = $di->make(MyInterface::class, $parameters = [], $contractT = MyClass::class); // > всегда новый экземпляр с параметрами
// $object = $di->take(MyInterface::class, $parametersWhenNew = [], $contractT = MyClass::class); // > get() если зарегистрировано, make() если не зарегистрировано
//
$three = $di->get('three');
//
var_dump(get_class($three));                          // string(28) "Gzhegow\Di\Demo\MyClassThree"
Lib::assert_true(get_class($three) === 'Gzhegow\Di\Demo\MyClassThree');
//
// >>> Если класс помечен как сиглтон, запросы его вернут один и тот же экземпляр
$three1 = $di->get('\Gzhegow\Di\Demo\MyClassThree');
$three2 = $di->get('\Gzhegow\Di\Demo\MyClassThree');
$threeByAlias = $di->get('three');
Lib::assert_true($three1 === $three2);
Lib::assert_true($three1 === $threeByAlias);
print_r(PHP_EOL);


// >>> Ранее мы говорили, что этот сервис слишком долго выполняет конструктор. Запросим его как ленивый. При этом подстановка в аргументы конструктора конечно будет невозможна, но как сервис-локатор - удобная вещь!
// >>> В PHP к сожалению нет возможности создать анонимный класс, который расширяет ("extend") имя класса, который лежит в переменной. Поэтому, к сожалению, только такие LazyService...
print_r('Case2:' . PHP_EOL);
//
// $object = $di->getLazy(MyInterface::class, $contractT = MyClass::class, $parametersWhenNew = []);
// $object = $di->makeLazy(MyInterface::class, $parameters = [], $contractT = MyClass::class);
// $object = $di->takeLazy(MyInterface::class, $parametersWhenNew = [], $contractT = MyClass::class);
//
$two1 = $di->getLazy('two', $contractT = null, [ 'hello' => 'User1' ]);
$two2 = $di->makeLazy('two', [ 'hello' => 'User2' ]);                      // > make создаст новый объект, но не перепишет имеющийся синглтон
$two1Again = $di->getLazy('two');                                          // > то есть тут мы получим $twoWithUser, а не $twoWithUser2
//
var_dump(get_class($two1));                                                // string(34) "Gzhegow\Di\LazyService\LazyService"
var_dump(get_class($two2));                                                // string(34) "Gzhegow\Di\LazyService\LazyService"
var_dump(get_class($two1Again));                                           // string(34) "Gzhegow\Di\LazyService\LazyService"
Lib::assert_true(get_class($two1) === 'Gzhegow\Di\LazyService\LazyService');
Lib::assert_true(get_class($two2) === 'Gzhegow\Di\LazyService\LazyService');
Lib::assert_true(get_class($two1Again) === 'Gzhegow\Di\LazyService\LazyService');
//
// >>> При вызове первого метода объект внутри LazyService будет создан с аргументами, что указали в __configure() или без них (только зависимости), если не указали
echo 'MyClassTwo загружается (3 секунды)...' . PHP_EOL;                    // MyClassB загружается (3 секунды)...
$two1->do();                                                               // Hello, [ User1 ] !
$two1Again->do();                                                          // Hello, [ User1 ] !
Lib::assert_true($two1 !== $two1Again);
Lib::assert_true($two1->instance === $two1Again->instance);
echo 'MyClassTwo загружается (3 секунды)...' . PHP_EOL;  // MyClassB загружается (3 секунды)...
$two2->do();                                             // Hello, [ User2 ] !
Lib::assert_true($two1 !== $two2);
Lib::assert_true($two1->instance !== $two2->instance);
print_r(PHP_EOL);


// >>> Еще пример. "Дозаполним аргументы уже существующего объекта, который мы не регистрировали в контейнере"
print_r('Case3:' . PHP_EOL);
$four = new MyClassFour();
//
// $di->autowire($four, $customArgs = [], $customMethod = '__myCustomAutowire');
//
$di->autowire($four);
//
var_dump(get_class($four));                              // string(27) "Gzhegow\Di\Demo\MyClassFour"
var_dump(get_class($four->one));                         // string(29) "Gzhegow\Di\Demo\MyClassOneOne"
Lib::assert_true(get_class($four) === 'Gzhegow\Di\Demo\MyClassFour');
Lib::assert_true(get_class($four->one) === 'Gzhegow\Di\Demo\MyClassOneOne');
//
$four2 = new MyClassFour();
$di->autowire($four2);
Lib::assert_true($four->one === $four2->one);              // > зависимость по интерфейсу, зарегистрированная как одиночка, будет равна в двух разных экземплярах
print_r(PHP_EOL);


// >>> Еще пример. "Дозаполним аргументы уже существующего объекта, который мы не регистрировали, и который имеет зависимости, которые мы тоже не регистрировали"
print_r('Case4:' . PHP_EOL);
$five = new MyClassFive();
$di->autowire($five);
//
var_dump(get_class($five));                                // string(27) "Gzhegow\Di\Demo\MyClassFive"
var_dump(get_class($five->four));                          // string(27) "Gzhegow\Di\Demo\MyClassFour"
Lib::assert_true(get_class($five) === 'Gzhegow\Di\Demo\MyClassFive');
Lib::assert_true(get_class($five->four) === 'Gzhegow\Di\Demo\MyClassFour');
//
$five2 = new MyClassFive();
$di->autowire($five2);
Lib::assert_true($five->four !== $five2->four);
print_r(PHP_EOL);


// >>> Еще пример. "Вызовем функцию, подбросив в неё зависимости"
print_r('Case5:' . PHP_EOL);
$fn = static function (
    $arg1,
    MyClassThree $three,
    $arg2
) {
    Lib::assert_true($arg1 === 1);
    Lib::assert_true($arg2 === 2);

    return get_class($three);
};
$args = [
    'arg1' => 1,
    'arg2' => 2,
];
$result = $di->callUserFuncArray($fn, $args);
//
// > можно и так, но поскольку аргументы передаются по порядку - придется указать NULL для тех, что мы хотим распознать, так что всегда разумнее применять call_user_func_array
// $args = [ 1, null, 2 ];
// $result = $di->callUserFunc($fn, ...$args);
//
var_dump($result);                                         // string(28) "Gzhegow\Di\Demo\MyClassThree"
Lib::assert_true($result === 'Gzhegow\Di\Demo\MyClassThree');
print_r(PHP_EOL);


// >>> Теперь сохраним кеш сделанной за скрипт рефлексии для следующего раза (в примере мы чистим кеш в начале скрипта, то есть это смысла не имеет, но на проде кеш вычищают вручную или не трогают вовсе)
print_r('Saving cache...' . PHP_EOL);
$di->flushCache();
print_r('Saved.');
