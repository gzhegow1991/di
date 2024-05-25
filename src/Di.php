<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Reflector\Reflector;
use Gzhegow\Di\Exception\RuntimeException;
use Gzhegow\Di\Injector\InjectorInterface;
use Gzhegow\Di\Reflector\ReflectorInterface;
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
     * @var DiFactoryInterface
     */
    protected $factory;
    /**
     * @var InjectorInterface
     */
    protected $injector;
    /**
     * @var ReflectorInterface
     */
    protected $reflector;


    public function __construct(
        DiFactoryInterface $factory,
        InjectorInterface $injector,
        ReflectorInterface $reflector
    )
    {
        $this->factory = $factory;
        $this->injector = $injector;
        $this->reflector = $reflector;
    }


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
    public function setCacheSettings(array $settings = null) // : static
    {
        $cacheMode = $settings[ 'reflectorCacheMode' ] ?? $settings[ 0 ] ?? null;
        $cacheAdapter = $settings[ 'reflectorCacheAdapter' ] ?? $settings[ 1 ] ?? null;
        $cacheDirpath = $settings[ 'reflectorCacheDirpath' ] ?? $settings[ 2 ] ?? null;
        $cacheFilename = $settings[ 'reflectorCacheFilename' ] ?? $settings[ 3 ] ?? null;

        $this->reflector->setCacheSettings(
            $cacheMode,
            $cacheAdapter,
            $cacheDirpath,
            $cacheFilename
        );

        return $this;
    }

    public function clearCache() // : static
    {
        $this->reflector->clearCache();

        return $this;
    }

    public function flushCache() // : static
    {
        $this->reflector->flushCache();

        return $this;
    }


    /**
     * @return static
     */
    public function merge(InjectorInterface $di) // : static
    {
        $this->injector->merge($di);

        return $this;
    }


    /**
     * @param string $id
     */
    public function has($id) : bool
    {
        $status = $this->injector->hasBound($id);

        return $status;
    }


    public function bind($id, $mixed = null, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);

        [ $_mixed, $bindType ] = $this->injector->resolveBind($id, $mixed);

        $this->injector->bindItem($bindType, $id, $_mixed, $isSingleton);

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

        $this->injector->bindItemAlias($id, $aliasId, $isSingleton);

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

        $this->injector->bindItemStruct($id, $structId, $isSingleton);

        return $this;
    }

    /**
     * @param callable $fnFactory
     */
    public function bindFactory($id, $fnFactory, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);

        $this->injector->bindItemFactory($id, $fnFactory, $isSingleton);

        return $this;
    }

    public function bindInstance($id, object $instance, bool $isSingleton = null) // : static
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id);

        $this->injector->bindItemInstance($id, $instance, $isSingleton);

        return $this;
    }


    /**
     * @param callable $fnExtend
     */
    public function extend($id, $fnExtend) // : static
    {
        $id = Id::from($id);

        $this->injector->extendItem($id, $fnExtend);

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

        $instance = $this->injector->askItem($_id, $parameters);

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

        $instance = $this->injector->getItem($_id);

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

        $instance = $this->injector->makeItem($_id, $parameters);

        return $instance;
    }


    /**
     * @return LazyService
     */
    public function askLazy($id, array $parameters = null) // : LazyService
    {
        $parameters = $parameters ?? [];

        $_id = Id::from($id);

        $instance = $this->askItemLazy($_id, $parameters);

        return $instance;
    }

    /**
     * @return LazyService
     *
     * @throws NotFoundException
     */
    public function getLazy($id) // : LazyService
    {
        $_id = Id::from($id);

        $instance = $this->getItemLazy($_id);

        return $instance;
    }

    /**
     * @return LazyService
     */
    public function makeLazy($id, array $parameters = null) // : LazyService
    {
        $parameters = $parameters ?? [];

        $_id = Id::from($id);

        $instance = $this->makeItemLazy($_id, $parameters);

        return $instance;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     */
    public function askGeneric($id, array $parameters = null, $structT = null, bool $forceInstanceOf = null) // : object
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $_id = Id::from($id);

        $instance = $this->injector->askItemGeneric($_id, $parameters, $structT, $forceInstanceOf);

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
    public function getGeneric($id, $structT = null, bool $forceInstanceOf = null) // : object
    {
        $structT = _filter_string($structT) ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $_id = Id::from($id);

        $instance = $this->getItemGeneric($_id, $structT, $forceInstanceOf);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $structT
     *
     * @return T
     */
    public function makeGeneric($id, array $parameters = null, $structT = null, bool $forceInstanceOf = null) // : object
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $_id = Id::from($id);

        $instance = $this->makeItemGeneric($_id, $parameters, $structT, $forceInstanceOf);

        return $instance;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     */
    public function askLazyGeneric($id, array $parameters = null, $structT = null) // : LazyService
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';

        $_id = Id::from($id);

        $instance = $this->askItemLazyGeneric($_id, $parameters, $structT);

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
    public function getLazyGeneric($id, $structT = null) // : LazyService
    {
        $structT = _filter_string($structT) ?? '';

        $_id = Id::from($id);

        $instance = $this->getItemLazyGeneric($_id, $structT);

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T>|T|null $structT
     *
     * @return LazyService<T>|T
     */
    public function makeLazyGeneric($id, array $parameters = null, $structT = null) // : LazyService
    {
        $parameters = $parameters ?? [];
        $structT = _filter_string($structT) ?? '';

        $_id = Id::from($id);

        $instance = $this->makeItemLazyGeneric($_id, $parameters, $structT);

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

        $this->injector->autowireItem($instance, $methodArgs, $methodName);

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

        $result = $this->injector->autowireFunctionCall($fn, $args);

        return $result;
    }


    protected function askItemLazy(Id $id, array $parameters = []) : LazyService
    {
        $lazyService = $this->factory->newLazyServiceAsk($id, $parameters);

        return $lazyService;
    }

    /**
     * @throws NotFoundException
     */
    protected function getItemLazy(Id $id) : LazyService
    {
        $lazyService = $this->factory->newLazyServiceGet($id);

        return $lazyService;
    }

    protected function makeItemLazy(Id $id, array $parameters = []) : LazyService
    {
        $lazyService = $this->factory->newLazyServiceMake($id, $parameters);

        return $lazyService;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T>|null $classT
     *
     * @return T
     */
    protected function askItemGeneric(Id $id, array $paremeters = [], string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->injector->askItem($id, $paremeters);

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
    protected function getItemGeneric(Id $id, string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->injector->getItem($id);

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
    protected function makeItemGeneric(Id $id, array $parameters = [], string $classT = '', bool $forceInstanceOf = false) : object
    {
        $instance = $this->injector->makeItem($id, $parameters);

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
    protected function askItemLazyGeneric(Id $id, array $parameters = [], string $classT = '') : LazyService
    {
        $instance = $this->askItemLazy($id, $parameters);

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
    protected function getItemLazyGeneric(Id $id, string $classT = '') : LazyService
    {
        $instance = $this->getItemLazy($id);

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
    protected function makeItemLazyGeneric(Id $id, array $parameters = [], string $classT = '') : LazyService
    {
        $instance = $this->makeItemLazy($id, $parameters);

        return $instance;
    }


    public static function getInstance() // : static
    {
        $instance = static::$instances[ static::class ];

        if (! is_a($instance, static::class)) {
            throw new RuntimeException(
                'No instance bound. Please, call Di::setInstance() first.'
            );
        }

        return $instance;
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
