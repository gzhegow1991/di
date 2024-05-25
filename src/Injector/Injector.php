<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Di\Di;
use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Exception\LogicException;
use Gzhegow\Di\Exception\RuntimeException;
use Gzhegow\Di\Reflector\ReflectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;
use function Gzhegow\Di\_php_dump;


class Injector implements InjectorInterface
{
    /**
     * @var ReflectorInterface
     */
    protected $reflector;

    /**
     * @var array<string, string>
     */
    protected $bindList = [];

    /**
     * @var array<string, string>
     */
    protected $aliasList = [];
    /**
     * @var array<string, string>
     */
    protected $structList = [];
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
     * @var array<string, bool>
     */
    protected $isSingletonIndex = [];


    public function __construct(ReflectorInterface $reflector)
    {
        $this->reflector = $reflector;
    }


    public function getReflector() : ReflectorInterface
    {
        return $this->reflector;
    }


    /**
     * @param static $di
     *
     * @return static
     */
    public function merge($di) // : static
    {
        if (! is_a($di, static::class)) {
            throw new RuntimeException(
                'The `di` should be instance of: ' . static::class
                . ' / ' . _php_dump($di)
            );
        }

        foreach ( $di->bindList as $_bindId => $bindType ) {
            $bindId = Id::from($_bindId);
            $bindProperty = "{$bindType}List";
            $bindObject = $di->{$bindProperty}[ $_bindId ];

            $isSingleton = ! empty($di->isSingletonIndex[ $_bindId ]);

            $this->bindItem($bindType, $bindId, $bindObject, $isSingleton);
        }

        foreach ( $di->extendList as $_extendId => $callables ) {
            $extendId = Id::from($_extendId);

            foreach ( $callables as $callable ) {
                $this->extendItem($extendId, $callable);
            }
        }

        return $this;
    }


    public function hasBound($id, Id &$result = null) : bool
    {
        $result = null;

        $id = Id::tryFrom($id);

        if (! $id) {
            return false;
        }

        $_id = $id->getValue();

        if (isset($this->bindList[ $_id ])) {
            $result = $id;

            return true;
        }

        return false;
    }

    public function hasItem($id, Id &$result = null) : bool
    {
        $result = null;

        $id = Id::tryFrom($id);

        if (! $id) {
            return false;
        }

        $_id = $id->getValue();

        if (isset($this->bindList[ $_id ])) {
            $result = $id;

            return true;
        }

        if ($id->isStruct()) {
            $result = $id;

            return true;
        }

        return false;
    }


    /**
     * @param callable|object|array|class-string $mixed
     */
    public function bindItem(string $type, Id $id, $mixed = null, bool $isSingleton = false) : void
    {
        if ($this->hasBound($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        switch ( $type ):
            case Di::BIND_TYPE_ALIAS:
                $aliasId = Id::from($mixed);

                $this->bindItemAlias($id, $aliasId, $isSingleton);

                break;

            case Di::BIND_TYPE_STRUCT:
                $structId = Id::from($mixed);

                $this->bindItemStruct($id, $structId, $isSingleton);

                break;

            case Di::BIND_TYPE_INSTANCE:
                $this->bindItemInstance($id, $mixed, $isSingleton);

                break;

            case Di::BIND_TYPE_FACTORY:
                $this->bindItemFactory($id, $mixed, $isSingleton);

                break;

            default:
                throw new LogicException(
                    'The `mixed` should be callable|object|class-string: '
                    . _php_dump($mixed)
                );

        endswitch;
    }

    public function bindItemAlias(Id $id, Id $aliasId, bool $isSingleton = false) // : static
    {
        $_id = $id->getValue();
        $_alias = $aliasId->getValue();

        if ($_id === $_alias) {
            throw new LogicException(
                'The `id` should be not equal to `aliasId`: '
                . $_id
                . ' / ' . $_alias
            );
        }

        $this->bindList[ $_id ] = Di::BIND_TYPE_ALIAS;
        $this->aliasList[ $_id ] = $_alias;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemStruct(Id $id, Id $structId, bool $isSingleton = false) // : static
    {
        $_id = $id->getValue();
        $_structId = $structId->getValue();

        if (! $structId->isStruct()) {
            throw new LogicException(
                'The `structId` should be existing class or interface: ' . $_structId
            );
        }

        $this->bindList[ $_id ] = Di::BIND_TYPE_STRUCT;
        $this->structList[ $_id ] = $_structId;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemFactory(Id $id, callable $fnFactory, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $_id = $id->getValue();

        $this->bindList[ $_id ] = Di::BIND_TYPE_FACTORY;
        $this->factoryList[ $_id ] = $fnFactory;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemInstance(Id $id, object $instance, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $_id = $id->getValue();

        $this->bindList[ $_id ] = Di::BIND_TYPE_INSTANCE;
        $this->instanceList[ $_id ] = $instance;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }


    public function extendItem(Id $id, callable $fnExtend) : void
    {
        $_id = $id->getValue();

        $this->extendList[ $_id ][ $this->extendId++ ] = $fnExtend;
    }


    public function askItem(Id $id, array $parameters = []) : object
    {
        $instance = $this->hasBound($id)
            ? $this->getItem($id, $parameters)
            : $this->makeItem($id, $parameters);

        return $instance;
    }

    /**
     * @throws NotFoundException
     */
    public function getItem(Id $id, array $parameters = []) : object
    {
        if (! $this->hasBound($id)) {
            throw new NotFoundException(
                'Missing bind: ' . $id
            );
        }

        $_id = $id->getValue();

        if (isset($this->instanceList[ $_id ])) {
            $instance = $this->instanceList[ $_id ];

        } else {
            [ $_aliasId ] = $this->resolveDependencyBoundId($id);

            $aliasId = Id::from($_aliasId);

            $instance = null
                ?? $this->instanceList[ $_aliasId ]
                ?? $this->makeItem($aliasId, $parameters);

            if (isset($this->isSingletonIndex[ $_id ])) {
                $this->instanceList[ $_id ] = $instance;
            }
        }

        return $instance;
    }

    public function makeItem(Id $id, array $parameters = []) : object
    {
        $id = Id::from($id);

        $_id = $id->getValue();

        [ $bound, $boundId, $boundType ] = $this->resolveDependency($id);

        if (Di::BIND_TYPE_INSTANCE === $boundType) {
            $instance = clone $bound;

        } elseif (Di::BIND_TYPE_STRUCT === $boundType) {
            $instance = $this->autowireClassConstructor($bound, $parameters);

        } elseif (Di::BIND_TYPE_FACTORY === $boundType) {
            $instance = $this->autowireFunctionCall($bound, $parameters);

        } else {
            throw new RuntimeException(
                'Unknown `boundType` while making: '
                . $boundType
                . ' / ' . $_id
            );
        }

        $classmap = [ $_id => $_id ];

        if ($id->isStruct()) {
            $classmap += class_parents($_id);
            $classmap += class_implements($_id);
        }

        $classmap += class_parents($instance);
        $classmap += class_implements($instance);

        $intersect = array_intersect_key($this->extendList, $classmap);

        if ($intersect) {
            $callablesOrdered = [];

            foreach ( $intersect as $extendClass => $callables ) {
                $callablesOrdered += $callables;
            }

            ksort($callablesOrdered);

            foreach ( $callablesOrdered as $callable ) {
                $this->autowireFunctionCall($callable, [ $instance ]);
            }
        }

        return $instance;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $classT
     *
     * @return T
     */
    public function askItemGeneric(Id $id, array $paremeters = [], string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->askItem($id, $paremeters);

        if ($forceInstanceOf && ! is_a($instance, $classT)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $classT
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $classT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function getItemGeneric(Id $id, string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->getItem($id);

        if ($forceInstanceOf && ! is_a($instance, $classT)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $classT
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $classT
     *
     * @return T
     */
    public function makeItemGeneric(Id $id, array $parameters = [], string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->makeItem($id, $parameters);

        if ($forceInstanceOf && ! is_a($instance, $classT)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $classT
                . ' / ' . _php_dump($instance)
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
    public function autowireItem(object $instance, array $methodArgs = [], string $methodName = '') : object
    {
        $methodName = $methodName ?: '__autowire';

        $this->autowireFunctionCall([ $instance, $methodName ], $methodArgs);

        return $instance;
    }

    public function autowireFunctionCall(callable $fn, array $args = [])
    {
        $reflectResult = $this->reflector->reflectArgumentsCallable($fn);

        $_args = $this->resolveArguments($reflectResult, $fn, $args);

        $result = call_user_func_array($fn, $_args);

        return $result;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function autowireClassConstructor(string $class, array $parameters = []) : object
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
    public function resolveBind(Id $id, $mixed) : array
    {
        $_id = $id->getValue();

        if (is_callable($mixed)) {
            $fnFactory = $mixed;

            return [ $fnFactory, Di::BIND_TYPE_FACTORY ];

        } elseif (is_object($mixed)) {
            $instance = $mixed;

            return [ $instance, Di::BIND_TYPE_INSTANCE ];

        } elseif (is_string($mixed)) {
            $stringId = Id::from($mixed);

            $_stringId = $stringId->getValue();

            $isAlias = ($_id !== $_stringId);

            if ($isAlias) {
                return [ $stringId, Di::BIND_TYPE_ALIAS ];

            } elseif ($stringId->isStruct()) {
                return [ $stringId, Di::BIND_TYPE_STRUCT ];
            }

        } elseif (null === $mixed) {
            if ($id->isStruct()) {
                return [ $id, Di::BIND_TYPE_STRUCT ];
            }
        }

        throw new LogicException(
            'Unable to resolve `bindType`: '
            . $_id
            . ' / ' . _php_dump($mixed)
        );
    }


    /**
     * @return array{
     *     0: mixed,
     *     1: string,
     *     2: string,
     *     3: array<string, string>
     * }
     */
    public function resolveDependency(Id $id) : array
    {
        $dependencyDefinition = $this->resolveDependencyId($id);

        [ $dependencyId, $dependencyType, $dependencyFullpath ] = $dependencyDefinition;

        $dependencyProperty = "{$dependencyType}List";
        $dependencyMixed = $this->{$dependencyProperty}[ $dependencyId ] ?? $dependencyId;

        return [ $dependencyMixed, $dependencyId, $dependencyType, $dependencyFullpath ];
    }

    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: array<string, string>
     * }
     */
    public function resolveDependencyId(Id $id) : array
    {
        $_id = $id->getValue();

        if (! isset($this->bindList[ $_id ])) {
            $dependencyId = $_id;
            $dependencyType = Di::BIND_TYPE_STRUCT;
            $dependencyFullpath = [ $dependencyId => $dependencyType ];

            if (! $id->isStruct()) {
                throw new RuntimeException(
                    "Invalid struct while resolving: "
                    . '[ ' . implode(' -> ', array_keys($dependencyFullpath)) . ' ]'
                );
            }

            return [ $dependencyId, $dependencyType, $dependencyFullpath ];
        }

        $boundDefinition = $this->resolveDependencyBoundId($id);

        return $boundDefinition;
    }


    /**
     * @return array{
     *     0: mixed,
     *     1: string,
     *     2: string,
     *     3: array<string, string>
     * }
     */
    public function resolveDependencyBound(Id $id) : array
    {
        $boundDefinition = $this->resolveDependencyBoundId($id);

        [ $boundId, $boundType, $boundFullpath ] = $boundDefinition;

        $boundProperty = "{$boundType}List";
        $boundMixed = $this->{$boundProperty}[ $boundId ] ?? null;

        if (null === $boundMixed) {
            throw new RuntimeException(
                "Missing `{$boundProperty}[ {$boundId} ]` while resolving: "
                . '[ ' . implode(' -> ', array_keys($boundFullpath)) . ' ]'
            );
        }

        return [ $boundMixed, $boundId, $boundType, $boundFullpath ];
    }

    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: array<string, string>
     * }
     */
    public function resolveDependencyBoundId(Id $id) : array
    {
        $_id = $id->getValue();

        if (! $this->hasBound($id)) {
            throw new RuntimeException(
                'Missing bound id: ' . $_id
            );
        }

        $boundId = $_id;
        $boundType = $this->bindList[ $boundId ];
        $boundPath = [];
        $boundFullpath = [ $boundId => $boundType ];

        $queue = [];
        $queue[] = [ $boundId, $boundType, $boundPath ];

        while ( $queue ) {
            [ $boundId, $boundType, $boundPath ] = array_shift($queue);

            if ($boundType !== Di::BIND_TYPE_ALIAS) {
                break;
            }

            if (isset($boundPath[ $boundId ])) {
                throw new RuntimeException(
                    'Cyclic dependency resolving detected while resolving: '
                    . '[ ' . implode(' -> ', array_keys($boundPath)) . ' ]'
                );
            }

            $boundFullpath = $boundPath;
            $boundFullpath[ $boundId ] = $boundType;

            $boundId = $this->aliasList[ $boundId ] ?? null;
            $boundType = $this->bindList[ $boundId ] ?? null;

            if (null === $boundType) {
                if (! Id::from($boundId)->isStruct()) {
                    throw new RuntimeException(
                        'Missing bound id while making: '
                        . '[ ' . implode(' -> ', array_keys($boundFullpath)) . ' ]'
                    );
                }

                $boundType = Di::BIND_TYPE_STRUCT;
            }

            $queue[] = [ $boundId, $boundType, $boundFullpath ];
        }

        return [ $boundId, $boundType, $boundFullpath ];
    }


    public function resolveArguments(array $reflectResult, $reflectable, array $arguments = []) : array
    {
        [ 'arguments' => $reflectArguments ] = $reflectResult;

        $reflectArguments = $reflectArguments ?? [];

        $_arguments = [];
        foreach ( $reflectArguments as $i => [ $argName, $argReflectionTypeList, $argReflectionTypeTree, $argIsNullable ] ) {
            if (array_key_exists($argName, $arguments)) {
                $_arguments[ $i ] = $arguments[ $argName ];

            } elseif (isset($arguments[ $i ])) {
                $_arguments[ $i ] = $arguments[ $i ];

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
                                'Resolving UNION / INTERSECT parameters is not implemented: '
                                . "[ {$i} ] \${$argName}"
                                . ' / ' . _php_dump($reflectable)
                            );

                        } else {
                            throw new RuntimeException(
                                'Unable to resolve parameter: '
                                . "[ {$i} ] \${$argName} : {$argReflectionTypeName}"
                                . ' / ' . _php_dump($reflectable)
                            );
                        }
                    }

                    $_arguments[ $i ] = null;

                } else {
                    if (! $this->hasBound($argReflectionTypeClass, $id)) {
                        throw new NotFoundException(
                            'Missing bind to resolve parameter: '
                            . "[ {$i} ] \${$argName} : {$argReflectionTypeName}"
                            . ' / ' . _php_dump($reflectable)
                        );
                    }

                    $_arguments[ $i ] = $this->getItem($id);
                }
            }
        }

        return $_arguments;
    }
}
