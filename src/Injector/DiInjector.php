<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Exception\LogicException;
use Gzhegow\Di\Exception\RuntimeException;
use Gzhegow\Di\Reflector\DiReflectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


class DiInjector implements DiInjectorInterface
{
    const BIND_TYPE_ALIAS    = 'ALIAS';
    const BIND_TYPE_CLASS    = 'CLASS';
    const BIND_TYPE_FACTORY  = 'FACTORY';
    const BIND_TYPE_INSTANCE = 'INSTANCE';

    const FETCH_FUNC_GET  = 'GET';
    const FETCH_FUNC_TAKE = 'TAKE';

    const LIST_BIND_TYPE = [
        self::BIND_TYPE_ALIAS    => true,
        self::BIND_TYPE_CLASS    => true,
        self::BIND_TYPE_FACTORY  => true,
        self::BIND_TYPE_INSTANCE => true,
    ];

    const LIST_FETCH_FUNC = [
        self::FETCH_FUNC_GET  => true,
        self::FETCH_FUNC_TAKE => true,
    ];


    /**
     * @var DiReflectorInterface
     */
    protected $reflector;

    /**
     * @var DiInjectorConfig
     */
    protected $config;

    /**
     * @var array<string, string>
     */
    protected $bindToTypeList = [];

    /**
     * @var array<string, string>
     */
    protected $aliasList = [];
    /**
     * @var array<string, string>
     */
    protected $classList = [];
    /**
     * @var array<string, callable>
     */
    protected $factoryList = [];
    /**
     * @var array<string, object>
     */
    protected $instanceList = [];

    /**
     * @var int
     */
    protected $extendId = 0;
    /**
     * @var array<string, callable[]>
     */
    protected $extendList = [];

    /**
     * @var array<string, object>
     */
    protected $singletonList = [];
    /**
     * @var array<string, bool>
     */
    protected $isSingletonIndex = [];


    public function __construct(
        DiReflectorInterface $reflector,
        //
        DiInjectorConfig $config
    )
    {
        $this->reflector = $reflector;

        $this->config = $config;
        $this->config->validate();
    }


    /**
     * @param static $di
     *
     * @return static
     */
    public function merge(DiInjectorInterface $di) : DiInjectorInterface
    {
        if (! is_a($di, static::class)) {
            throw new RuntimeException(
                [ 'The `di` should be instance of: ' . static::class, $di ]
            );
        }

        foreach ( $di->bindToTypeList as $bindIdString => $bindType ) {
            $bindId = Id::from($bindIdString)->orThrow();
            $bindProperty = "{$bindType}List";
            $bindObject = $di->{$bindProperty}[ $bindIdString ];

            $isSingleton = ! empty($di->isSingletonIndex[ $bindIdString ]);

            $this->bindItemOfType($bindType, $bindId, $bindObject, $isSingleton);
        }

        foreach ( $di->extendList as $extendIdString => $callables ) {
            $extendId = Id::from($extendIdString)->orThrow();

            foreach ( $callables as $callable ) {
                $this->extendItem($extendId, $callable);
            }
        }

        return $this;
    }


    public function has($id, ?Id &$result = null) : bool
    {
        $result = null;

        $idObject = Id::from($id)->orNull();
        if (null === $idObject) {
            return false;
        }

        $idValue = $idObject->getValue();

        if (isset($this->bindToTypeList[ $idValue ])) {
            $result = $idObject;

            return true;
        }

        return false;
    }


    public function bindItemAlias(Id $id, Id $idOfAlias, bool $isSingleton = false) : DiInjectorInterface
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();
        $_aliasId = $idOfAlias->getValue();

        if ($_id === $_aliasId) {
            throw new LogicException(
                'The `id` should be not equal to `aliasId`: '
                . $_id
                . ' / ' . $_aliasId
            );
        }

        $this->bindToTypeList[ $_id ] = static::BIND_TYPE_ALIAS;
        $this->aliasList[ $_id ] = $_aliasId;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemClass(Id $id, Id $idOfClass, bool $isSingleton = false) : DiInjectorInterface
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();
        $_classId = $idOfClass->getValue();

        if ($_id !== $_classId) {
            throw new LogicException(
                'The `id` should be equal to `classId`: '
                . $_id
                . ' / ' . $_classId
            );
        }

        if (! $idOfClass->isClass()) {
            throw new LogicException(
                'The `classId` value should be valid class: ' . $_classId
            );
        }

        $this->bindToTypeList[ $_id ] = static::BIND_TYPE_CLASS;
        $this->classList[ $_id ] = $_classId;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemFactory(Id $id, callable $fnFactory, bool $isSingleton = false) : DiInjectorInterface
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();

        $this->bindToTypeList[ $_id ] = static::BIND_TYPE_FACTORY;
        $this->factoryList[ $_id ] = $fnFactory;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemInstance(Id $id, object $instance, bool $isSingleton = false) : DiInjectorInterface
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();

        $this->bindToTypeList[ $_id ] = static::BIND_TYPE_INSTANCE;
        $this->instanceList[ $_id ] = $instance;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }


    public function bindItemAuto(Id $id, $mixed = null, bool $isSingleton = false) : DiInjectorInterface
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        [ $_mixed, $bindType ] = $this->resolveBind($id, $mixed);

        $this->bindItemOfType($bindType, $id, $_mixed, $isSingleton);

        return $this;
    }

    /**
     * @param callable|object|array|class-string $mixed
     */
    protected function bindItemOfType(string $type, Id $id, $mixed, bool $isSingleton = false) : DiInjectorInterface
    {
        switch ( $type ):
            case static::BIND_TYPE_ALIAS:
                $aliasId = Id::from($mixed)->orThrow();

                $this->bindItemAlias($id, $aliasId, $isSingleton);

                break;

            case static::BIND_TYPE_CLASS:
                $classId = Id::from($mixed)->orThrow();

                $this->bindItemClass($id, $classId, $isSingleton);

                break;

            case static::BIND_TYPE_INSTANCE:
                $instance = $mixed;

                $this->bindItemInstance($id, $instance, $isSingleton);

                break;

            case static::BIND_TYPE_FACTORY:
                $fnFactory = $mixed;

                $this->bindItemFactory($id, $fnFactory, $isSingleton);

                break;

            default:
                throw new LogicException(
                    [
                        'The `mixed` should be callable|object|array|class-string',
                        $mixed,
                    ]
                );

        endswitch;

        return $this;
    }


    public function extendItem(Id $id, callable $fnExtend) : DiInjectorInterface
    {
        $_id = $id->getValue();

        $this->extendList[ $_id ][ $this->extendId++ ] = $fnExtend;

        return $this;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T|null
     */
    public function askItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : ?object
    {
        if (! $this->has($id)) {
            return null;
        }

        $instance = $this->getItem($id, $contractT, $forceInstanceOf, $parametersWhenNew);

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                [
                    'Returned object should be instance of: ' . $contractT,
                    $instance,
                ]
            );
        }

        return $instance;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function getItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : object
    {
        if (! $this->has($id)) {
            throw new NotFoundException(
                'Missing bind: ' . $id
            );
        }

        $_id = $id->getValue();

        if (isset($this->singletonList[ $_id ])) {
            $instance = $this->singletonList[ $_id ];

        } elseif (isset($this->instanceList[ $_id ])) {
            $instance = $this->instanceList[ $_id ];

            if ($forceInstanceOf && ! is_a($instance, $contractT)) {
                throw new RuntimeException(
                    [
                        'Returned object should be instance of: ' . $contractT,
                        $instance,
                    ]
                );
            }

            if (true
                && isset($this->isSingletonIndex[ $_id ])
                && ! isset($this->singletonList[ $_id ])
            ) {
                $this->singletonList[ $_id ] = $instance;
            }

            return $instance;

        } else {
            $resolvedPath = $this->resolveItemPath($id);

            $instance = null;
            foreach ( $resolvedPath as $_resolvedId => $resolvedType ) {
                $isSingleton = false;
                $isInstance = false;

                (false)
                || ($isSingleton = isset($this->singletonList[ $_resolvedId ]))
                || ($isInstance = isset($this->instanceList[ $_resolvedId ]));

                if ($isSingleton || $isInstance) {
                    $instance = null
                        ?? $this->singletonList[ $_resolvedId ]
                        ?? $this->instanceList[ $_resolvedId ];

                    break;
                }
            }

            if (null === $instance) {
                $instance = $this->resolveItem($resolvedPath, $id, $parametersWhenNew);
            }

            if ($forceInstanceOf && ! is_a($instance, $contractT)) {
                throw new RuntimeException(
                    [
                        'Returned object should be instance of: ' . $contractT,
                        $instance,
                    ]
                );
            }

            foreach ( $resolvedPath as $_resolvedId => $resolvedType ) {
                if (true
                    && isset($this->isSingletonIndex[ $_resolvedId ])
                    && ! isset($this->singletonList[ $_resolvedId ])
                ) {
                    $this->singletonList[ $_resolvedId ] = $instance;
                }
            }
        }

        return $instance;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function makeItem(Id $id, array $parameters = [], string $contractT = '', bool $forceInstanceOf = false) : object
    {
        $resolvedPath = $this->resolveItemPath($id);

        $instance = $this->resolveItem($resolvedPath, $id, $parameters);

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                [
                    'Returned object should be instance of: ' . $contractT,
                    $instance,
                ]
            );
        }

        foreach ( $resolvedPath as $_resolvedId => $resolvedType ) {
            if (true
                && isset($this->isSingletonIndex[ $_resolvedId ])
                && ! isset($this->singletonList[ $_resolvedId ])
            ) {
                $this->singletonList[ $_resolvedId ] = $instance;
            }
        }

        return $instance;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function takeItem(Id $id, array $parametersWhenNew = [], string $contractT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->has($id)
            ? $this->getItem($id, $contractT, $forceInstanceOf, $parametersWhenNew)
            : $this->makeItem($id, $parametersWhenNew);

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                [
                    'Returned object should be instance of: ' . $contractT,
                    $instance,
                ]
            );
        }

        return $instance;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T> $contractT
     *
     * @return T
     */
    public function fetchItem(Id $id, array $parametersWhenNew = [], string $contractT = '', bool $forceInstanceOf = false) : object
    {
        switch ( $this->config->fetchFunc ):
            case static::FETCH_FUNC_GET:
                $instance = $this->getItem($id, $contractT, $forceInstanceOf, $parametersWhenNew);

                break;

            case static::FETCH_FUNC_TAKE:
                $instance = $this->takeItem($id, $parametersWhenNew, $forceInstanceOf, $forceInstanceOf);

                break;

            default:
                throw new RuntimeException(
                    [
                        'Unknown `fetchFunc`',
                        $this->config->fetchFunc,
                    ]
                );

        endswitch;

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                [
                    'Returned object should be instance of: ' . $contractT,
                    $instance,
                ]
            );
        }

        return $instance;
    }


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowireInstance(object $instance, array $methodArgs = [], string $methodName = '') : object
    {
        $methodName = $methodName ?: '__autowire';

        $this->callUserFuncArrayAutowired([ $instance, $methodName ], $methodArgs);

        return $instance;
    }


    public function callUserFuncAutowired(callable $fn, ...$args)
    {
        $reflectResult = $this->reflector->reflectArguments($fn);

        $_args = $this->resolveArguments($reflectResult, $fn, $args);

        $result = call_user_func($fn, ...$_args);

        return $result;
    }

    public function callUserFuncArrayAutowired(callable $fn, array $args = [])
    {
        $reflectResult = $this->reflector->reflectArguments($fn);

        $_args = $this->resolveArguments($reflectResult, $fn, $args);

        $result = call_user_func_array($fn, $_args);

        return $result;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function callConstructorArrayAutowired(string $class, array $parameters = []) : object
    {
        $reflectResult = $this->reflector->reflectArgumentsConstructor($class);

        $arguments = $this->resolveArguments($reflectResult, $class, $parameters);

        $instance = new $class(...$arguments);

        return $instance;
    }


    /**
     * @return array{
     *     0: mixed,
     *     1: string,
     * }
     */
    protected function resolveBind(Id $id, $mixed) : array
    {
        $idValue = $id->getValue();

        $result = null;

        if (null === $mixed) {
            if ($id->isClass()) {
                return [ $id, static::BIND_TYPE_CLASS ];
            }

        } elseif (is_callable($mixed)) {
            $fnFactory = $mixed;

            $result = [ $fnFactory, static::BIND_TYPE_FACTORY ];

        } elseif (is_object($mixed)) {
            $object = $mixed;

            $result = [ $object, static::BIND_TYPE_INSTANCE ];

        } elseif (is_string($mixed) && ('' !== $mixed)) {
            $mixedIdObject = Id::from($mixed)->orNull();

            if (null !== $mixedIdObject) {
                $mixedIdValue = $mixedIdObject->getValue();

                if ($isAlias = ($idValue !== $mixedIdValue)) {
                    $result = [ $mixedIdObject, static::BIND_TYPE_ALIAS ];

                } elseif ($isClass = $mixedIdObject->isClass()) {
                    $result = [ $mixedIdObject, static::BIND_TYPE_CLASS ];
                }
            }
        }

        if (null === $result) {
            throw new LogicException(
                [
                    'Unable to resolve bind: ' . $idValue,
                    $idValue,
                ]
            );
        }

        return $result;
    }


    protected function resolveItem(array $resolvedPath, Id $id, array $parameters = []) : object
    {
        $_id = $id->getValue();

        $lastResolvedType = end($resolvedPath);
        $lastResolvedId = key($resolvedPath);

        if (static::BIND_TYPE_INSTANCE === $lastResolvedType) {
            $resolvedInstance = $this->instanceList[ $lastResolvedId ];

            $instance = clone $resolvedInstance;

        } elseif (static::BIND_TYPE_CLASS === $lastResolvedType) {
            $resolvedClass = $this->classList[ $lastResolvedId ] ?? $lastResolvedId;

            $instance = $this->callConstructorArrayAutowired($resolvedClass, $parameters);

        } elseif (static::BIND_TYPE_FACTORY === $lastResolvedType) {
            $resolvedFnFactory = $this->factoryList[ $lastResolvedId ];

            $instance = $this->callUserFuncArrayAutowired($resolvedFnFactory, $parameters);

        } else {
            // } elseif (static::BIND_TYPE_ALIAS === $lastResolvedType) {

            throw new RuntimeException(
                'Unknown `boundType` while making: '
                . $lastResolvedType
                . ' / ' . $_id
            );
        }

        $extendIdList = [];
        $extendContractList = [];

        $id->isContract()
            ? ($extendContractList[ $_id ] = true)
            : ($extendIdList[ $_id ] = true);

        foreach ( $resolvedPath as $resolvedIdString => $resolvedType ) {
            $resolvedId = Id::from($resolvedIdString)->orThrow();

            $resolvedId->isContract()
                ? ($extendContractList[ $resolvedIdString ] = true)
                : ($extendIdList[ $resolvedIdString ] = true);
        }

        $extendContractList[ get_class($instance) ] = true;

        $extendList = [];
        $extendList += $extendIdList;
        $extendList += $extendContractList;
        foreach ( $extendContractList as $contract => $bool ) {
            $extendList += class_implements($contract);
            $extendList += class_parents($contract);
        }

        $intersect = array_intersect_key($this->extendList, $extendList);

        if ($intersect) {
            $callablesOrdered = [];

            foreach ( $intersect as $callables ) {
                $callablesOrdered += $callables;
            }

            ksort($callablesOrdered);

            foreach ( $callablesOrdered as $callable ) {
                $this->callUserFuncArrayAutowired($callable, [ $instance ]);
            }
        }

        return $instance;
    }

    /**
     * @return array<string, string>
     */
    protected function resolveItemPath(Id $id) : array
    {
        $_id = $id->getValue();

        $result = isset($this->bindToTypeList[ $_id ])
            ? $this->resolveBoundPath($id)
            : $this->resolveUnboundPath($id);

        return $result;
    }

    /**
     * @return array<string, string>
     */
    protected function resolveBoundPath(Id $id) : array
    {
        $_id = $id->getValue();

        if (! isset($this->bindToTypeList[ $_id ])) {
            throw new RuntimeException(
                'Unable to ' . __FUNCTION__ . '(). '
                . 'Missing `id`: ' . $_id
            );
        }

        $boundId = $_id;
        $boundType = $this->bindToTypeList[ $_id ];

        $queue = [];
        $queue[] = [ $boundId, $boundType, [] ];

        $boundFullpath = [];

        do {
            [ $boundId, $boundType, $boundPath ] = array_shift($queue);

            if (isset($boundPath[ $boundId ])) {
                throw new RuntimeException(
                    'Unable to ' . __FUNCTION__ . '(). '
                    . 'Cyclic dependency resolving detected while resolving: '
                    . '[ ' . implode(' -> ', array_keys($boundPath)) . ' ]'
                );
            }

            $boundFullpath = $boundPath;
            $boundFullpath[ $boundId ] = $boundType;

            if (static::BIND_TYPE_ALIAS === $boundType) {
                $boundIdChild = $this->aliasList[ $boundId ] ?? null;
                $boundTypeChild = $this->bindToTypeList[ $boundIdChild ] ?? null;
                $boundPathChild = $boundFullpath;

                if (null === $boundTypeChild) {
                    if (class_exists($boundIdChild)) {
                        $boundTypeChild = static::BIND_TYPE_CLASS;

                    } else {
                        throw new RuntimeException(
                            'Unable to ' . __FUNCTION__ . '(). '
                            . 'Missing `boundId` while making: '
                            . '[ ' . implode(' -> ', array_keys($boundPathChild)) . ' ]'
                        );
                    }
                }

                $queue[] = [ $boundIdChild, $boundTypeChild, $boundPathChild ];
            }
        } while ( $queue );

        $result = $boundFullpath;

        return $result;
    }

    /**
     * @return array<string, string>
     */
    protected function resolveUnboundPath(Id $id) : array
    {
        $_id = $id->getValue();

        if (isset($this->bindToTypeList[ $_id ])) {
            throw new RuntimeException(
                'Unable to ' . __FUNCTION__ . '(). '
                . 'Bind exists, so this `id` it is not a class: ' . $_id
            );
        }

        if (! $id->isClass()) {
            throw new RuntimeException(
                'Unable to ' . __FUNCTION__ . '(). '
                . 'The `id` is not a class: ' . $_id
            );
        }

        $classId = $_id;
        $classType = static::BIND_TYPE_CLASS;

        $result = [ $classId => $classType ];

        return $result;
    }


    protected function resolveArguments(array $reflectResult, $reflectable, array $parameters = []) : array
    {
        [ 'arguments' => $reflectArguments ] = $reflectResult;

        $reflectArguments = $reflectArguments ?? [];

        $_arguments = [];
        foreach ( $reflectArguments as $i => $reflectArgument ) {
            [ $argName, $argReflectionTypeList, $argReflectionTypeTree, $argIsNullable ] = $reflectArgument;

            if (array_key_exists($argName, $parameters)) {
                $_arguments[ $i ] = $parameters[ $argName ];

            } elseif (isset($parameters[ $i ])) {
                $_arguments[ $i ] = $parameters[ $i ];

            } else {
                $argReflectionTypeIsMulti = (count($argReflectionTypeTree[ '' ]) > 2);

                $argReflectionTypeName = false;
                $argReflectionTypeClass = false;
                if (! $argReflectionTypeIsMulti) {
                    $argReflectionTypeName = $argReflectionTypeList[ 0 ][ 'name' ] ?? null;
                    $argReflectionTypeClass = $argReflectionTypeList[ 0 ][ 'class' ] ?? null;
                }

                if (! isset($argReflectionTypeClass)) {
                    if (! $argIsNullable) {
                        if ($argReflectionTypeIsMulti) {
                            throw new RuntimeException(
                                [
                                    'Resolving UNION / INTERSECT parameters is not implemented: ' . "[ {$i} ] \${$argName}",
                                    $reflectable,
                                ]
                            );

                        } else {
                            throw new RuntimeException(
                                [
                                    'Unable to resolve parameter: ' . "[ {$i} ] \${$argName} : {$argReflectionTypeName}",
                                    $reflectable,
                                ]
                            );
                        }
                    }

                    $_arguments[ $i ] = null;

                } else {
                    $id = Id::from($argReflectionTypeClass)->orThrow();

                    try {
                        $_arguments[ $i ] = $this->fetchItem($id);
                    }
                    catch ( NotFoundException $e ) {
                        throw new NotFoundException(
                            [
                                'Missing bound `argReflectionTypeClass` to resolve parameter: ' . "[ {$i} ] \${$argName} : {$argReflectionTypeName}",
                                $reflectable,
                            ]
                        );
                    }
                }
            }
        }

        return $_arguments;
    }
}
