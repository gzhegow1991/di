<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


/**
 * @return Di
 */
function _di(DiInterface $di = null)
{
    static $instance;

    $before = $instance;

    $instance = $di ?? $instance ?? new Di();

    if ($before !== $instance) {
        $instance::setInstance($instance);
    }

    return $instance;
}


/**
 * @param string $id
 */
function _di_has($id) : bool
{
    return _di()->has($id);
}


function _di_bind(string $id, $mixed = null, bool $isSingleton = null)
{
    _di()->bind($id, $mixed, $isSingleton);
}

function _di_bind_singleton(string $id, $mixed = null)
{
    _di()->bindSingleton($id, $mixed);
}


function _di_bind_alias(string $id, string $aliasId, bool $isSingleton = null)
{
    _di()->bindAlias($id, $aliasId, $isSingleton);
}

/**
 * @param class-string $class
 */
function _di_bind_class(string $id, string $class, bool $isSingleton = null)
{
    _di()->bindStruct($id, $class, $isSingleton);
}

function _di_bind_instance(string $id, object $instance, bool $isSingleton = null)
{
    _di()->bindInstance($id, $instance, $isSingleton);
}

/**
 * @param callable $fnFactory
 */
function _di_bind_factory(string $id, $fnFactory, bool $isSingleton = null) : void
{
    _di()->bindFactory($id, $fnFactory, $isSingleton);
}


/**
 * @param callable $fnExtend
 */
function _di_extend(string $id, $fnExtend)
{
    _di()->extend($id, $fnExtend);
}


/**
 * @template-covariant T
 *
 * @param class-string<T>|null $classT
 *
 * @return T
 */
function _di_ask(string $id, array $parameters = null, $classT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->askGeneric($id, $parameters, $classT, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|T|null $classT
 *
 * @return LazyService<T>|T
 */
function _di_ask_lazy(string $id, array $parameters = null, $classT = null) // : LazyService
{
    return _di()->askLazyGeneric($id, $parameters, $classT);
}


/**
 * @template-covariant T
 *
 * @param class-string<T>|null $classT
 *
 * @return T
 *
 * @throws NotFoundException
 */
function _di_get(string $id, $classT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->getGeneric($id, $classT, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|T|null $classT
 *
 * @return LazyService<T>|T
 *
 * @throws NotFoundException
 */
function _di_get_lazy(string $id, $classT = null) // : LazyService
{
    return _di()->getLazyGeneric($id, $classT);
}


/**
 * @template-covariant T
 *
 * @param class-string<T>|null $classT
 *
 * @return T
 */
function _di_make(string $id, array $parameters = null, $classT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->makeGeneric($id, $parameters, $classT, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|T|null $classT
 *
 * @return LazyService<T>|T
 */
function _di_make_lazy(string $id, array $parameters = null, $classT = null) // : LazyService
{
    return _di()->makeLazyGeneric($id, $parameters, $classT);
}


/**
 * @template T
 *
 * @param T|object $instance
 *
 * @return T
 */
function _di_autowire(object $instance, array $methodArgs = null, string $methodName = null) // : object
{
    return _di()->autowire($instance, $methodArgs, $methodName);
}


/**
 * @param callable $fn
 */
function _di_call($fn, array $args = null) // : mixed
{
    return _di()->call($fn, $args);
}
