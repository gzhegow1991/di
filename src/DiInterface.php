<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Injector\InjectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


interface DiInterface
{
    /**
     * @param array{
     *     reflectorCacheMode: string|null,
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
     * @return static
     */
    public function merge(InjectorInterface $di);


    /**
     * @param string $id
     */
    public function hasBound($id, Id &$result = null) : bool;

    /**
     * @param string $id
     */
    public function hasItem($id, Id &$result = null) : bool;


    public function bind($id, $mixed = null, bool $isSingleton = null);

    public function bindSingleton($id, $mixed = null);


    public function bindAlias($id, $aliasId, bool $isSingleton = null);

    /**
     * @param class-string $structId
     */
    public function bindStruct($id, $structId, bool $isSingleton = null);

    /**
     * @param callable $fnFactory
     */
    public function bindFactory($id, $fnFactory, bool $isSingleton = null);

    public function bindInstance($id, object $instance, bool $isSingleton = null);


    /**
     * @param callable $fnExtend
     */
    public function extend($id, $fnExtend);


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function ask($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function get($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function make($id, array $parameters = null, string $contractT = null, bool $forceInstanceOf = null);


    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return LazyService<T>|T
     */
    public function askLazy($id, string $contractT = null, array $parametersWhenNew = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return LazyService<T>|T
     *
     * @throws NotFoundException
     */
    public function getLazy($id, string $contractT = null, array $parametersWhenNew = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return LazyService<T>|T
     */
    public function makeLazy($id, array $parameters = null, string $contractT = null);


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
}
