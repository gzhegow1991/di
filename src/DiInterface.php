<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\LazyService\DiLazyService;
use Gzhegow\Di\Exception\Runtime\NotFoundException;
use Gzhegow\Di\LazyService\DiLazyServiceFactoryInterface;


interface DiInterface
{
    public function resetCache() : DiInterface;

    public function clearCache() : DiInterface;

    public function saveCache() : DiInterface;


    public function merge(DiInterface $di) : DiInterface;


    /**
     * @param string $id
     */
    public function has($id, Id &$result = null) : bool;


    public function bind($id, $mixed = null, bool $isSingleton = null) : DiInterface;

    public function bindSingleton($id, $mixed = null) : DiInterface;


    public function bindAlias($id, $aliasId, bool $isSingleton = null) : DiInterface;

    /**
     * @param class-string $classId
     */
    public function bindClass($id, $classId, bool $isSingleton = null) : DiInterface;

    /**
     * @param callable $fnFactory
     */
    public function bindFactory($id, $fnFactory, bool $isSingleton = null) : DiInterface;

    public function bindInstance($id, object $instance, bool $isSingleton = null) : DiInterface;


    /**
     * @param callable $fnExtend
     */
    public function extend($id, $fnExtend) : DiInterface;


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T|null
     */
    public function ask($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null) : ?object;


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function get($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null) : object;

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function make($id, array $parameters = null, string $contractT = null, bool $forceInstanceOf = null) : object;

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function take($id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null) : object;

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function fetch($id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null) : object;


    public function setLazyServiceFactory(DiLazyServiceFactoryInterface $lazyServiceFactory) : DiInterface;

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     *
     * @throws NotFoundException
     */
    public function getLazy($id, string $contractT = null, array $parametersWhenNew = null);

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public function makeLazy($id, array $parameters = null, string $contractT = null);

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public function takeLazy($id, array $parametersWhenNew = null, string $contractT = null);

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public function fetchLazy($id, array $parametersWhenNew = null, string $contractT = null);


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowireInstance(object $instance, array $methodArgs = null, string $methodName = null);


    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public function callUserFuncAutowired($fn, ...$args);

    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public function callUserFuncArrayAutowired($fn, array $args = null);
}
