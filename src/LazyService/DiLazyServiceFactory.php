<?php

namespace Gzhegow\Di\LazyService;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\DiInterface;


class DiLazyServiceFactory implements DiLazyServiceFactoryInterface
{
    /**
     * @var DiInterface
     */
    protected $di;


    public function __construct(DiInterface $di)
    {
        $this->di = $di;
    }


    public function newLazyServiceGet($lazyId, array $parametersWhenNew = null) : DiLazyService
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $lazyService = new DiLazyService($lazyId, [ $this, 'fnFactoryGet' ], $parametersWhenNew);

        return $lazyService;
    }

    public function newLazyServiceMake($lazyId, array $parameters = null) : DiLazyService
    {
        $lazyId = Id::from($lazyId);
        $parameters = $parameters ?? [];

        $lazyService = new DiLazyService($lazyId, [ $this, 'fnFactoryMake' ], $parameters);

        return $lazyService;
    }

    public function newLazyServiceTake($lazyId, array $parametersWhenNew = null) : DiLazyService
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $lazyService = new DiLazyService($lazyId, [ $this, 'fnFactoryTake' ], $parametersWhenNew);

        return $lazyService;
    }

    public function newLazyServiceFetch($lazyId, array $parametersWhenNew = null) : DiLazyService
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $lazyService = new DiLazyService($lazyId, [ $this, 'fnFactoryFetch' ], $parametersWhenNew);

        return $lazyService;
    }


    /**
     * @return object
     */
    public function fnFactoryGet($lazyId, array $parametersWhenNew = null)
    {
        $instance = $this->di->get($lazyId, null, null, $parametersWhenNew);

        return $instance;
    }

    /**
     * @return object
     */
    public function fnFactoryMake($lazyId, array $parameters = null)
    {
        $instance = $this->di->make($lazyId, $parameters);

        return $instance;
    }

    /**
     * @return object
     */
    public function fnFactoryTake($lazyId, array $parametersWhenNew = null)
    {
        $instance = $this->di->take($lazyId, $parametersWhenNew);

        return $instance;
    }

    /**
     * @return object
     */
    public function fnFactoryFetch($lazyId, array $parametersWhenNew = null)
    {
        $instance = $this->di->fetch($lazyId, $parametersWhenNew);

        return $instance;
    }
}
