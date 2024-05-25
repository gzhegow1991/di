<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Injector\InjectorInterface;


interface DiFactoryInterface
{
    public function newDi() : DiInterface;

    public function newInjector() : InjectorInterface;


    public function newLazyServiceAsk($id, array $parameters = null) : LazyService;

    public function newLazyServiceGet($id) : LazyService;

    public function newLazyServiceMake($id, array $parameters = null) : LazyService;


    public function lazyServiceFnFactoryAsk($lazyId, array $parameters = null);

    public function lazyServiceFnFactoryGet($lazyId);

    public function lazyServiceFnFactoryMake($lazyId, array $parameters = null);
}
