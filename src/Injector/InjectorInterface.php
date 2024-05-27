<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Reflector\ReflectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


interface InjectorInterface
{
    public function getReflector() : ReflectorInterface;


    /**
     * @param static $di
     *
     * @return static
     */
    public function merge($di);


    public function hasBound($id, Id &$result = null) : bool;

    public function hasItem($id, Id &$result = null) : bool;


    /**
     * @param callable|object|array|class-string $mixed
     */
    public function bindItemOfType(string $type, Id $id, $mixed = null, bool $isSingleton = false) : void;

    public function bindItemAlias(Id $id, Id $aliasId, bool $isSingleton = false);

    public function bindItemStruct(Id $id, Id $structId, bool $isSingleton = false);

    public function bindItemFactory(Id $id, callable $fnFactory, bool $isSingleton = null);

    public function bindItemInstance(Id $id, object $instance, bool $isSingleton = null);

    public function bindItem(Id $id, $mixed = null, bool $isSingleton = false);


    public function extendItem(Id $id, callable $fnExtend) : void;


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T|null
     */
    public function askItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : ?object;

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function getItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : object;

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function takeItem(Id $id, array $parametersWhenNew = [], string $contractT = '', bool $forceInstanceOf = false) : object;

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function makeItem(Id $id, array $parameters = [], string $contractT = '', bool $forceInstanceOf = false) : object;


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowireItem(object $instance, array $methodArgs = [], string $methodName = '') : object;


    public function autowireFunctionCall(callable $fn, array $args = []);

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function autowireClassConstructor(string $class, array $parameters = []) : object;
}
