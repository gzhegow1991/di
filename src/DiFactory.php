<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Injector\Injector;
use Gzhegow\Di\Reflector\ReflectorFactory;
use Gzhegow\Di\Injector\InjectorInterface;


class DiFactory implements DiFactoryInterface
{
    /**
     * @var InjectorInterface
     */
    protected $injector;


    public function __construct(InjectorInterface $injector = null)
    {
        $this->injector = $injector ?? $this->newInjector();
    }


    public function newDi() : DiInterface
    {
        $di = new Di(
            $this,
            $this->injector,
            $this->injector->getReflector()
        );

        return $di;
    }

    public function newInjector() : InjectorInterface
    {
        $reflector = (new ReflectorFactory())->newReflector();

        $injector = new Injector($reflector);

        return $injector;
    }


    public function newLazyServiceAsk($id, array $parameters = null) : LazyService
    {
        $parameters = $parameters ?? [];

        $lazyService = new LazyService($id, [ $this, 'lazyServiceFnFactoryAsk' ], $parameters);

        return $lazyService;
    }

    public function newLazyServiceGet($id) : LazyService
    {
        $lazyService = new LazyService($id, [ $this, 'lazyServiceFnFactoryGet' ]);

        return $lazyService;
    }

    public function newLazyServiceMake($id, array $parameters = null) : LazyService
    {
        $parameters = $parameters ?? [];

        $lazyService = new LazyService($id, [ $this, 'lazyServiceFnFactoryMake' ], $parameters);

        return $lazyService;
    }


    public function lazyServiceFnFactoryAsk($lazyId, array $parameters = null) // : object
    {
        $lazyId = Id::from($lazyId);
        $parameters = $parameters ?? [];

        $instance = $this->injector->askItem($lazyId, $parameters);

        return $instance;
    }

    public function lazyServiceFnFactoryGet($lazyId) // : object
    {
        $lazyId = Id::from($lazyId);

        $instance = $this->injector->getItem($lazyId);

        return $instance;
    }

    public function lazyServiceFnFactoryMake($lazyId, array $parameters = null) // : object
    {
        $lazyId = Id::from($lazyId);
        $parameters = $parameters ?? [];

        $instance = $this->injector->makeItem($lazyId, $parameters);

        return $instance;
    }
}
