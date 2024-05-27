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


function _di_bind($id, $mixed = null, bool $isSingleton = null)
{
    _di()->bind($id, $mixed, $isSingleton);
}

function _di_bind_singleton($id, $mixed = null)
{
    _di()->bindSingleton($id, $mixed);
}


function _di_bind_alias($id, $aliasId, bool $isSingleton = null)
{
    return _di()->bindAlias($id, $aliasId, $isSingleton);
}

/**
 * @param class-string $structId
 */
function _di_bind_struct($id, $structId, bool $isSingleton = null)
{
    return _di()->bindStruct($id, $structId, $isSingleton);
}

/**
 * @param callable $fnFactory
 */
function _di_bind_factory($id, $fnFactory, bool $isSingleton = null)
{
    return _di()->bindFactory($id, $fnFactory, $isSingleton);
}

function _di_bind_instance($id, object $instance, bool $isSingleton = null)
{
    return _di()->bindInstance($id, $instance, $isSingleton);
}


/**
 * @param callable $fnExtend
 */
function _di_extend($id, $fnExtend)
{
    return _di()->extend($id, $fnExtend);
}


/**
 * @template-covariant T
 *
 * @param class-string<T>|null $contractT
 *
 * @return T|null
 */
function _di_ask($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null) // : ?object
{
    return _di()->ask($id, $contractT, $forceInstanceOf, $parametersWhenNew);
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
function _di_get($id, string $contractT = null, bool $forceInstanceOf = null, array $parametersWhenNew = null) // : object
{
    return _di()->get($id, $contractT, $forceInstanceOf, $parametersWhenNew);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|null $contractT
 *
 * @return T
 */
function _di_take($id, array $parametersWhenNew = null, string $contractT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->take($id, $parametersWhenNew, $contractT, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|null $contractT
 *
 * @return T
 */
function _di_make($id, array $parameters = null, string $contractT = null, bool $forceInstanceOf = null) // : object
{
    return _di()->make($id, $parameters, $contractT, $forceInstanceOf);
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
function _di_get_lazy($id, string $contractT = null, array $parametersWhenNew = null) // : LazyService
{
    return _di()->getLazy($id, $contractT, $parametersWhenNew);
}

/**
 * @template-covariant T
 *
 * @param class-string<T>|T|null $contractT
 *
 * @return LazyService<T>|T
 */
function _di_make_lazy($id, array $parameters = null, string $contractT = null) // : LazyService
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
