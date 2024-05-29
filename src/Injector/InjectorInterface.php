<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Reflector\ReflectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


interface InjectorInterface
{
    public function getReflector() : ReflectorInterface;


    public function setSettings(
        bool $resolveUseTake = null
    ); // : static


    /**
     * @param static $di
     *
     * @return static
     */
    public function merge($di);


    public function has($id, Id &$result = null) : bool;


    public function bindItemAlias(Id $id, Id $aliasId, bool $isSingleton = false);

    public function bindItemClass(Id $id, Id $classId, bool $isSingleton = false);

    public function bindItemFactory(Id $id, callable $fnFactory, bool $isSingleton = false);

    public function bindItemInstance(Id $id, object $instance, bool $isSingleton = false);

    public function bindItemAuto(Id $id, $mixed = null, bool $isSingleton = false);


    public function extendItem(Id $id, callable $fnExtend);


    /**
     * @template-covariant T
     *
     * @param class-string<T> $contractT
     *
     * @return T|null
     */
    public function askItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : ?object;


    /**
     * @template-covariant T
     *
     * @param class-string<T> $contractT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function getItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : object;

    /**
     * @template-covariant T
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function makeItem(Id $id, array $parameters = [], string $contractT = '', bool $forceInstanceOf = false) : object;

    /**
     * @template-covariant T
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function takeItem(Id $id, array $parametersWhenNew = [], string $contractT = '', bool $forceInstanceOf = false) : object;


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowireItem(object $instance, array $methodArgs = [], string $methodName = '') : object;


    public function autowireUserFunc(callable $fn, ...$args);

    public function autowireUserFuncArray(callable $fn, array $args = []);


    /**
     * @template-covariant T
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function autowireConstructorArray(string $class, array $parameters = []) : object;
}
