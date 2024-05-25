<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Reflector\Reflector;
use Gzhegow\Di\Exception\LogicException;
use Gzhegow\Di\Exception\RuntimeException;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


class Di implements DiInterface
{
    const BIND_TYPE_ALIAS    = 'alias';
    const BIND_TYPE_STRUCT   = 'struct';
    const BIND_TYPE_FACTORY  = 'factory';
    const BIND_TYPE_INSTANCE = 'instance';

    const LIST_BIND_TYPE = [
        self::BIND_TYPE_ALIAS    => true,
        self::BIND_TYPE_STRUCT   => true,
        self::BIND_TYPE_FACTORY  => true,
        self::BIND_TYPE_INSTANCE => true,
    ];


    /**
     * @var array<string, string>
     */
    protected $bindList = [];


    /**
     * @var array<string, string>
     */
    protected $structList = [];

    /**
     * @var array<string, string>
     */
    protected $aliasList = [];

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


    protected function getReflector() : Reflector
    {
        return _reflector();
    }


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
    public function setCacheSettings(array $settings = null) // : static
    {
        $theReflector = $this->getReflector();

        $cacheMode = $settings[ 'reflectorCacheMode' ] ?? $settings[ 0 ] ?? null;
        $cacheAdapter = $settings[ 'reflectorCacheAdapter' ] ?? $settings[ 1 ] ?? null;
        $cacheDirpath = $settings[ 'reflectorCacheDirpath' ] ?? $settings[ 2 ] ?? null;
        $cacheFilename = $settings[ 'reflectorCacheFilename' ] ?? $settings[ 3 ] ?? null;

        $theReflector->setCacheSettings(
            $cacheMode,
            $cacheAdapter,
            $cacheDirpath,
            $cacheFilename
        );

        return $this;
    }

    public function clearCache() // : static
    {
        $theReflector = $this->getReflector();

        $theReflector->clearCache();

        return $this;
    }

    public function flushCache() // : static
    {
        $theReflector = $this->getReflector();

        $theReflector->flushCache();

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

            $this->bindDependency($bindType, $bindId, $bindObject, $isSingleton);
        }

        foreach ( $di->extendList as $_extendId => $callables ) {
            $extendId = Id::from($_extendId);

            foreach ( $callables as $callable ) {
                $this->extendDependency($extendId, $callable);
            }
        }

        return $this;
    }


    /**
     * @param string $id
     */
    public function has($id) : bool
    {
        $status = $this->hasBound($id);

        return $status;
    }


    public function bind($id, $mixed = null, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);

        [ $_mixed, $bindType ] = $this->resolveBind($id, $mixed);

        $this->bindDependency($bindType, $id, $_mixed, $isSingleton);

        return $this;
    }

    public function bindSingleton($id, $mixed = null) // : static
    {
        $this->bind($id, $mixed, true);

        return $this;
    }


    public function bindAlias($id, $aliasId, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);
        $aliasId = Id::from($aliasId);

        $this->bindDependencyAlias($id, $aliasId, $isSingleton);

        return $this;
    }

    /**
     * @param class-string $structId
     */
    public function bindStruct($id, $structId, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);
        $structId = Id::from($structId);

        $this->bindDependencyStruct($id, $structId, $isSingleton);

        return $this;
    }

    public function bindInstance($id, object $instance, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);

        $this->bindDependencyInstance($id, $instance, $isSingleton);

        return $this;
    }

    /**
     * @param callable $fnFactory
     */
    public function bindFactory($id, $fnFactory, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);

        $this->bindDependencyFactory($id, $fnFactory, $isSingleton);

        return $this;
    }


    /**
     * @param callable $fnExtend
     */
    public function extend($id, $fnExtend) // : static
    {
        $id = Id::from($id);

        $this->extendDependency($id, $fnExtend);

        return $this;
    }


    /**
     * @param string $id
     *
     * @return object
     */
    public function ask($id, array $parameters = null) // : object
    {
        $parameters = $parameters ?? [];

        $_id = Id::from($id);

        $instance = $this->askDependency($_id, $parameters);

        return $instance;
    }

    /**
     * @param string $id
     *
     * @return object
     *
     * @throws NotFoundException
     */
    public function get($id) // : object
    {
        $_id = Id::from($id);

        $instance = $this->getDependency($_id);

        return $instance;
    }

    /**
     * @param string $id
     *
     * @return object
     */
    public function make($id, array $parameters = null) // : object
    {
        $parameters = $parameters ?? [];

        $_id = Id::from($id);

        $instance = $this->makeDependency($_id, $parameters);

        return $instance;
    }


    /**
     * @return LazyService
     */
    public function askLazy(string $id, array $parameters = null) // : LazyService
    {
        $parameters = $parameters ?? [];

        $_id = Id::from($id);

        $instance = $this->askDependencyLazy($_id, $parameters);

        return $instance;
    }

    /**
     * @return LazyService
     *
     * @throws NotFoundException
     */
    public function getLazy(string $id) // : LazyService
    {
        $_id = Id::from($id);

        $instance = $this->getDependencyLazy($_id);

        return $instance;
    }

    /**
     * @return LazyService
     */
    public function makeLazy(string $id, array $parameters = null) // : LazyService
    {
        $parameters = $parameters ?? [];

        $_id = Id::from($id);

        $instance = $this->makeDependencyLazy($_id, $parameters);

        return $instance;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     */
    public function askGeneric(string $id, array $parameters = null, $structT = null, bool $forceInstanceOf = null) // : object
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $_id = Id::from($id);

        $instance = $this->askDependencyGeneric($_id, $parameters, $structT, $forceInstanceOf);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function getGeneric(string $id, $structT = null, bool $forceInstanceOf = null) // : object
    {
        $structT = _filter_string($structT) ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $_id = Id::from($id);

        $instance = $this->getDependencyGeneric($_id, $structT, $forceInstanceOf);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     */
    public function makeGeneric(string $id, array $parameters = null, $structT = null, bool $forceInstanceOf = null) // : object
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $_id = Id::from($id);

        $instance = $this->makeDependencyGeneric($_id, $parameters, $structT, $forceInstanceOf);

        return $instance;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     */
    public function askLazyGeneric(string $id, array $parameters = null, $structT = null) // : LazyService
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';

        $_id = Id::from($id);

        $instance = $this->askDependencyLazyGeneric($_id, $parameters, $structT);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     *
     * @throws NotFoundException
     */
    public function getLazyGeneric(string $id, $structT = null) // : LazyService
    {
        $structT = _filter_string($structT) ?? '';

        $_id = Id::from($id);

        $instance = $this->getDependencyLazyGeneric($_id, $structT);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     */
    public function makeLazyGeneric(string $id, array $parameters = null, $structT = null) // : LazyService
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';

        $_id = Id::from($id);

        $instance = $this->makeDependencyLazyGeneric($_id, $parameters, $structT);

        return $instance;
    }


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowire(object $instance, array $methodArgs = null, string $methodName = null) // : object
    {
        $methodArgs = $methodArgs ?? [];
        $methodName = $methodName ?? '';

        $this->autowireDependency($instance, $methodArgs, $methodName);

        return $instance;
    }


    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public function call($fn, array $args = null) // : mixed
    {
        $args = $args ?? [];

        $result = $this->autowireCallFunction($fn, $args);

        return $result;
    }


    protected function hasBound($id, Id &$result = null) : bool
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


    /**
     * @param static::BIND_TYPE_ALIAS|static::BIND_TYPE_STRUCT|static::BIND_TYPE_FACTORY|static::BIND_TYPE_INSTANCE|static::BIND_TYPE_LAZY $type
     * @param callable|object|array|class-string                                                                                           $mixed
     */
    protected function bindDependency(string $type, Id $id, $mixed = null, bool $isSingleton = false) : void
    {
        if ($this->hasBound($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        switch ( $type ):
            case static::BIND_TYPE_ALIAS:
                $aliasId = Id::from($mixed);

                $this->bindDependencyAlias($id, $aliasId, $isSingleton);

                break;

            case static::BIND_TYPE_STRUCT:
                $structId = Id::from($mixed);

                $this->bindDependencyStruct($id, $structId, $isSingleton);

                break;

            case static::BIND_TYPE_INSTANCE:
                $this->bindDependencyInstance($id, $mixed, $isSingleton);

                break;

            case static::BIND_TYPE_FACTORY:
                $this->bindDependencyFactory($id, $mixed, $isSingleton);

                break;

            default:
                throw new LogicException(
                    'The `mixed` should be callable|object|class-string: '
                    . _php_dump($mixed)
                );

        endswitch;
    }

    protected function bindDependencyAlias(Id $id, Id $aliasId, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $_id = $id->getValue();
        $_alias = $aliasId->getValue();

        if ($_id === $_alias) {
            throw new LogicException(
                'The `id` should be not equal to `aliasId`: '
                . $_id
                . ' / ' . $_alias
            );
        }

        $this->bindList[ $_id ] = static::BIND_TYPE_ALIAS;
        $this->aliasList[ $_id ] = $_alias;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    protected function bindDependencyStruct(Id $id, Id $structId, bool $isSingleton = false) // : static
    {
        $_id = $id->getValue();
        $_structId = $structId->getValue();

        if (! $structId->isStruct()) {
            throw new LogicException(
                'The `structId` should be existing class or interface: ' . $_structId
            );
        }

        $this->bindList[ $_id ] = static::BIND_TYPE_STRUCT;
        $this->structList[ $_id ] = $_structId;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    protected function bindDependencyInstance(Id $id, object $instance, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $_id = $id->getValue();

        $this->bindList[ $_id ] = static::BIND_TYPE_INSTANCE;
        $this->instanceList[ $_id ] = $instance;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }

    protected function bindDependencyFactory(Id $id, callable $fnFactory, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $_id = $id->getValue();

        $this->bindList[ $_id ] = static::BIND_TYPE_FACTORY;
        $this->factoryList[ $_id ] = $fnFactory;

        $_id = $id->getValue();

        if ($isSingleton) {
            $this->isSingletonIndex[ $_id ] = true;
        }

        return $this;
    }


    protected function extendDependency(Id $id, callable $fnExtend) : void
    {
        $_id = $id->getValue();

        $this->extendList[ $_id ][ $this->extendId++ ] = $fnExtend;
    }


    protected function askDependency(Id $id, array $parameters = []) : object
    {
        $instance = $this->hasBound($id)
            ? $this->getDependency($id, $parameters)
            : $this->makeDependency($id, $parameters);

        return $instance;
    }

    /**
     * @throws NotFoundException
     */
    protected function getDependency(Id $id, array $parameters = []) : object
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
                ?? $this->makeDependency($aliasId, $parameters);

            if (isset($this->isSingletonIndex[ $_id ])) {
                $this->instanceList[ $_id ] = $instance;
            }
        }

        return $instance;
    }

    protected function makeDependency(Id $id, array $parameters = []) : object
    {
        $id = Id::from($id);

        $_id = $id->getValue();

        [ $bound, $boundId, $boundType ] = $this->resolveDependency($id);

        if (static::BIND_TYPE_INSTANCE === $boundType) {
            $instance = clone $bound;

        } elseif (static::BIND_TYPE_STRUCT === $boundType) {
            $instance = $this->autowireNewInstance($bound, $parameters);

        } elseif (static::BIND_TYPE_FACTORY === $boundType) {
            $instance = $this->autowireCallFunction($bound, $parameters);

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

            foreach ( $callablesOrdered as $callable ) {
                $this->autowireCallFunction($callable, [ $instance ]);
            }
        }

        return $instance;
    }


    protected function askDependencyLazy(Id $id, array $parameters = []) : LazyService
    {
        $lazyService = $this->newLazyServiceAsk($id, $parameters);

        return $lazyService;
    }

    /**
     * @throws NotFoundException
     */
    protected function getDependencyLazy(Id $id) : LazyService
    {
        $lazyService = $this->newLazyServiceGet($id);

        return $lazyService;
    }

    protected function makeDependencyLazy(Id $id, array $parameters = []) : LazyService
    {
        $lazyService = $this->newLazyServiceMake($id, $parameters);

        return $lazyService;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $classT
     *
     * @return T
     */
    protected function askDependencyGeneric(Id $id, array $paremeters = [], string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->askDependency($id, $paremeters);

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
    protected function getDependencyGeneric(Id $id, string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->getDependency($id);

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
    protected function makeDependencyGeneric(Id $id, array $parameters = [], string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->makeDependency($id, $parameters);

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
     * @param class-string<T>|T $classT
     *
     * @return LazyService<T>|T
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function askDependencyLazyGeneric(Id $id, array $parameters = [], string $classT = '') : LazyService
    {
        $instance = $this->askDependencyLazy($id, $parameters);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T $classT
     *
     * @return LazyService<T>|T
     *
     * @throws NotFoundException
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getDependencyLazyGeneric(Id $id, string $classT = '') : LazyService
    {
        $instance = $this->getDependencyLazy($id);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T $classT
     *
     * @return LazyService<T>|T
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function makeDependencyLazyGeneric(Id $id, array $parameters = [], string $classT = '') : LazyService
    {
        $instance = $this->makeDependencyLazy($id, $parameters);

        return $instance;
    }


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    protected function autowireDependency(object $instance, array $methodArgs = [], string $methodName = '') : object
    {
        $methodName = $methodName ?: '__autowire';

        $this->autowireCallFunction([ $instance, $methodName ], $methodArgs);

        return $instance;
    }

    protected function autowireCallFunction(callable $fn, array $args = [])
    {
        $theReflector = $this->getReflector();

        $reflectResult = $theReflector->reflectArgumentsCallable($fn);

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
    protected function autowireNewInstance(string $class, array $parameters = []) : object
    {
        $theReflector = $this->getReflector();

        $reflectResult = $theReflector->reflectArgumentsConstructor($class);

        $arguments = $this->resolveArguments($reflectResult, $class, $parameters);

        $instance = new $class(...$arguments);

        return $instance;
    }


    protected function resolveBind(Id $id, $mixed) : array
    {
        $_id = $id->getValue();

        if (is_callable($mixed)) {
            $fnFactory = $mixed;

            return [ $fnFactory, static::BIND_TYPE_FACTORY ];

        } elseif (is_object($mixed)) {
            $instance = $mixed;

            return [ $instance, static::BIND_TYPE_INSTANCE ];

        } elseif (is_string($mixed)) {
            $stringId = Id::from($mixed);

            $_stringId = $stringId->getValue();

            $isAlias = ($_id !== $_stringId);

            if ($isAlias) {
                return [ $stringId, static::BIND_TYPE_ALIAS ];

            } elseif ($stringId->isStruct()) {
                return [ $stringId, static::BIND_TYPE_STRUCT ];
            }

        } elseif (null === $mixed) {
            if ($id->isStruct()) {
                return [ $id, static::BIND_TYPE_STRUCT ];
            }
        }

        throw new LogicException(
            'Unable to resolve `bindType`: '
            . $_id
            . ' / ' . _php_dump($mixed)
        );
    }


    protected function resolveDependency(Id $id) : array
    {
        $dependencyDefinition = $this->resolveDependencyId($id);

        [ $dependencyId, $dependencyType, $dependencyFullpath ] = $dependencyDefinition;

        $dependencyProperty = "{$dependencyType}List";
        $dependencyMixed = $this->{$dependencyProperty}[ $dependencyId ] ?? $dependencyId;

        return [ $dependencyMixed, $dependencyId, $dependencyType, $dependencyFullpath ];
    }

    protected function resolveDependencyId(Id $id) : array
    {
        $_id = $id->getValue();

        if (! isset($this->bindList[ $_id ])) {
            $dependencyId = $_id;
            $dependencyType = static::BIND_TYPE_STRUCT;
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


    protected function resolveDependencyBound(Id $id) : array
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

    protected function resolveDependencyBoundId(Id $id) : array
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

            if ($boundType !== static::BIND_TYPE_ALIAS) {
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

                $boundType = static::BIND_TYPE_STRUCT;
            }

            $queue[] = [ $boundId, $boundType, $boundFullpath ];
        }

        return [ $boundId, $boundType, $boundFullpath ];
    }


    protected function resolveArguments($reflectResult, $reflectable, array $arguments = []) : array
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

                    $_arguments[ $i ] = $this->getDependency($id);
                }
            }
        }

        return $_arguments;
    }



    public function newLazyServiceAsk($id, array $parameters = null) : LazyService
    {
        $parameters = $parameters ?? [];

        $lazyService = isset(static::$instances[ static::class ])
            ? new LazyService($id, [ static::class, 'lazyServiceFnFactoryAskStatic' ], $parameters)
            : new LazyService($id, [ $this, 'lazyServiceFnFactoryAskPublic' ], $parameters);

        return $lazyService;
    }

    public function newLazyServiceGet($id) : LazyService
    {
        $lazyService = isset(static::$instances[ static::class ])
            ? new LazyService($id, [ static::class, 'lazyServiceFnFactoryGetStatic' ])
            : new LazyService($id, [ $this, 'lazyServiceFnFactoryGetPublic' ]);

        return $lazyService;
    }

    public function newLazyServiceMake($id, array $parameters = null) : LazyService
    {
        $parameters = $parameters ?? [];

        $lazyService = isset(static::$instances[ static::class ])
            ? new LazyService($id, [ static::class, 'lazyServiceFnFactoryMakeStatic' ], $parameters)
            : new LazyService($id, [ $this, 'lazyServiceFnFactoryMakePublic' ], $parameters);

        return $lazyService;
    }


    public function lazyServiceFnFactoryAskPublic($lazyId, array $parameters = null) : object
    {
        $instance = $this->ask($lazyId, $parameters);

        return $instance;
    }

    public function lazyServiceFnFactoryGetPublic($lazyId) : object
    {
        $instance = $this->get($lazyId);

        return $instance;
    }

    public function lazyServiceFnFactoryMakePublic($lazyId, array $parameters = null) : object
    {
        $instance = $this->make($lazyId, $parameters);

        return $instance;
    }


    public static function lazyServiceFnFactoryAskStatic($lazyId, array $parameters = []) : object
    {
        $instance = static::getInstance()->lazyServiceFnFactoryAskPublic($lazyId, $parameters);

        return $instance;
    }

    public static function lazyServiceFnFactoryGetStatic($lazyId) : object
    {
        $instance = static::getInstance()->lazyServiceFnFactoryGetPublic($lazyId);

        return $instance;
    }

    public static function lazyServiceFnFactoryMakeStatic($lazyId, array $parameters = []) : object
    {
        $instance = static::getInstance()->lazyServiceFnFactoryMakePublic($lazyId, $parameters);

        return $instance;
    }


    public static function getInstance() // : static
    {
        return static::$instances[ static::class ] = static::$instances[ static::class ] ?? new static();
    }

    /**
     * @param static $di
     *
     * @return void
     */
    public static function setInstance($di) : void
    {
        if (! is_a($di, static::class)) {
            throw new RuntimeException(
                'The `di` should be instance of: ' . static::class
                . ' / ' . _php_dump($di)
            );
        }

        static::$instances[ get_class($di) ] = $di;
    }

    /**
     * @var array<class-string, static>
     */
    protected static $instances = [];
}
