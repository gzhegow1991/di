<?php

namespace Gzhegow\Di\LazyService;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\DiInterface;


class LazyServiceFactory implements LazyServiceFactoryInterface
{
    /**
     * @var DiInterface
     */
    protected $di;


    public function __construct(DiInterface $di)
    {
        $this->di = $di;
    }


    public function newLazyServiceGet($lazyId, array $parametersWhenNew = null) : LazyService
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $lazyService = new LazyService($lazyId, [ $this, 'fnFactoryGet' ], $parametersWhenNew);

        return $lazyService;
    }

    public function newLazyServiceMake($lazyId, array $parameters = null) : LazyService
    {
        $lazyId = Id::from($lazyId);
        $parameters = $parameters ?? [];

        $lazyService = new LazyService($lazyId, [ $this, 'fnFactoryMake' ], $parameters);

        return $lazyService;
    }

    public function newLazyServiceTake($lazyId, array $parametersWhenNew = null) : LazyService
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $lazyService = new LazyService($lazyId, [ $this, 'fnFactoryTake' ], $parametersWhenNew);

        return $lazyService;
    }

    public function newLazyServiceFetch($lazyId, array $parametersWhenNew = null) : LazyService
    {
        $lazyId = Id::from($lazyId);
        $parametersWhenNew = $parametersWhenNew ?? [];

        $lazyService = new LazyService($lazyId, [ $this, 'fnFactoryFetch' ], $parametersWhenNew);

        return $lazyService;
    }


    /**
     * @return object
     */
    public function fnFactoryGet($lazyId, array $parametersWhenNew = null) // : object
    {
        $instance = $this->di->get($lazyId, null, null, $parametersWhenNew);

        return $instance;
    }

    /**
     * @return object
     */
    public function fnFactoryMake($lazyId, array $parameters = null) // : object
    {
        $instance = $this->di->make($lazyId, $parameters);

        return $instance;
    }

    /**
     * @return object
     */
    public function fnFactoryTake($lazyId, array $parametersWhenNew = null) // : object
    {
        $instance = $this->di->take($lazyId, $parametersWhenNew);

        return $instance;
    }

    /**
     * @return object
     */
    public function fnFactoryFetch($lazyId, array $parametersWhenNew = null) // : object
    {
        $instance = $this->di->fetch($lazyId, $parametersWhenNew);

        return $instance;
    }
}
