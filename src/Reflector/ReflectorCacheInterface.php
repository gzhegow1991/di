<?php

namespace Gzhegow\Di\Reflector;

interface ReflectorCacheInterface
{
    /**
     * @param string|null                                   $cacheMode
     * @param object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
     * @param string|null                                   $cacheDirpath
     *
     * @return static
     */
    public function setCacheSettings(string $cacheMode = null, object $cacheAdapter = null, string $cacheDirpath = null);


    /**
     * @return static
     */
    public function reset();

    /**
     * @return static
     */
    public function clear();

    /**
     * @return static
     */
    public function flush();


    public function hasReflectResult(string $reflectKey, string $reflectNamespace = null, array &$result = null) : bool;

    public function getReflectResult(string $reflectKey, string $reflectNamespace = null, array $fallback = []) : array;


    /**
     * @return static
     */
    public function setReflectResult(array $reflectResult, string $reflectKey, string $reflectNamespace = null);
}
