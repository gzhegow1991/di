<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\LazyService\DiLazyService;
use Gzhegow\Di\Exception\Runtime\NotFoundException;
use Gzhegow\Di\LazyService\DiLazyServiceFactoryInterface;


class Di
{
    public static function resetCache() : DiInterface
    {
        return static::$facade->resetCache();
    }

    public static function saveCache() : DiInterface
    {
        return static::$facade->saveCache();
    }

    public static function clearCache() : DiInterface
    {
        return static::$facade->clearCache();
    }


    public static function merge(?DiInterface $di) : DiInterface
    {
        return static::$facade->merge($di);
    }


    /**
     * @param string $id
     */
    public static function has($id, Id &$result = null) : bool
    {
        return static::$facade->has($id, $result);
    }


    public static function bind($id, $mixed = null, bool $isSingleton = null) : DiInterface
    {
        return static::$facade->bind($id, $mixed, $isSingleton);
    }

    public static function bindSingleton($id, $mixed = null) : DiInterface
    {
        return static::$facade->bindSingleton($id, $mixed);
    }


    public static function bindAlias($id, $aliasId, bool $isSingleton = null) : DiInterface
    {
        return static::$facade->bindAlias($id, $aliasId, $isSingleton);
    }

    /**
     * @param class-string $classId
     */
    public static function bindClass($id, $classId, bool $isSingleton = null) : DiInterface
    {
        return static::$facade->bindClass($id, $classId, $isSingleton);
    }

    /**
     * @param callable $fnFactory
     */
    public static function bindFactory($id, $fnFactory, bool $isSingleton = null) : DiInterface
    {
        return static::$facade->bindFactory($id, $fnFactory, $isSingleton);
    }

    public static function bindInstance($id, object $instance, bool $isSingleton = null) : DiInterface
    {
        return static::$facade->bindInstance($id, $instance, $isSingleton);
    }


    /**
     * @param callable $fnExtend
     */
    public static function extend($id, $fnExtend) : DiInterface
    {
        return static::$facade->extend($id, $fnExtend);
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T|null
     */
    public static function ask($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null) : ?object
    {
        return static::$facade->ask($id, $contractT, $forceInstanceOf, $parametersWhenNew);
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
    public static function get($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null) : object
    {
        return static::$facade->get($id, $contractT, $forceInstanceOf, $parametersWhenNew);
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public static function make($id, array $parameters = null, string $contractT = null, bool $forceInstanceOf = null) : object
    {
        return static::$facade->make($id, $parameters, $contractT, $forceInstanceOf);
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public static function take($id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null) : object
    {
        return static::$facade->take($id, $parametersWhenNew, $contractT, $forceInstanceOf);
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|null $contractT
     *
     * @return T
     */
    public static function fetch($id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null) : object
    {
        return static::$facade->fetch($id, $parametersWhenNew, $contractT, $forceInstanceOf);
    }


    public static function setLazyServiceFactory(DiLazyServiceFactoryInterface $lazyServiceFactory) : DiInterface
    {
        return static::$facade->setLazyServiceFactory($lazyServiceFactory);
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
    public static function getLazy($id, string $contractT = null, array $parametersWhenNew = null)
    {
        return static::$facade->getLazy($id, $contractT, $parametersWhenNew);
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public static function makeLazy($id, array $parameters = null, string $contractT = null)
    {
        return static::$facade->makeLazy($id, $parameters, $contractT);
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public static function takeLazy($id, array $parametersWhenNew = null, string $contractT = null)
    {
        return static::$facade->takeLazy($id, $parametersWhenNew, $contractT);
    }

    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T|null $contractT
     *
     * @return DiLazyService<T>|T
     */
    public static function fetchLazy($id, array $parametersWhenNew = null, string $contractT = null)
    {
        return static::$facade->fetchLazy($id, $parametersWhenNew, $contractT);
    }


    /**
     * @template T
     *
     * @param T|object $instance
     *
     * @return T
     */
    public static function autowireInstance(object $instance, array $methodArgs = null, string $methodName = null)
    {
        return static::$facade->autowireInstance($instance, $methodArgs, $methodName);
    }


    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public static function callUserFuncAutowired($fn, ...$args)
    {
        return static::$facade->callUserFuncAutowired($fn, ...$args);
    }

    /**
     * @param callable $fn
     *
     * @return mixed
     */
    public static function callUserFuncArrayAutowired($fn, array $args = null)
    {
        return static::$facade->callUserFuncArrayAutowired($fn, $args);
    }


    public static function setFacade(?DiInterface $facade) : ?DiInterface
    {
        $last = static::$facade;

        static::$facade = $facade;

        return $last;
    }

    /**
     * @var DiInterface
     */
    protected static $facade;
}
