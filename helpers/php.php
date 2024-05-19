<?php

namespace Gzhegow\Di;


use Psr\Cache\InvalidArgumentException;
use Gzhegow\Di\Exception\LogicException;


/**
 * > gzhegow, выводит тип переменной в виде строки
 */
function _php_type($value) : string
{
    $_value = null
        ?? (($value === null) ? '{ NULL }' : null)
        ?? (($value === false) ? '{ FALSE }' : null)
        ?? (($value === true) ? '{ TRUE }' : null)
        ?? (is_object($value) ? ('{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }') : null)
        ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
        ?? '{ ' . gettype($value) . ' }';

    return $_value;
}

/**
 * > gzhegow, выводит короткую и наглядную форму содержимого переменной в виде строки
 */
function _php_dump($value, int $maxlen = null) : string
{
    if (is_string($value)) {
        $_value = ''
            . '{ '
            . 'string(' . strlen($value) . ')'
            . ' "'
            . ($maxlen
                ? (substr($value, 0, $maxlen) . '...')
                : $value
            )
            . '"'
            . ' }';

    } elseif (! is_iterable($value)) {
        $_value = null
            ?? (($value === null) ? '{ NULL }' : null)
            ?? (($value === false) ? '{ FALSE }' : null)
            ?? (($value === true) ? '{ TRUE }' : null)
            ?? (is_object($value) ? ('{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }') : null)
            ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
            //
            ?? (is_int($value) ? (var_export($value, 1)) : null) // INF
            ?? (is_float($value) ? (var_export($value, 1)) : null) // NAN
            //
            ?? null;

    } else {
        foreach ( $value as $k => $v ) {
            $value[ $k ] = null
                ?? (is_array($v) ? '{ array(' . count($v) . ') }' : null)
                ?? (is_iterable($v) ? '{ iterable(' . get_class($value) . ' # ' . spl_object_id($value) . ') }' : null)
                // > ! recursion
                ?? _php_dump($v, $maxlen);
        }

        $_value = var_export($value, true);

        $_value = str_replace("\n", ' ', $_value);
        $_value = preg_replace('/\s+/', ' ', $_value);
    }

    if (null === $_value) {
        throw _php_throw(
            'Unable to dump variable'
        );
    }

    return $_value;
}

/**
 * > gzhegow, перебрасывает исключение на "тихое", если из библиотеки внутреннее постоянно подсвечивается в PHPStorm
 *
 * @return \LogicException|null
 */
function _php_throw($error, ...$errors) : ?object
{
    if (is_a($error, \Closure::class)) {
        $error = $error(...$errors);
    }

    if (
        is_a($error, \LogicException::class)
        || is_a($error, \RuntimeException::class)
    ) {
        return $error;
    }

    $throwErrors = _php_throw_errors($error, ...$errors);

    $message = $throwErrors[ 'message' ] ?? __FUNCTION__;
    $code = $throwErrors[ 'code' ] ?? -1;
    $previous = $throwErrors[ 'previous' ] ?? null;

    return $previous
        ? new \RuntimeException($message, $code, $previous)
        : new \LogicException($message, $code);
}

/**
 * > gzhegow, парсит ошибки для передачи результата в конструктор исключения
 *
 * @return array{
 *     message: string,
 *     code: int,
 *     previous: string,
 *     messageCode: string,
 *     messageData: array,
 *     messageObject: object,
 * }
 */
function _php_throw_errors($error, ...$errors) : array
{
    $_message = null;
    $_code = null;
    $_previous = null;
    $_messageCode = null;
    $_messageData = null;
    $_messageObject = null;

    array_unshift($errors, $error);

    foreach ( $errors as $error ) {
        if (is_int($error)) {
            $_code = $error;

            continue;
        }

        if (is_a($error, \Throwable::class)) {
            $_previous = $error;

            continue;
        }

        if (null !== ($_string = _filter_string($error))) {
            $_message = $_string;
            $_messageCode = _filter_word($_message);

            continue;
        }

        if (
            is_array($error)
            || is_a($error, \stdClass::class)
        ) {
            $_messageData = (array) $error;

            if (isset($_messageData[ 0 ])) {
                $_message = _filter_string($_messageData[ 0 ]);
                $_messageCode = _filter_word($_message);
            }
        }
    }

    $_message = $_message ?? null;
    $_code = $_code ?? null;
    $_previous = $_previous ?? null;

    $_messageCode = $_messageCode ?? null;

    $_messageObject = null
        ?? (isset($_messageData) ? (object) $_messageData : null)
        ?? (isset($_message) ? (object) [ $_message ] : null);

    if (isset($_messageData)) {
        array_shift($_messageData);

        $_messageData = $_messageData ?: null;
    }

    $result = [];
    $result[ 'message' ] = $_message;
    $result[ 'code' ] = $_code;
    $result[ 'previous' ] = $_previous;
    $result[ 'messageCode' ] = $_messageCode;
    $result[ 'messageData' ] = $_messageData;
    $result[ 'messageObject' ] = $_messageObject;

    return $result;
}


/**
 * @param callable|string $function
 */
function _php_function_exists($function) : ?string
{
    if (! is_string($function)) return null;

    if (function_exists($function)) {
        return $function;
    }

    return null;
}

/**
 * @param callable|array|object|class-string     $mixed
 *
 * @param array{0: class-string, 1: string}|null $resultArray
 * @param callable|string|null                   $resultString
 *
 * @return array{0: class-string|object, 1: string}|null
 */
function _php_method_exists(
    $mixed, $method = null,
    array &$resultArray = null, string &$resultString = null
) : ?array
{
    $resultArray = null;
    $resultString = null;

    $method = $method ?? '';

    $_class = null;
    $_object = null;
    $_method = null;
    if (is_object($mixed)) {
        $_object = $mixed;

    } elseif (is_array($mixed)) {
        $list = array_values($mixed);

        /** @noinspection PhpWrongStringConcatenationInspection */
        [ $classOrObject, $_method ] = $list + [ '', '' ];

        is_object($classOrObject)
            ? ($_object = $classOrObject)
            : ($_class = $classOrObject);

    } elseif (is_string($mixed)) {
        [ $_class, $_method ] = explode('::', $mixed) + [ '', '' ];

        $_method = $_method ?? $method;
    }

    if (isset($_method) && ! is_string($_method)) {
        return null;
    }

    if ($_object) {
        if ($_object instanceof \Closure) {
            return null;
        }

        if (method_exists($_object, $_method)) {
            $class = get_class($_object);

            $resultArray = [ $class, $_method ];
            $resultString = $class . '::' . $_method;

            return [ $_object, $_method ];
        }

    } elseif ($_class) {
        if (method_exists($_class, $_method)) {
            $resultArray = [ $_class, $_method ];
            $resultString = $_class . '::' . $_method;

            return [ $_class, $_method ];
        }
    }

    return null;
}


define('REFLECT_CACHE_MODE_RUNTIME_CACHE', -1);
define('REFLECT_CACHE_MODE_NO_CACHE', 0);
define('REFLECT_CACHE_MODE_STORAGE_CACHE', 1);
function _php_reflect_cache_settings(array $settings = []) : array
{
    static $current;

    $current = $current
        ?? [
            'mode'     => null,
            'filepath' => null,
            'adapter'  => null,
        ];


    $cacheMode = $settings[ 'mode' ]
        ?? $current[ 'mode' ]
        ?? -1 // > use runtime cache
        // ?? 0 // > use no cache
        // ?? 1 // > use defined cache
    ;

    $cacheMode = (int) $cacheMode;

    if (! in_array($cacheMode, $in = [
        REFLECT_CACHE_MODE_RUNTIME_CACHE,
        REFLECT_CACHE_MODE_NO_CACHE,
        REFLECT_CACHE_MODE_STORAGE_CACHE,
    ])) {
        throw _php_throw(
            'The `mode` should be one of: ' . implode(';', $in)
            . ' / ' . _php_dump($cacheMode)
        );
    }

    $current[ 'mode' ] = $cacheMode;


    $cacheFilepath = $settings[ 'filepath' ]
        ?? $current[ 'filepath' ]
        ?? null;

    $cacheFilepath = strval($cacheFilepath) ?: null;


    $cacheAdapter = $settings[ 'adapter' ]
        ?? $current[ 'adapter' ]
        ?? null;

    if ($cacheAdapter) {
        if (! is_a($cacheAdapter, $class = '\Psr\Cache\CacheItemPoolInterface')) {
            throw _php_throw(
                'Setting `cache` should be instance of: ' . $class
                . ' / ' . _php_dump($current[ 'adapter' ])
            );
        }
    }


    $cacheAdapter
        ? ($current[ 'adapter' ] = $cacheAdapter)
        : ($current[ 'filepath' ] = $cacheFilepath ?? (__DIR__ . '/../var/cache/php.reflect_cache/latest.cache'));


    return $current;
}

function _php_reflect_cache(object $cacheNew = null) : object
{
    static $cache;
    static $cacheItem;

    $cache = $cacheNew ?? $cache ?? null;
    $cacheItem = $cacheItem ?? null;

    if (isset($cacheNew)
        && ! ($cacheNew instanceof \stdClass)
    ) {
        throw _php_throw(
            'The `cacheNew` should be instance of: ' . \stdClass::class
            . ' / ' . _php_dump($cacheNew)
        );
    }

    [
        'mode'     => $cacheMode,
        'filepath' => $cacheFilepath,
        'adapter'  => $cacheAdapter,
    ] = $cacheSettings = _php_reflect_cache_settings();

    if (! isset($cache)) {
        if ($cacheMode === REFLECT_CACHE_MODE_NO_CACHE) {
            $cache = (object) [];

        } elseif ($cacheMode === REFLECT_CACHE_MODE_STORAGE_CACHE) {
            if ($cacheAdapter) {
                /** @var \Psr\Cache\CacheItemPoolInterface $cacheAdapter */

                if (! isset($cacheItem)) {
                    try {
                        $cacheItem = $cacheAdapter->getItem('php.reflect_cache');
                    }
                    catch ( InvalidArgumentException $e ) {
                        throw _php_throw($e);
                    }
                }

                if ($cacheItem->isHit()) {
                    $cache = $cacheItem->get();
                }

            } else {
                $cacheSerialized = null;

                if (is_file($cacheFilepath)) {
                    $cacheSerialized = file_get_contents($cacheFilepath);
                }

                if (is_string($cacheSerialized)) {
                    $cache = unserialize($cacheSerialized);
                }
            }

            $cache = $cache ?? (object) [];
        }
    }

    if (isset($cacheNew)) {
        if ($cacheMode === REFLECT_CACHE_MODE_STORAGE_CACHE) {
            if ($cacheAdapter) {
                /** @var \Psr\Cache\CacheItemPoolInterface $cacheAdapter */

                if (! isset($cacheItem)) {
                    try {
                        $cacheItem = $cacheAdapter->getItem('php.reflect_cache');
                    }
                    catch ( InvalidArgumentException $e ) {
                        throw _php_throw($e);
                    }
                }

                $cacheItem->set($cacheNew);

                $cacheAdapter->save($cacheItem);

            } else {
                $cacheSerialized = serialize($cacheNew);

                file_put_contents($cacheFilepath, $cacheSerialized);
            }
        }
    }

    return $cache;
}

function _php_reflect($reflectable) : array
{
    $reflectCache = _php_reflect_cache();

    $reflectCacheKey = null;
    $reflectionFunctionAbstract = null;
    if (is_object($reflectable)) {
        $_reflectableObject = $reflectable;

        if ($reflectable instanceof \Closure) {
            $reflectCacheKey = _php_type($reflectable);

            if (! isset($reflectCache->{$reflectCacheKey})) {
                try {
                    $rf = new \ReflectionFunction($reflectable);
                }
                catch ( \ReflectionException $e ) {
                    throw _php_throw($e);
                }

                $reflectionFunctionAbstract = $rf;
            }

        } elseif (is_callable($reflectable)) {
            $reflectCacheKey = get_class($_reflectableObject) . '::__invoke';

            if (! isset($reflectCache->{$reflectCacheKey})) {
                try {
                    $rf = new \ReflectionMethod($_reflectableObject, '__invoke');
                }
                catch ( \ReflectionException $e ) {
                    throw _php_throw($e);
                }

                $reflectionFunctionAbstract = $rf;
            }

        } else {
            $reflectCacheKey = get_class($_reflectableObject) . '::__construct';

            if (! isset($reflectCache->{$reflectCacheKey})) {
                try {
                    $rc = new \ReflectionClass($_reflectableObject);
                }
                catch ( \ReflectionException $e ) {
                    throw _php_throw($e);
                }

                $rm = $rc->getConstructor();

                $reflectionFunctionAbstract = $rm;
            }
        }

    } elseif (is_array($reflectable)) {
        if (_php_method_exists(
            $reflectable, null,
            $resultArray, $resultString
        )) {
            $reflectCacheKey = $resultString;

            if (! isset($reflectCache->{$reflectCacheKey})) {
                try {
                    $rm = new \ReflectionMethod(...$resultArray);
                }
                catch ( \ReflectionException $e ) {
                    throw _php_throw($e);
                }

                $reflectionFunctionAbstract = $rm;
            }
        }

    } elseif (is_string($reflectable)) {
        if (_php_function_exists($reflectable)) {
            $reflectCacheKey = $reflectable;

            if (! isset($reflectCache->{$reflectCacheKey})) {
                try {
                    $rf = new \ReflectionFunction($reflectable);
                }
                catch ( \ReflectionException $e ) {
                    throw _php_throw($e);
                }

                $reflectionFunctionAbstract = $rf;
            }

        } elseif (class_exists($reflectable)) {
            $reflectCacheKey = $reflectable . '::__construct';

            if (! isset($reflectCache->{$reflectCacheKey})) {
                $rc = new \ReflectionClass($reflectable);
                $rm = $rc->getConstructor();

                $reflectionFunctionAbstract = $rm;
            }

        } elseif (_php_method_exists(
            $reflectable, '__invoke',
            $resultArray, $resultString
        )) {
            $reflectCacheKey = $resultString;

            if (! isset($reflectCache->{$reflectCacheKey})) {
                try {
                    $rm = new \ReflectionMethod(...$resultArray);
                }
                catch ( \ReflectionException $e ) {
                    throw _php_throw($e);
                }

                $reflectionFunctionAbstract = $rm;
            }
        }
    }

    if (! isset($reflectCacheKey)) {
        throw _php_throw(
            'The `reflectable` should be class name or function/method name: ' . _php_dump($reflectable)
        );
    }

    if (! isset($reflectCache->{$reflectCacheKey})) {
        $result = [
            'arguments' => [],
            'return'    => null,
        ];

        if ($reflectionFunctionAbstract) {
            $rfParams = $reflectionFunctionAbstract->getParameters();

            $arguments = [];
            foreach ( $rfParams as $i => $rp ) {
                [ $rtList, $rtTree, $rtIsNullable ] = _php_reflect_type($rp->getType());

                $rpName = $rp->getName();
                $rpIsNullable = $rtIsNullable || $rp->isOptional();

                $arguments[ $i ] = [ $rpName, $rtList, $rtTree, $rpIsNullable ];
            }

            $rfReturn = $reflectionFunctionAbstract->getReturnType();
            $return = _php_reflect_type($rfReturn);

            $result[ 'arguments' ] = $arguments;
            $result[ 'return' ] = $return;
        }

        $reflectCache->{$reflectCacheKey} = $result;
    }

    return $reflectCache->{$reflectCacheKey};
}

function _php_reflect_type(?\ReflectionType $rt)
{
    $list = [];
    $tree = [];
    $isNullable = false;

    if (! $rt) {
        $list[ 0 ] = [ null, null, true ];

        $tree[ '' ][ '' ] = 'and';
        $tree[ '' ][ 0 ] = true;

        $isNullable = true;

    } else {
        $stack = [];
        $stack[] = [ $rt, [ 0 ], 'and' ];

        while ( $stack ) {
            [ $rt, $fullpath, $logic ] = array_pop($stack);

            $isRtUnion = $rt && is_a($rt, '\ReflectionUnionType');
            $isRtIntersection = $rt && is_a($rt, '\ReflectionIntersectionType');
            $isRtNamed = $rt && is_a($rt, '\ReflectionNamedType');

            if ($isRtUnion) {
                $logic = 'or';
            }

            if ($isRtUnion || $isRtIntersection) {
                $array = $rt->getTypes();

                end($array);
                while ( null !== ($k = key($array)) ) {
                    $fullpathChild = $fullpath;
                    $fullpathChild[] = $k;

                    $stack[] = [ $rt, $fullpath, $logic ];

                    prev($array);
                }

            } elseif ($isRtNamed) {
                $isRtNamedClass = ! $rt->isBuiltin();

                $rtName = $rt->getName();
                $rtClass = $isRtNamedClass ? $rtName : null;
                $rtIsNullable = $rt->allowsNull();

                $path = array_slice($fullpath, 0, -1);

                $key = implode('.', $fullpath);
                $keyParent = implode('.', $path);

                $list[ $key ] = [ $rtName, $rtClass, $rtIsNullable ];

                $tree[ $keyParent ][ '' ] = $logic;
                $tree[ $keyParent ][ $key ] = true;

                $isNullable = $isNullable || $rtIsNullable;

            } else {
                $list[ 0 ] = [ null, null, true ];

                $tree[ '' ][ '' ] = $logic;
                $tree[ '' ][ 0 ] = true;

                $isNullable = true;
            }
        }
    }

    return [ $list, $tree, $isNullable ];
}
