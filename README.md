# Dependency Injection / IoC Container

Контейнер внедрения зависимостей с поддержкой кеша, ленивых сервисов и фабрик.

Поддерживает файловый кеш для рефлексии, однако сохраняет в рефлексию в рантайме.
Если вы хотите сохранить её в хранилище - вызовите метод `$di->saveCache();` в конце работы скрипта.
Функция разогрева не имеет смысла, потому что это требует "создать все возможные обьекты во всех возможных комбинациях".

## Установка

```
composer require gzhegow/di
```

## Запустить тесты

```
php test.php
```

## Примеры и тесты

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');


// > настраиваем обработку ошибок
(new \Gzhegow\Lib\Exception\ErrorHandler())
    ->useErrorReporting()
    ->useErrorHandler()
    ->useExceptionHandler()
;


// > добавляем несколько функция для тестирования
$ffn = new class {
    function root() : string
    {
        return realpath(__DIR__ . '/..');
    }


    function values($separator = null, ...$values) : string
    {
        return \Gzhegow\Lib\Lib::debug()->values([], $separator, ...$values);
    }


    function print(...$values) : void
    {
        echo $this->values(' | ', ...$values) . PHP_EOL;
    }


    function assert_stdout(
        \Closure $fn, array $fnArgs = [],
        string $expectedStdout = null
    ) : void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        \Gzhegow\Lib\Lib::test()->assertStdout(
            $trace,
            $fn, $fnArgs,
            $expectedStdout
        );
    }

    function assert(
        \Closure $fn, array $fnArgs = [],
        string $expectedStdout = null,
        float $expectedMicrotimeMax = null, float $expectedMicrotimeMin = null
    ) : void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        \Gzhegow\Lib\Lib::test()->assert(
            $trace,
            $fn, $fnArgs,
            $expectedStdout,
            $expectedMicrotimeMax, $expectedMicrotimeMin
        );
    }
};


// >>> ЗАПУСКАЕМ!

// > сначала всегда фабрика
$factory = new \Gzhegow\Di\DiFactory();

// > создаем конфигурацию
$config = new \Gzhegow\Di\DiConfig();
$config->configure(function (\Gzhegow\Di\DiConfig $config) use ($ffn) {
    // > задаем функцию выборки по-умолчанию - GET - бросает исключение, если объекта нет, TAKE - пытается создать
    $config->injector->fetchFunc = \Gzhegow\Di\Injector\DiInjector::FETCH_FUNC_GET;

    // > настраиваем кэширование рефлексии
    $config->reflectorCache->cacheMode = \Gzhegow\Di\Reflector\DiReflectorCache::CACHE_MODE_STORAGE;
    //
    $cacheDir = $ffn->root() . '/var/cache';
    $cacheNamespace = 'gzhegow.di';
    $cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
    $config->reflectorCache->cacheDirpath = $cacheDirpath;
    //
    // > можно симфонийский кэш использовать
    // $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
    //     $cacheNamespace, $defaultLifetime = 0, $cacheDir
    // );
    // $redisClient = \Symfony\Component\Cache\Adapter\RedisAdapter::createConnection('redis://localhost');
    // $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\RedisAdapter(
    //     $redisClient,
    //     $cacheNamespace = '',
    //     $defaultLifetime = 0
    // );
    // $config->reflectorCache->cacheMode = \Gzhegow\Di\Reflector\ReflectorCache::CACHE_MODE_STORAGE;
    // $config->reflectorCache->cacheAdapter = $symfonyCacheAdapter;
});

// > создаем кэш рефлектора - кэш наполняется и сохраняется автоматически по мере наполнения контейнера
$reflectorCache = new \Gzhegow\Di\Reflector\DiReflectorCache(
    $config->reflectorCache
);

// > создаем рефлектор
$reflector = new \Gzhegow\Di\Reflector\DiReflector(
    $reflectorCache
);

// > создаем инжектор
$injector = new \Gzhegow\Di\Injector\DiInjector(
    $reflector,
    $config->injector
);

// > создаем контейнер
$di = new \Gzhegow\Di\Di(
    $factory,
    //
    $injector,
    $reflector,
    //
    $config
);

// > сохраняем DI статически
\Gzhegow\Di\Di::setInstance($di);

// > можно обернуть в контейнер Psr, для стандартизации (не обязательно)
// $container = new \Gzhegow\Di\Container\ContainerPsr($di);      // composer require psr/container
// $container = new \Gzhegow\Di\Container\ContainerPsr10000($di); // composer require psr/container:~1.0

// > так можно очистить кеш принудительно (обычно для этого делают консольный скрипт и запускают вручную или кроном, но если использовать symfony/cache можно и просто установить TTL - время устаревания)
$di->clearCache();
// > так можно сбросить кэш принудительно (чтобы запросить его из хранилища заново в режиме STORAGE)
// $di->resetCache();
// > так можно сохранить кеш (обычно в конце скрипта)
// $di->saveCache();


// >>> Регистрируем сервисы

// > Можно зарегистрировать класс в контейнере (смысл только в том, что метод get() не будет выбрасывать исключение)
// $di->bind(MyClassOneOne::class);
// > Можно привязать на интерфейс (а значит объект сможет пройти проверки зависимостей на входе конструктора)
// $di->bind(MyClassOneInterface::class, MyClassOneOne::class, $isSingleton = false);
// > Можно сразу указать, что созданный экземпляр будет одиночкой (все запросы к нему вернут тот же объект)
// $di->bindSingleton(MyClassOneInterface::class, MyClassOneOne::class);

// > объявим фабричный метод - при создании класса будет использоваться именно он
$fnNewMyClassOne = static function () use ($di) {
    $object = $di->make('\Gzhegow\Di\Demo\MyClassOneOne', [ 123 ]);

    return $object;
};
// > теперь сохраним его результат как одиночку ("singleton"), то есть при втором вызове get()/ask() вернется тот же экземпляр
$di->bindSingleton('\Gzhegow\Di\Demo\MyClassOneInterface', $fnNewMyClassOne);

// > а также зарегистрируем алиас на наш интерфейс по имени - можно будет применять паттерн "сервис-локатор", вместо того чтобы создавать файлы под интерфейс и наследник имеющегося класса
// > в добавок, если мы задаем алиас в виде строки (с именем класса '\MyClass'), а не в виде php-константы `\MyClass::class`,
// > то мы избегаем подгрузки классов через autoloader (откладывая её до того момента, как мы запросим зависимость), а значит ускоряем запуск программы
$di->bind('one', '\Gzhegow\Di\Demo\MyClassOneInterface');

// > известно, например, что сервис MyClassTwo долго выполняет __construct(), пусть, соединяется по сети
// > регистриуем как обычно, а дальше - запросим через $di->getLazy()
$di->bindSingleton('\Gzhegow\Di\Demo\MyClassTwoInterface', '\Gzhegow\Di\Demo\MyClassTwo');
$di->bind('two', '\Gzhegow\Di\Demo\MyClassTwoInterface');

// > зарегистрируем класс как синглтон (первый вызов создаст объект, второй - вернет созданный)
$di->bindSingleton('\Gzhegow\Di\Demo\MyClassThree');
$di->bind('three', '\Gzhegow\Di\Demo\MyClassThree');

// > MyClassThree требует сервисов One и Two, а чтобы не фиксировать сигнатуру конструктора, мы добавим их с помощью Интерфейсов и Трейтов
// > некоторые контейнеры зависимостей в интернетах позволяют это делать не только по интерфейсам, но и по тегам, как например в symfony. Теги штука удобная, однако по сути своей теги это интерфейсы
$di->extend(\Gzhegow\Di\Demo\MyClassOneAwareInterface::class, static function (\Gzhegow\Di\Demo\MyClassOneAwareInterface $aware) use ($di) {
    $one = $di->get('one');

    $aware->setOne($one);
});
$di->extend(\Gzhegow\Di\Demo\MyClassTwoAwareInterface::class, static function (\Gzhegow\Di\Demo\MyClassTwoAwareInterface $aware) use ($di) {
    $two = $di->getLazy('two');

    $aware->setTwo($two);
});


// > TEST
// > получить сервис (с автоматическим заполнением зависимостей, "автовайрингом")
// > если в настройках установлено $config->injector->fetchFunc = 'GET', то при попытке заполнения необъявленных зависимостей будет выброшено исключение
// > иначе, если $config->injector->fetchFunc = 'TAKE', то инжектор попытается создать новый экземпляр, если в качестве `id` передано имя класса
$fn = function () use ($di, $ffn) {
    $ffn->print('TEST 1');
    echo PHP_EOL;

    // > Используя параметр $contractT можно задавать имя класса, который поймет PHPStorm как генерик и будет давать подсказки

    // > метод ask() возвращает экземпляр или NULL
    // $object = $di->ask(MyInterface::class, $contractT = MyClass::class, $forceInstanceOf = false, $parametersWhenNew = []); // > использует get() если зарегистрировано, NULL если не зарегистрировано

    // > get() бросит исключение, если не зарегистрировано в контейнере
    // $object = $di->get(MyInterface::class, $contractT = MyClass::class, $forceInstanceOf = false, $parametersWhenNew = []);

    // > make() вернет всегда новый экземпляр с параметрами
    // $object = $di->make(MyInterface::class, $parameters = [], $contractT = MyClass::class);

    // > take() выполнит get() если зарегистрирован, или make() если нет
    // $object = $di->take(MyInterface::class, $parametersWhenNew = [], $contractT = MyClass::class); // > get() если зарегистрировано, make() если не зарегистрировано

    // > fetch() выполнит то, что указано в $config->injector->fetchFunc = 'GET'/'TAKE'
    // $object = $di->fetch(MyInterface::class, $parametersWhenNew = [], $contractT = MyClass::class); // > get() если зарегистрировано, make() если не зарегистрировано

    $result = $di->get('three');
    $ffn->print($result);

    echo PHP_EOL;

    // > Если класс помечен как сиглтон, запросы его вернут один и тот же экземпляр
    $result1 = $di->get('\Gzhegow\Di\Demo\MyClassThree');
    $result2 = $di->get('\Gzhegow\Di\Demo\MyClassThree');
    $result3 = $di->get('three');
    $ffn->print($result1, $result2, $result3);
    $ffn->print($result1 === $result2);
    $ffn->print($result2 === $result3);
};
$ffn->assert_stdout($fn, [], '
"TEST 1"

{ object # Gzhegow\Di\Demo\MyClassThree }

{ object # Gzhegow\Di\Demo\MyClassThree } | { object # Gzhegow\Di\Demo\MyClassThree } | { object # Gzhegow\Di\Demo\MyClassThree }
TRUE
TRUE
');


// > TEST
// > дозаполнить аргументы уже существующего объекта, который мы не регистрировали в контейнере
$fn = function () use ($di, $ffn) {
    $ffn->print('TEST 2');
    echo PHP_EOL;

    // $di->autowireInstance($four, $customArgs = [], $customMethod = '__myCustomAutowire');

    $four1 = new \Gzhegow\Di\Demo\MyClassFour();
    $di->autowireInstance($four1);
    $ffn->print($four1);
    $ffn->print($four1->one);

    echo PHP_EOL;

    // > зависимость по интерфейсу, зарегистрированная как одиночка, будет равна в двух разных экземплярах
    $four2 = new \Gzhegow\Di\Demo\MyClassFour();
    $di->autowireInstance($four2);
    $ffn->print($four2);
    $ffn->print($four2->one);
    $ffn->print($four1->one === $four2->one);
};
$ffn->assert_stdout($fn, [], '
"TEST 2"

{ object # Gzhegow\Di\Demo\MyClassFour }
{ object # Gzhegow\Di\Demo\MyClassOneOne }

{ object # Gzhegow\Di\Demo\MyClassFour }
{ object # Gzhegow\Di\Demo\MyClassOneOne }
TRUE
');


// > TEST
// > дозаполнить аргументы уже существующего объекта, который мы не регистрировали в контейнере, имеющий зависимости, которые тоже не были зарегистрированы
$fn = function () use ($di, $config, $ffn) {
    $ffn->print('TEST 3');
    echo PHP_EOL;

    // > попытка заполнить зависимости, которые не зарегистрированы в контейнере с `fetchFunc = GET' приведет к ошибке
    try {
        $five1 = new \Gzhegow\Di\Demo\MyClassFive();
        $di->autowireInstance($five1);
    }
    catch ( \Gzhegow\Di\Exception\Runtime\NotFoundException $e ) {
        $ffn->print('[ CATCH ] ' . $e->getMessage());
    }
    echo PHP_EOL;

    // > переключаем режим (на продакшене лучше включить его в начале приложения и динамически не менять)
    $config->configure(function (\Gzhegow\Di\DiConfig $config) {
        $config->injector->fetchFunc = \Gzhegow\Di\Injector\DiInjector::FETCH_FUNC_TAKE;
    });

    $five1 = new \Gzhegow\Di\Demo\MyClassFive();
    $di->autowireInstance($five1);
    $ffn->print($five1);
    $ffn->print($five1->four);
    echo PHP_EOL;

    $five2 = new \Gzhegow\Di\Demo\MyClassFive();
    $di->autowireInstance($five2);
    $ffn->print($five2);
    $ffn->print($five2->four);
    echo PHP_EOL;

    $ffn->print($five1->four !== $five2->four);

    $config->configure(function (\Gzhegow\Di\DiConfig $config) {
        $config->injector->fetchFunc = \Gzhegow\Di\Injector\DiInjector::FETCH_FUNC_GET;
    });
};
$ffn->assert_stdout($fn, [], '
"TEST 3"

"[ CATCH ] Missing bound `argReflectionTypeClass` to resolve parameter: [ 0 ] $four : Gzhegow\Di\Demo\MyClassFour"

{ object # Gzhegow\Di\Demo\MyClassFive }
{ object # Gzhegow\Di\Demo\MyClassFour }

{ object # Gzhegow\Di\Demo\MyClassFive }
{ object # Gzhegow\Di\Demo\MyClassFour }

TRUE
');


// > TEST
// > вызовем произвольную функцию и заполним её аргументы
$fn = function () use ($di, $ffn) {
    $ffn->print('TEST 4');
    echo PHP_EOL;

    $fn = static function (
        $arg1,
        \Gzhegow\Di\Demo\MyClassThree $three,
        $arg2
    ) use ($ffn) {
        $ffn->print($arg1);
        $ffn->print($arg2);

        return get_class($three);
    };

    $args = [
        'arg1' => 1,
        'arg2' => 2,
    ];

    $result = $di->callUserFuncArrayAutowired($fn, $args);
    $ffn->print($result);

    // > можно и так, но поскольку аргументы передаются по порядку - придется указать NULL для тех, что мы хотим распознать
    // $args = [ 1, null, 2 ];
    // $result = $di->callUserFuncAutowired($fn, ...$args);
};
$ffn->assert_stdout($fn, [], '
"TEST 4"

1
2
"Gzhegow\Di\Demo\MyClassThree"
');


// > TEST
// > некоторые сервисы слишком долго выполняют конструктор (например, подключаются к внешней апи)
// > запросим его как ленивый (правда, при этом подстановка в аргументы конструктора будет невозможна)
// > В PHP, к сожалению, нет возможности создать анонимный класс, который расширяет ("extend") имя класса из строковой переменной
// > поэтому, приходится использовать только такие LazyService
$lazy1 = null;
$lazy2 = null;
$lazy3 = null;
$fn = function () use (
    $di, $ffn,
    //
    &$lazy1,
    &$lazy2,
    &$lazy3
) {
    $ffn->print('TEST 5');
    echo PHP_EOL;

    // $object = $di->getLazy(MyInterface::class, $contractT = MyClass::class, $parametersWhenNew = []);
    // $object = $di->makeLazy(MyInterface::class, $parameters = [], $contractT = MyClass::class);
    // $object = $di->takeLazy(MyInterface::class, $parametersWhenNew = [], $contractT = MyClass::class);
    // $object = $di->fetchLazy(MyInterface::class, $parametersWhenNew = [], $contractT = MyClass::class);

    // > get получил бы имеющийся объект, но его нет, так что создаст новый и сохранит как синглтон
    $lazy1 = $di->getLazy('two', $contractT = null, [ 'hello' => 'User1' ]);
    $ffn->print($lazy1, (string) $lazy1->id);
    echo PHP_EOL;

    // > make создаст новый объект с переданными параметрами, но не перепишет имеющийся синглтон
    $lazy2 = $di->makeLazy('two', [ 'hello' => 'User2' ]);
    $ffn->print($lazy2, (string) $lazy2->id);
    echo PHP_EOL;

    // > повторный запрос get возвратит синглтон, записанный в первом вызове
    $lazy3 = $di->getLazy('two');
    $ffn->print($lazy3, (string) $lazy3->id);
};
$ffn->assert_stdout($fn, [], '
"TEST 5"

{ object # Gzhegow\Di\LazyService\DiLazyService } | "two"

{ object # Gzhegow\Di\LazyService\DiLazyService } | "two"

{ object # Gzhegow\Di\LazyService\DiLazyService } | "two"
');


// > TEST
// > вызовем действия на ленивом сервисе
$fn = function () use (
    $di,
    //
    &$lazy1,
    &$lazy2,
    &$lazy3
) {
    // > это вызовет конструктор ленивого сервиса и займет 3 секунды...
    $lazy1->do();

    // > это вызовет конструктор ленивого сервиса и займет 3 секунды...
    $lazy2->do();

    // > в этом случае время не потребуется, поскольку объект был создан ранее
    $lazy3->do();
};
$ffn->assert($fn, [], '
Hello, [ User1 ]
Hello, [ User2 ]
Hello, [ User1 ]
', 7.0, 6.0);


// > Теперь сохраним кеш сделанной за скрипт рефлексии для следующего раза
// > В примере мы чистим кеш в начале скрипта, то есть это смысла не имеет
// > На проде же кеш вычищают вручную или не трогают вовсе
$di->saveCache();
```

