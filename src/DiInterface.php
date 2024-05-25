<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


interface DiInterface
{
    /**
     * @param array{
     *     reflectorCacheMode: Reflector::CACHE_MODE_RUNTIME|Reflector::CACHE_MODE_NO_CACHE|Reflector::CACHE_MODE_STORAGE|null,
     *     reflectorCacheAdapter: object|\Psr\Cache\CacheItemPoolInterface|null,
     *     reflectorCacheDirpath: string|null,
     *     reflectorCacheFilename: string|null,
     * }|null $settings
     *
     * @noinspection PhpUndefinedNamespaceInspection
     * @noinspection PhpUndefinedClassInspection
     */
    public function setCacheSettings(array $settings = null);

    public function clearCache();

    public function flushCache();


    /**
     * @param static $di
     *
     * @return static
     */
    public function merge($di);


    /**
     * @param string $id
     */
    public function has($id) : bool;


    public function bind($id, $mixed = null, bool $isSingleton = null);

    public function bindSingleton($id, $mixed = null);

    public function bindAlias($id, $aliasId, bool $isSingleton = null);

    /**
     * @param class-string $structId
     */
    public function bindStruct($id, $structId, bool $isSingleton = null);

    public function bindInstance($id, object $instance, bool $isSingleton = null);

    /**
     * @param callable $fnFactory
     */
    public function bindFactory($id, $fnFactory, bool $isSingleton = null);


    /**
     * @param callable $fnExtend
     */
    public function extend($id, $fnExtend);


    /**
     * @param string $id
     *
     * @return object
     */
    public function ask($id, array $parameters = null);

    /**
     * @param string $id
     *
     * @return object
     *
     * @throws NotFoundException
     */
    public function get($id);

    /**
     * @param string $id
     *
     * @return object
     */
    public function make($id, array $parameters = null);


    /**
     * @return LazyService
     */
    public function askLazy(string $id, array $parameters = null);

    /**
     * @return LazyService
     *
     * @throws NotFoundException
     */
    public function getLazy(string $id);

    /**
     * @return LazyService
     */
    public function makeLazy(string $id, array $parameters = null);


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     */
    public function askGeneric(string $id, array $parameters = null, $structT = null, bool $forceInstanceOf = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function getGeneric(string $id, $structT = null, bool $forceInstanceOf = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     */
    public function makeGeneric(string $id, array $parameters = null, $structT = null, bool $forceInstanceOf = null);


    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     */
    public function askLazyGeneric(string $id, array $parameters = null, $structT = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     *
     * @throws NotFoundException
     */
    public function getLazyGeneric(string $id, $structT = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     */
    public function makeLazyGeneric(string $id, array $parameters = null, $structT = null);


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowire(object $instance, array $methodArgs = null, string $methodName = null);

    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public function call($fn, array $args = null);


    public function newLazyServiceAsk($id, array $parameters = null) : LazyService;

    public function newLazyServiceGet($id) : LazyService;

    public function newLazyServiceMake($id, array $parameters = null) : LazyService;


    public function lazyServiceFnFactoryAskPublic($lazyId, array $parameters = null) : object;

    public function lazyServiceFnFactoryGetPublic($lazyId) : object;

    public function lazyServiceFnFactoryMakePublic($lazyId, array $parameters = null) : object;
}
