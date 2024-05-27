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


    public function newLazyServiceGet($lazyId, array $parametersWhenNew = null) : LazyService
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $lazyService = new LazyService($lazyId, [ $this, 'lazyServiceFnFactoryGet' ], $parametersWhenNew);

        return $lazyService;
    }

    public function newLazyServiceMake($lazyId, array $parameters = null) : LazyService
    {
        $lazyId = Id::from($lazyId);
        $parameters = $parameters ?? [];

        $lazyService = new LazyService($lazyId, [ $this, 'lazyServiceFnFactoryMake' ], $parameters);

        return $lazyService;
    }


    /**
     * @return object
     */
    public function lazyServiceFnFactoryGet($lazyId, array $parametersWhenNew = null) // : object
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $instance = $this->injector->getItem($lazyId, '', false, $parametersWhenNew);

        return $instance;
    }

    /**
     * @return object
     */
    public function lazyServiceFnFactoryMake($lazyId, array $parameters = null) // : object
    {
        $lazyId = Id::from($lazyId);
        $parameters = $parameters ?? [];

        $instance = $this->injector->makeItem($lazyId, $parameters);

        return $instance;
    }
}
