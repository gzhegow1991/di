<?php

namespace Gzhegow\Di\LazyService;

interface DiLazyServiceFactoryInterface
{
    public function newLazyServiceGet($lazyId, ?array $parametersWhenNew = null) : DiLazyService;

    public function newLazyServiceMake($lazyId, ?array $parameters = null) : DiLazyService;

    public function newLazyServiceTake($lazyId, ?array $parametersWhenNew = null) : DiLazyService;

    public function newLazyServiceFetch($lazyId, ?array $parametersWhenNew = null) : DiLazyService;


    /**
     * @return object
     */
    public function fnFactoryGet($lazyId, ?array $parametersWhenNew = null);

    /**
     * @return object
     */
    public function fnFactoryMake($lazyId, ?array $parameters = null);

    /**
     * @return object
     */
    public function fnFactoryTake($lazyId, ?array $parametersWhenNew = null);

    /**
     * @return object
     */
    public function fnFactoryFetch($lazyId, ?array $parametersWhenNew = null);
}
