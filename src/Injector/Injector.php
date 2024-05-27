<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Exception\LogicException;
use Gzhegow\Di\Exception\RuntimeException;
use Gzhegow\Di\Reflector\ReflectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;
use function Gzhegow\Di\_php_dump;


class Injector implements InjectorInterface
{
    const BIND_TYPE_ALIAS    = 'alias';
    const BIND_TYPE_CLASS    = 'class';
    const BIND_TYPE_FACTORY  = 'factory';
    const BIND_TYPE_INSTANCE = 'instance';

    const LIST_BIND_TYPE = [
        self::BIND_TYPE_ALIAS    => true,
        self::BIND_TYPE_CLASS    => true,
        self::BIND_TYPE_FACTORY  => true,
        self::BIND_TYPE_INSTANCE => true,
    ];


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
     * @var array<string, bool>
     */
    protected $isSingletonIndex = [];

    /**
     * @var bool
     */
    protected $settingsResolveArgumentsUseTake = false;


    public function __construct(ReflectorInterface $reflector)
    {
        $this->reflector = $reflector;
    }


    public function getReflector() : ReflectorInterface
    {
        return $this->reflector;
    }


    public function setSettings(
        bool $resolveUseTake = null
    ) // : static
    {
        $resolveUseTake = $resolveUseTake ?? false;

        $this->settingsResolveArgumentsUseTake = $resolveUseTake;

        return $this;
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

            $this->bindItemOfType($bindType, $bindId, $bindObject, $isSingleton);
        }

        foreach ( $di->extendList as $_extendId => $callables ) {
            $extendId = Id::from($_extendId);

            foreach ( $callables as $callable ) {
                $this->extendItem($extendId, $callable);
            }
        }

        return $this;
    }


    public function has($id, Id &$result = null) : bool
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


    public function bindItemAlias(Id $id, Id $aliasId, bool $isSingleton = false) // : static
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();
        $_aliasId = $aliasId->getValue();

        if ($_id === $_aliasId) {
            throw new LogicException(
                'The `id` should be not equal to `aliasId`: '
                . $_id
                . ' / ' . $_aliasId
            );
        }

        $this->bindList[ $_id ] = static::BIND_TYPE_ALIAS;
        $this->aliasList[ $_id ] = $_aliasId;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemClass(Id $id, Id $classId, bool $isSingleton = false) // : static
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();
        $_classId = $classId->getValue();

        if ($_id !== $_classId) {
            throw new LogicException(
                'The `id` should be equal to `classId`: '
                . $_id
                . ' / ' . $_classId
            );
        }

        if (! $classId->isClass()) {
            throw new LogicException(
                'The `classId` value should be valid class: ' . $_classId
            );
        }

        $this->bindList[ $_id ] = static::BIND_TYPE_CLASS;
        $this->classList[ $_id ] = $_classId;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemFactory(Id $id, callable $fnFactory, bool $isSingleton = false) // : static
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();

        $this->bindList[ $_id ] = static::BIND_TYPE_FACTORY;
        $this->factoryList[ $_id ] = $fnFactory;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    public function bindItemInstance(Id $id, object $instance, bool $isSingleton = false) // : static
    {
        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_id = $id->getValue();

        $this->bindList[ $_id ] = static::BIND_TYPE_INSTANCE;
        $this->instanceList[ $_id ] = $instance;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }


    public function bindItemAuto(Id $id, $mixed = null, bool $isSingleton = false) // : static
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
    protected function bindItemOfType(string $type, Id $id, $mixed, bool $isSingleton = false) // : static
    {
        switch ( $type ):
            case static::BIND_TYPE_ALIAS:
                $aliasId = Id::from($mixed);

                $this->bindItemAlias($id, $aliasId, $isSingleton);

                break;

            case static::BIND_TYPE_CLASS:
                $classId = Id::from($mixed);

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
                    'The `mixed` should be callable|object|array|class-string: ' . _php_dump($mixed)
                );

        endswitch;

        return $this;
    }


    public function extendItem(Id $id, callable $fnExtend) // : static
    {
        $_id = $id->getValue();

        $this->extendList[ $_id ][ $this->extendId++ ] = $fnExtend;

        return $this;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T|null
     */
    public function askItem(Id $id, string $contractT = '', bool $forceInstanceOf = false, array $parametersWhenNew = []) : ?object
    {
        $paremeters = $paremeters ?? [];

        if (! $this->has($id)) {
            return null;
        }

        $instance = $this->getItem($id, $contractT, $forceInstanceOf, $parametersWhenNew);

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $contractT
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
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

        if (isset($this->instanceList[ $_id ])) {
            $instance = $this->instanceList[ $_id ];

        } else {
            [ $_resolvedId ] = $this->resolveBoundId($id);

            if (isset($this->instanceList[ $_resolvedId ])) {
                $instance = $this->instanceList[ $_resolvedId ];

            } else {
                $resolvedId = Id::from($_resolvedId);

                $instance = $this->makeItem($resolvedId, $parametersWhenNew);
            }
        }

        if (isset($this->isSingletonIndex[ $_id ])
            && ! isset($this->instanceList[ $_id ])
        ) {
            $this->instanceList[ $_id ] = $instance;
        }

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $contractT
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function makeItem(Id $id, array $parameters = [], string $contractT = '', bool $forceInstanceOf = false) : object
    {
        $id = Id::from($id);

        $_id = $id->getValue();

        [ $bound, , $boundType ] = $resolved = $this->resolveItem($id);

        if (static::BIND_TYPE_INSTANCE === $boundType) {
            $instance = clone $bound;

        } elseif (static::BIND_TYPE_CLASS === $boundType) {
            $instance = $this->autowireConstructorArray($bound, $parameters);

        } elseif (static::BIND_TYPE_FACTORY === $boundType) {
            $instance = $this->autowireUserFuncArray($bound, $parameters);

        } else {
            throw new RuntimeException(
                'Unknown `boundType` while making: '
                . $boundType
                . ' / ' . $_id
            );
        }

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $contractT
                . ' / ' . _php_dump($instance)
            );
        }

        if (isset($this->isSingletonIndex[ $_id ])
            && ! isset($this->instanceList[ $_id ])
        ) {
            $this->instanceList[ $_id ] = $instance;
        }

        $classmap = [ $_id => $_id ];

        if ($id->isContract()) {
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
                $this->autowireUserFuncArray($callable, [ $instance ]);
            }
        }

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function takeItem(Id $id, array $parametersWhenNew = [], string $contractT = '', bool $forceInstanceOf = false) : object
    {
        $paremeters = $paremeters ?? [];

        $instance = $this->has($id)
            ? $this->getItem($id, $contractT, $forceInstanceOf, $parametersWhenNew)
            : $this->makeItem($id, $parametersWhenNew);

        if ($forceInstanceOf && ! is_a($instance, $contractT)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $contractT
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

        $this->autowireUserFuncArray([ $instance, $methodName ], $methodArgs);

        return $instance;
    }


    public function autowireUserFuncArray(callable $fn, array $args = [])
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
    public function autowireConstructorArray(string $class, array $parameters = []) : object
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
        $_id = $id->getValue();

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

        } elseif (is_string($mixed) && ($mixed !== '')) {
            $mixedId = Id::tryFrom($mixed);

            if ($isAlias = ($_id !== $mixedId->getValue())) {
                $result = [ $mixedId, static::BIND_TYPE_ALIAS ];

            } elseif ($isClass = $mixedId->isClass()) {
                $result = [ $mixedId, static::BIND_TYPE_CLASS ];
            }
        }

        if (null === $result) {
            throw new LogicException(
                'Unable to ' . __FUNCTION__ . '. '
                . $_id
                . ' / ' . _php_dump($mixed)
            );
        }

        return $result;
    }


    /**
     * @return array{
     *     0: mixed,
     *     1: string,
     *     2: string,
     *     3: array<string, string>
     * }
     */
    protected function resolveItem(Id $id) : array
    {
        [ $itemId, $itemType, $itemFullpath ] = $this->resolveItemId($id);

        $itemProperty = "{$itemType}List";
        $itemPropertyValue = $this->{$itemProperty}[ $itemId ] ?? null;

        if ($isClass = ($itemType === static::BIND_TYPE_CLASS)) {
            $itemPropertyValue = $itemId;
        }

        $result = [ $itemPropertyValue, $itemId, $itemType, $itemFullpath ];

        return $result;
    }

    /**
     * @return array{
     *     0: mixed,
     *     1: string,
     *     2: string,
     *     3: array<string, string>
     * }
     */
    protected function resolveBound(Id $id) : array
    {
        [ $itemId, $itemType, $itemFullpath ] = $this->resolveBoundId($id);

        $itemProperty = "{$itemType}List";
        $itemPropertyValue = $this->{$itemProperty}[ $itemId ] ?? null;

        $result = [ $itemPropertyValue, $itemId, $itemType, $itemFullpath ];

        return $result;
    }

    /**
     * @return array{
     *     0: mixed,
     *     1: string,
     *     2: string,
     *     3: array<string, string>
     * }
     */
    protected function resolveClass(Id $id) : array
    {
        [ $itemId, $itemType, $itemFullpath ] = $this->resolveClassId($id);

        $itemPropertyValue = $itemId;

        $result = [ $itemPropertyValue, $itemId, $itemType, $itemFullpath ];

        return $result;
    }


    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: array<string, string>
     * }
     */
    protected function resolveItemId(Id $id) : array
    {
        $_id = $id->getValue();

        $result = isset($this->bindList[ $_id ])
            ? $this->resolveBoundId($id)
            : $this->resolveClassId($id);

        return $result;
    }

    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: array<string, string>
     * }
     */
    protected function resolveBoundId(Id $id) : array
    {
        $_id = $id->getValue();

        if (! isset($this->bindList[ $_id ])) {
            throw new RuntimeException(
                'Unable to ' . __FUNCTION__ . '. '
                . 'Missing `id`: ' . $_id
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

            if (static::BIND_TYPE_ALIAS !== $boundType) {
                break;
            }

            if (isset($boundPath[ $boundId ])) {
                throw new RuntimeException(
                    'Unable to ' . __FUNCTION__ . '. '
                    . 'Cyclic dependency resolving detected while resolving: '
                    . '[ ' . implode(' -> ', array_keys($boundPath)) . ' ]'
                );
            }

            $boundFullpath = $boundPath;
            $boundFullpath[ $boundId ] = $boundType;

            $boundId = $this->aliasList[ $boundId ] ?? null;
            $boundType = $this->bindList[ $boundId ] ?? null;

            if (null === $boundType) {
                $boundIdObject = Id::from($boundId);

                if ($boundIdObject->isClass()) {
                    $boundType = static::BIND_TYPE_CLASS;

                } else {
                    throw new RuntimeException(
                        'Unable to ' . __FUNCTION__ . '. '
                        . 'Missing `boundId` while making: '
                        . '[ ' . implode(' -> ', array_keys($boundFullpath)) . ' ]'
                    );
                }
            }

            $queue[] = [ $boundId, $boundType, $boundFullpath ];
        }

        $result = [ $boundId, $boundType, $boundFullpath ];

        return $result;
    }

    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: array<string, string>
     * }
     */
    protected function resolveClassId(Id $id) : array
    {
        $_id = $id->getValue();

        if (isset($this->bindList[ $_id ])) {
            throw new RuntimeException(
                'Unable to ' . __FUNCTION__ . '. '
                . 'Bind exists, so this `id` it is not a class: ' . $_id
            );
        }

        if (! $id->isClass()) {
            throw new RuntimeException(
                'Unable to ' . __FUNCTION__ . '. '
                . 'The `id` is not a class: ' . $_id
            );
        }

        $itemId = $_id;
        $itemType = static::BIND_TYPE_CLASS;
        $itemFullpath = [ $itemId => $itemType ];

        $result = [ $itemId, $itemType, $itemFullpath ];

        return $result;
    }


    protected function resolveArguments(array $reflectResult, $reflectable, array $arguments = []) : array
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
                    $id = Id::from($argReflectionTypeClass);

                    try {
                        $_arguments[ $i ] = $this->settingsResolveArgumentsUseTake
                            ? $this->takeItem($id)
                            : $this->getItem($id);
                    }
                    catch ( NotFoundException $e ) {
                        throw new NotFoundException(
                            'Missing bound `argReflectionTypeClass` to resolve parameter: '
                            . "[ {$i} ] \${$argName} : {$argReflectionTypeName}"
                            . ' / ' . _php_dump($reflectable)
                        );
                    }
                }
            }
        }

        return $_arguments;
    }
}
