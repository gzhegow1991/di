<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Reflector\Reflector;


/**
 * @return Reflector
 */
function _reflector(Reflector $reflector = null)
{
    static $instance;

    $instance = $reflector ?? $instance ?? new Reflector();

    return $instance;
}

/**
 * @param array{
 *     cacheMode: static::MODE_RUNTIME|static::MODE_NO_CACHE|static::MODE_STORAGE|null,
 *     cacheAdapter: object|\Psr\Cache\CacheItemPoolInterface|null,
 *     cacheDirpath: string|null,
 *     cacheFilename: string|null,
 * }|null $settings
 *
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */
function _reflector_cache_settins(array $settings = null) : void
{
    $settings = $settings ?? [];

    $cacheMode = $settings[ 'cacheMode' ] ?? $settings[ 0 ] ?? null;
    $cacheAdapter = $settings[ 'cacheAdapter' ] ?? $settings[ 1 ] ?? null;
    $cacheDirpath = $settings[ 'cacheDirpath' ] ?? $settings[ 2 ] ?? null;
    $cacheFilename = $settings[ 'cacheFilename' ] ?? $settings[ 3 ] ?? null;

    _reflector()->setCacheSettings(
        $cacheMode,
        $cacheAdapter,
        $cacheDirpath,
        $cacheFilename
    );
}

function _reflector_flush_cache() : void
{
    _reflector()->flushCache();
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
