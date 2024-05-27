<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


/**
 * @return DiInterface
 */
function _di(DiInterface $di = null)
{
    static $instance;

    $before = $instance;

    $instance = $di ?? $instance ?? (new DiFactory())->newDi();

    if ($before !== $instance) {
        $instance::setInstance($instance);
    }

    return $instance;
}


/**
 * @param string $id
 */
function _di_has_bound($id, Id &$result = null) : bool
{
    return _di()->hasBound($id, $result);
}

/**
 * @param string $id
 */
function _di_has_item($id, Id &$result = null) : bool
{
    return _di()->hasItem($id, $result);
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
    return _di()->bindAlias($id, $aliasId, $isSingleton);
}

/**
 * @param class-string $class
 */
function _di_bind_class(string $id, string $class, bool $isSingleton = null)
{
    return _di()->bindStruct($id, $class, $isSingleton);
}

/**
 * @param callable $fnFactory
 */
function _di_bind_factory(string $id, $fnFactory, bool $isSingleton = null)
{
    return _di()->bindFactory($id, $fnFactory, $isSingleton);
}

function _di_bind_instance(string $id, object $instance, bool $isSingleton = null)
{
    return _di()->bindInstance($id, $instance, $isSingleton);
}


/**
 * @param callable $fnExtend
 */
function _di_extend(string $id, $fnExtend)
{
    return _di()->extend($id, $fnExtend);
}


/**
 * @template-covariant T
 *
 * @param class-string<T>|null $contractT
 *
 * @return T
 */
function _di_ask(string $id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->ask($id, $parametersWhenNew, $contractT, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|null $contractT
 *
 * @return T
 *
 * @throws NotFoundException
 */
function _di_get(string $id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->get($id, $parametersWhenNew, $contractT, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|null $contractT
 *
 * @return T
 */
function _di_make(string $id, array $parameters = null, string $contractT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->make($id, $parameters, $contractT, $forceInstanceOf);
}


/**
 * @template-covariant T
 *
 * @param class-string<T>|T|null $contractT
 *
 * @return LazyService<T>|T
 */
function _di_ask_lazy(string $id, array $parametersWhenNew = null, string $contractT = null) // : LazyService
{
    return _di()->askLazy($id, $parametersWhenNew, $contractT);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|T|null $contractT
 *
 * @return LazyService<T>|T
 *
 * @throws NotFoundException
 */
function _di_get_lazy(string $id, array $parametersWhenNew = null, string $contractT = null) // : LazyService
{
    return _di()->getLazy($id, $parametersWhenNew, $contractT);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|T|null $contractT
 *
 * @return LazyService<T>|T
 */
function _di_make_lazy(string $id, array $parameters = null, string $contractT = null) // : LazyService
{
    return _di()->makeLazy($id, $parameters, $contractT);
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
