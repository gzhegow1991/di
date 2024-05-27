<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Injector\InjectorInterface;


interface DiFactoryInterface
{
    public function newDi() : DiInterface;

    public function newInjector() : InjectorInterface;


    public function newLazyServiceAsk($lazyId, array $parametersWhenNew = null) : LazyService;

    public function newLazyServiceGet($lazyId, array $parametersWhenNew = null) : LazyService;

    public function newLazyServiceMake($lazyId, array $parameters = null) : LazyService;


    public function lazyServiceFnFactoryAsk($lazyId, array $parametersWhenNew = null);

    public function lazyServiceFnFactoryGet($lazyId, array $parametersWhenNew = null);

    public function lazyServiceFnFactoryMake($lazyId, array $parameters = null);
}
