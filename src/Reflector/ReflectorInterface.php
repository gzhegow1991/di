<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di\Reflector;

use Gzhegow\Di\Reflector\Struct\ReflectorCacheRuntime;


interface ReflectorInterface
{
    public function resetCache();

    public function loadCache(bool $readData = null) : ReflectorCacheRuntime;

    public function clearCache();

    public function flushCache();


    /**
     * @param string|null                                   $cacheMode
     * @param object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
     * @param string|null                                   $cacheDirpath
     * @param string|null                                   $cacheFilename
     */
    public function setCacheSettings(
        string $cacheMode = null,
        object $cacheAdapter = null,
        string $cacheDirpath = null,
        string $cacheFilename = null
    );


    /**
     * @param callable|object|array|string $callable
     */
    public function reflectArgumentsCallable($callable) : array;

    /**
     * @param object|class-string $objectOrClass
     */
    public function reflectArgumentsConstructor($objectOrClass) : array;
}
