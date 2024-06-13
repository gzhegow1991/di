<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di\Reflector;


interface ReflectorInterface
{
    /**
     * @param string|null                                   $cacheMode
     * @param object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
     * @param string|null                                   $cacheDirpath
     *
     * @return static
     */
    public function setCacheSettings(
        string $cacheMode = null,
        object $cacheAdapter = null,
        string $cacheDirpath = null
    );


    /**
     * @return static
     */
    public function resetCache();

    /**
     * @return static
     */
    public function clearCache();

    /**
     * @return static
     */
    public function flushCache();


    /**
     * @param callable|object|array|string $callable
     */
    public function reflectArgumentsCallable($callable) : array;

    /**
     * @param object|class-string $objectOrClass
     */
    public function reflectArgumentsConstructor($objectOrClass) : array;
}
