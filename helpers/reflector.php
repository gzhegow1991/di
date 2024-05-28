<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di;

use Gzhegow\Di\Reflector\Reflector;
use Gzhegow\Di\Reflector\ReflectorFactory;
use Gzhegow\Di\Reflector\Struct\ReflectorCacheRuntime;


/**
 * @return Reflector
 */
function _reflector(Reflector $reflector = null)
{
    static $instance;

    $before = $instance;

    $instance = $reflector ?? $instance ?? (new ReflectorFactory())->newReflector();

    if ($before !== $instance) {
        $instance::setInstance($instance);
    }

    return $instance;
}


function _reflector_reset_cache() // : static
{
    return _reflector()->resetCache();
}

function _reflector_load_cache(bool $readData = null) : ReflectorCacheRuntime
{
    return _reflector()->loadCache($readData);
}

function _reflector_clear_cache() // : static
{
    return _reflector()->clearCache();
}

function _reflector_flush_cache() // : static
{
    return _reflector()->flushCache();
}


/**
 * @param array{
 *     cacheMode: static::MODE_RUNTIME|static::MODE_NO_CACHE|static::MODE_STORAGE|null,
 *     cacheAdapter: object|\Psr\Cache\CacheItemPoolInterface|null,
 *     cacheDirpath: string|null,
 *     cacheFilename: string|null,
 * }|null $settings
 */
function _reflector_cache_settins(array $settings = null)
{
    $settings = $settings ?? [];

    $cacheMode = $settings[ 'cacheMode' ] ?? $settings[ 0 ] ?? null;
    $cacheAdapter = $settings[ 'cacheAdapter' ] ?? $settings[ 1 ] ?? null;
    $cacheDirpath = $settings[ 'cacheDirpath' ] ?? $settings[ 2 ] ?? null;
    $cacheFilename = $settings[ 'cacheFilename' ] ?? $settings[ 3 ] ?? null;

    return _reflector()->setCacheSettings(
        $cacheMode,
        $cacheAdapter,
        $cacheDirpath,
        $cacheFilename
    );
}


/**
 * @param callable|object|array|string $callable
 */
function _reflect_arguments_callable($callable) : array
{
    return _reflector()->reflectArgumentsCallable($callable);
}

/**
 * @param object|class-string $objectOrClass
 */
function _reflect_arguments_constructor($objectOrClass) : array
{
    return _reflector()->reflectArgumentsConstructor($objectOrClass);
}
