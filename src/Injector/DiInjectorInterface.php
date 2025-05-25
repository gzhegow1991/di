<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


interface DiInjectorInterface
{
    /**
     * @param DiInjectorInterface $di
     *
     * @return DiInjectorInterface
     */
    public function merge(DiInjectorInterface $di) : DiInjectorInterface;


    public function has($id, ?Id &$result = null) : bool;


    public function bindItemAlias(Id $id, Id $aliasId, bool $isSingleton = false) : DiInjectorInterface;

    public function bindItemClass(Id $id, Id $classId, bool $isSingleton = false) : DiInjectorInterface;

    public function bindItemFactory(Id $id, callable $fnFactory, bool $isSingleton = false) : DiInjectorInterface;

    public function bindItemInstance(Id $id, object $instance, bool $isSingleton = false) : DiInjectorInterface;

    public function bindItemAuto(Id $id, $mixed = null, bool $isSingleton = false) : DiInjectorInterface;


    public function extendItem(Id $id, callable $fnExtend) : DiInjectorInterface;


    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T|null
     */
    public function askItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : ?object;


    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function getItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : object;

    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function makeItem(Id $id, array $parameters = [], string $contractT = '', bool $forceInstanceOf = false) : object;

    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function takeItem(Id $id, array $parametersWhenNew = [], string $contractT = '', bool $forceInstanceOf = false) : object;

    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function fetchItem(Id $id, array $parametersWhenNew = [], string $contractT = '', bool $forceInstanceOf = false) : object;


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowireInstance(object $instance, array $methodArgs = [], string $methodName = '') : object;


    /**
     * @param callable $fn
     * @param array    $args
     *
     * @return mixed
     */
    public function callUserFuncAutowired(callable $fn, ...$args);

    /**
     * @param callable $fn
     * @param array    $args
     *
     * @return mixed
     */
    public function callUserFuncArrayAutowired(callable $fn, array $args = []);


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function callConstructorArrayAutowired(string $class, array $parameters = []) : object;
}
