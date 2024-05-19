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

    $instance = $di ?? $instance ?? new Di();

    return $instance;
}


/**
 * @return Di
 */
function _di_merge(DiInterface $di)
{
    $current = _di();

    $current->merge($di);

    return $current;
}


function _di_has(string $id) : bool
{
    return _di()->has($id);
}


/**
 * @throws NotFoundException
 */
function _di_get(string $id) : object
{
    return _di()->get($id);
}

/**
 * @throws NotFoundException
 */
function _di_get_lazy(string $id) : LazyService
{
    return _di()->getLazy($id);
}

/**
 * @template-covariant T
 *
 * @param class-string<T> $generic
 *
 * @return T
 *
 * @throws NotFoundException
 */
function _di_get_generic(string $id, string $generic, bool $forceInstanceOf = null) : object
{
    return _di()->getGeneric($id, $generic, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T> $generic
 *
 * @return LazyService<T>|T
 *
 * @throws NotFoundException
 */
function _di_get_generic_lazy(string $id, string $generic, bool $forceInstanceOf = null) : object
{
    return _di()->getGenericLazy($id, $generic, $forceInstanceOf);
}


function _di_make(string $id, array $parameters = []) : object
{
    return _di()->make($id, $parameters);
}

function _di_make_lazy(string $id, array $parameters = []) : LazyService
{
    return _di()->makeLazy($id, $parameters);
}

/**
 * @template-covariant T
 *
 * @param class-string<T> $generic
 *
 * @return T
 */
function _di_make_generic(string $id, string $generic, array $parameters = [], bool $forceInstanceOf = null)
{
    return _di()->makeGeneric($id, $generic, $parameters, $forceInstanceOf);
}

/**
 * @template-covariant T
 *
 * @param class-string<T> $generic
 *
 * @return LazyService<T>|T
 */
function _di_make_generic_lazy(string $id, string $generic, array $parameters = [], bool $forceInstanceOf = null)
{
    return _di()->makeGenericLazy($id, $generic, $parameters, $forceInstanceOf);
}


function _di_autowire(object $instance, array $methodArgs = null, string $methodName = null) : object
{
    return _di()->autowire($instance, $methodArgs, $methodName);
}


function _di_bind(string $id, $mixed = null, bool $singleton = null) : void
{
    _di()->bind($id, $mixed, $singleton);
}

function _di_bind_singleton(string $id, $mixed = null) : void
{
    _di()->bindSingleton($id, $mixed);
}

function _di_bind_instance(string $id, object $value) : void
{
    _di()->bindInstance($id, $value);
}

function _di_bind_alias(string $id, string $alias, bool $singleton = null) : void
{
    _di()->bindAlias($id, $alias, $singleton);
}

function _di_bind_lazy(string $id, string $class, bool $singleton = null) : void
{
    _di()->bindLazy($id, $class, $singleton);
}

/**
 * @param callable $fnFactory
 */
function _di_bind_factory(string $id, $fnFactory, bool $singleton = null) : void
{
    _di()->bindFactory($id, $fnFactory, $singleton);
}


/**
 * @param callable $fnExtend
 */
function _di_extend(string $id, $fnExtend)
{
    _di()->extend($id, $fnExtend);
}
