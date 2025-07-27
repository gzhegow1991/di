<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\LazyService\DiLazyService;
use Gzhegow\Di\Injector\DiInjectorInterface;
use Gzhegow\Di\Reflector\DiReflectorInterface;
use Gzhegow\Di\Exception\Runtime\NotFoundException;
use Gzhegow\Di\LazyService\DiLazyServiceFactoryInterface;


class DiFacade implements DiInterface
{
    /**
     * @var DiFactoryInterface
     */
    protected $factory;

    /**
     * @var DiInjectorInterface
     */
    protected $injector;
    /**
     * @var DiReflectorInterface
     */
    protected $reflector;

    /**
     * @var DiLazyServiceFactoryInterface
     */
    protected $lazyServiceFactory;

    /**
     * @var DiConfig
     */
    protected $config;


    public function __construct(
        DiFactoryInterface $factory,
        //
        DiInjectorInterface $injector,
        DiReflectorInterface $reflector,
        //
        DiConfig $config
    )
    {
        $this->factory = $factory;

        $this->injector = $injector;
        $this->reflector = $reflector;

        $this->config = $config;
        $this->config->validate();
    }


    public function resetCache() : DiInterface
    {
        $this->reflector->resetCache();

        return $this;
    }

    public function saveCache() : DiInterface
    {
        $this->reflector->saveCache();

        return $this;
    }

    public function clearCache() : DiInterface
    {
        $this->reflector->clearCache();

        return $this;
    }


    public function merge(DiInterface $di) : DiInterface
    {
        $this->injector->merge($di->injector);

        return $this;
    }


    /**
     * @param string $id
     */
    public function has($id, ?Id &$result = null) : bool
    {
        $status = $this->injector->has($id, $result);

        return $status;
    }


    public function bind($id, $mixed = null, ?bool $isSingleton = null) : DiInterface
    {
        $isSingleton = $isSingleton ?? false;

        $id = Id::from($id)->orThrow();

        $this->injector->bindItemAuto($id, $mixed, $isSingleton);

        return $this;
    }

    public function bindSingleton($id, $mixed = null) : DiInterface
    {
        $this->bind($id, $mixed, true);

        return $this;
    }


    public function bindAlias($id, $idOrAlias, ?bool $isSingleton = null) : DiInterface
    {
        $isSingleton = $isSingleton ?? false;

        $idValid = Id::from($id)->orThrow();
        $idOrAliasValid = Id::from($idOrAlias)->orThrow();

        $this->injector->bindItemAlias($idValid, $idOrAliasValid, $isSingleton);

        return $this;
    }

    /**
     * @param class-string $idOrClass
     */
    public function bindClass($id, $idOrClass, ?bool $isSingleton = null) : DiInterface
    {
        $isSingleton = $isSingleton ?? false;

        $idValid = Id::from($id)->orThrow();
        $idOrClassValid = Id::from($idOrClass)->orThrow();

        $this->injector->bindItemClass($idValid, $idOrClassValid, $isSingleton);

        return $this;
    }

    /**
     * @param callable $fnFactory
     */
    public function bindFactory($id, $fnFactory, ?bool $isSingleton = null) : DiInterface
    {
        $isSingleton = $isSingleton ?? false;

        $idValid = Id::from($id)->orThrow();

        $this->injector->bindItemFactory($idValid, $fnFactory, $isSingleton);

        return $this;
    }

    public function bindInstance($id, object $instance, ?bool $isSingleton = null) : DiInterface
    {
        $isSingleton = $isSingleton ?? false;

        $idValid = Id::from($id)->orThrow();

        $this->injector->bindItemInstance($idValid, $instance, $isSingleton);

        return $this;
    }


    /**
     * @param callable $fnExtend
     */
    public function extend($id, $fnExtend) : DiInterface
    {
        $idValid = Id::from($id)->orThrow();

        $this->injector->extendItem($idValid, $fnExtend);

        return $this;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T|null
     */
    public function ask($id, ?string $contractT = null, ?bool $forceInstanceOf = null, ?array $parametersWhenNew = null) : ?object
    {
        $parametersWhenNew = $parametersWhenNew ?? [];
        $contractT = $contractT ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $idValid = Id::from($id)->orThrow();

        $instance = $this->injector->askItem($idValid, $contractT, $forceInstanceOf, $parametersWhenNew);

        return $instance;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     *
     * @throws NotFoundException
     */
    public function get($id, ?string $contractT = null, ?bool $forceInstanceOf = null, ?array $parametersWhenNew = null) : object
    {
        $parametersWhenNew = $parametersWhenNew ?? [];
        $contractT = $contractT ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $idValid = Id::from($id)->orThrow();

        $instance = $this->injector->getItem($idValid, $contractT, $forceInstanceOf, $parametersWhenNew);

        return $instance;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function make($id, ?array $parameters = null, ?string $contractT = null, ?bool $forceInstanceOf = null) : object
    {
        $parameters = $parameters ?? [];
        $contractT = $contractT ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $idValid = Id::from($id)->orThrow();

        $instance = $this->injector->makeItem($idValid, $parameters, $contractT, $forceInstanceOf);

        return $instance;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function take($id, ?array $parametersWhenNew = null, ?string $contractT = null, ?bool $forceInstanceOf = null) : object
    {
        $parametersWhenNew = $parametersWhenNew ?? [];
        $contractT = $contractT ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $idValid = Id::from($id)->orThrow();

        $instance = $this->injector->takeItem($idValid, $parametersWhenNew, $contractT, $forceInstanceOf);

        return $instance;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public function fetch($id, ?array $parametersWhenNew = null, ?string $contractT = null, ?bool $forceInstanceOf = null) : object
    {
        $parametersWhenNew = $parametersWhenNew ?? [];
        $contractT = $contractT ?? '';
        $forceInstanceOf = $forceInstanceOf ?? false;

        $idValid = Id::from($id)->orThrow();

        $instance = $this->injector->fetchItem($idValid, $parametersWhenNew, $contractT, $forceInstanceOf);

        return $instance;
    }


    public function getLazyServiceFactory() : DiLazyServiceFactoryInterface
    {
        return $this->lazyServiceFactory = null
            ?? $this->lazyServiceFactory
            ?? $this->factory->newLazyServiceFactory($this);
    }

    public function setLazyServiceFactory(?DiLazyServiceFactoryInterface $lazyServiceFactory) : DiInterface
    {
        $this->lazyServiceFactory = $lazyServiceFactory;

        return $this;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     *
     * @throws NotFoundException
     */
    public function getLazy($id, ?string $contractT = null, ?array $parametersWhenNew = null) : DiLazyService
    {
        $parametersWhenNew = $parametersWhenNew ?? [];
        $contractT = $contractT ?? '';

        $idValid = Id::from($id)->orThrow();

        $lazyService = $this->getItemLazy($idValid, $contractT, $parametersWhenNew);

        return $lazyService;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public function makeLazy($id, ?array $parameters = null, ?string $contractT = null) : DiLazyService
    {
        $parameters = $parameters ?? [];
        $contractT = $contractT ?? '';

        $idValid = Id::from($id)->orThrow();

        $lazyService = $this->makeItemLazy($idValid, $parameters, $contractT);

        return $lazyService;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public function takeLazy($id, ?array $parametersWhenNew = null, ?string $contractT = null) : DiLazyService
    {
        $parametersWhenNew = $parametersWhenNew ?? [];
        $contractT = $contractT ?? '';

        $idValid = Id::from($id)->orThrow();

        $lazyService = $this->takeItemLazy($idValid, $parametersWhenNew, $contractT);

        return $lazyService;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public function fetchLazy($id, ?array $parametersWhenNew = null, ?string $contractT = null) : DiLazyService
    {
        $parametersWhenNew = $parametersWhenNew ?? [];
        $contractT = $contractT ?? '';

        $idValid = Id::from($id)->orThrow();

        $lazyService = $this->fetchItemLazy($idValid, $parametersWhenNew, $contractT);

        return $lazyService;
    }


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public function autowireInstance(object $instance, ?array $methodArgs = null, ?string $methodName = null)
    {
        $methodArgs = $methodArgs ?? [];
        $methodName = $methodName ?? '';

        $this->injector->autowireInstance($instance, $methodArgs, $methodName);

        return $instance;
    }


    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public function callUserFuncAutowired($fn, ...$args)
    {
        $result = $this->injector->callUserFuncAutowired($fn, ...$args);

        return $result;
    }

    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public function callUserFuncArrayAutowired($fn, ?array $args = null)
    {
        $args = $args ?? [];

        $result = $this->injector->callUserFuncArrayAutowired($fn, $args);

        return $result;
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $contractT
     *
     * @return DiLazyService<T>|T
     *
     * @throws NotFoundException
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getItemLazy(Id $id, string $contractT = '', array $parametersWhenNew = []) : DiLazyService
    {
        $theLazyServiceFactory = $this->getLazyServiceFactory();

        $lazyService = $theLazyServiceFactory->newLazyServiceGet($id, $parametersWhenNew);

        return $lazyService;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $contractT
     *
     * @return DiLazyService<T>|T
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function makeItemLazy(Id $id, array $parameters = [], string $contractT = '') : DiLazyService
    {
        $theLazyServiceFactory = $this->getLazyServiceFactory();

        $lazyService = $theLazyServiceFactory->newLazyServiceMake($id, $parameters);

        return $lazyService;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $contractT
     *
     * @return DiLazyService<T>|T
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function takeItemLazy(Id $id, array $parametersWhenNew = [], string $contractT = '') : DiLazyService
    {
        $theLazyServiceFactory = $this->getLazyServiceFactory();

        $lazyService = $theLazyServiceFactory->newLazyServiceTake($id, $parametersWhenNew);

        return $lazyService;
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $contractT
     *
     * @return DiLazyService<T>|T
     *
     * @noinspection PhpUnusedParameterInspection
     */
    protected function fetchItemLazy(Id $id, array $parametersWhenNew = [], string $contractT = '') : DiLazyService
    {
        $theLazyServiceFactory = $this->getLazyServiceFactory();

        $lazyService = $theLazyServiceFactory->newLazyServiceFetch($id, $parametersWhenNew);

        return $lazyService;
    }
}
