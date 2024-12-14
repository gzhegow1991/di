<?php

namespace Gzhegow\Di\LazyService;

interface LazyServiceFactoryInterface
{
    public function newLazyServiceGet($lazyId, array $parametersWhenNew = null) : LazyService;

    public function newLazyServiceMake($lazyId, array $parameters = null) : LazyService;

    public function newLazyServiceTake($lazyId, array $parametersWhenNew = null) : LazyService;

    public function newLazyServiceFetch($lazyId, array $parametersWhenNew = null) : LazyService;


    /**
     * @return object
     */
    public function fnFactoryGet($lazyId, array $parametersWhenNew = null);

    /**
     * @return object
     */
    public function fnFactoryMake($lazyId, array $parameters = null);

    /**
     * @return object
     */
    public function fnFactoryTake($lazyId, array $parametersWhenNew = null);

    /**
     * @return object
     */
    public function fnFactoryFetch($lazyId, array $parametersWhenNew = null);
}
