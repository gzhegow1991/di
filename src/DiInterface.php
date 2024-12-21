<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\LazyService\LazyService;
use Gzhegow\Di\Injector\InjectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


interface DiInterface
{
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
    public function saveCache();


    /**
     * @return static
     */
    public function merge(InjectorInterface $di);


    /**
     * @param string $id
     */
    public function has($id, Id &$result = null) : bool;


    /**
     * @return static
     */
    public function bind($id, $mixed = null, bool $isSingleton = null);

    /**
     * @return static
     */
    public function bindSingleton($id, $mixed = null);


    /**
     * @return static
     */
    public function bindAlias($id, $aliasId, bool $isSingleton = null);

    /**
     * @param class-string $classId
     *
     * @return static
     */
    public function bindClass($id, $classId, bool $isSingleton = null);

    /**
     * @param callable $fnFactory
     *
     * @return static
     */
    public function bindFactory($id, $fnFactory, bool $isSingleton = null);

    /**
     * @return static
     */
    public function bindInstance($id, object $instance, bool $isSingleton = null);


    /**
     * @param callable $fnExtend
     *
     * @return static
     */
    public function extend($id, $fnExtend);


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T|null
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
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function take($id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function fetch($id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null);


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
     * @template-covariant T
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return LazyService<T>|T
     */
    public function takeLazy($id, array $parametersWhenNew = null, string $contractT = null);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return LazyService<T>|T
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
