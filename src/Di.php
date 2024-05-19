<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Lazy\LazyService;
use Gzhegow\Di\Exception\LogicException;
use Gzhegow\Di\Exception\RuntimeException;
use Gzhegow\Di\Exception\Runtime\NotFoundException;


class Di implements DiInterface
{
    /**
     * @var array<string, string>
     */
    public $bind = [];

    /**
     * @var array<string, string>
     */
    public $class = [];
    /**
     * @var array<string, string>
     */
    public $alias = [];

    /**
     * @var array<string, bool>
     */
    public $singleton = [];

    /**
     * @var array<string, callable>
     */
    public $factory = [];

    /**
     * @var array<string, callable[]>
     */
    public $extend = [];
    /**
     * @var array<string, string>
     */
    public $extendParents = [];

    /**
     * @var array<string, object>
     */
    public $instance = [];


    public function merge(self $di) : void
    {
        foreach ( $di->bind as $id => $type ) {
            $this->bind(
                $id,
                $this->{$type}[ $id ],
                ! empty($this->singleton[ $id ])
            );
        }

        foreach ( $di->extend as $id => $callables ) {
            foreach ( $callables as $callable ) {
                $this->extend($id, $callable);
            }
        }
    }


    public function has($id) : bool
    {
        $status = $this->hasInstance($id);

        return $status;
    }

    /**
     * @return object
     *
     * @throws NotFoundException
     */
    public function get($id)
    {
        $instance = $this->getInstance($id);

        return $instance;
    }

    /**
     * @return object
     */
    public function make($id, array $parameters = [])
    {
        $instance = $this->makeInstance($id, $parameters);

        return $instance;
    }


    public function hasInstance($id) : bool
    {
        if (! is_string($id)) {
            return false;
        }

        if (isset($this->bind[ $id ])) {
            return true;
        }

        return false;
    }

    public function getInstance(string $id) : object
    {
        if (! $this->has($id)) {
            throw new NotFoundException(
                'Missing bind: ' . $id
            );
        }

        if (isset($this->instance[ $id ])) {
            $instance = $this->instance[ $id ];

        } else {
            $instance = $this->makeInstance($id);

            if (isset($this->singleton[ $id ])) {
                $this->instance[ $id ] = $instance;
            }
        }

        return $instance;
    }

    public function makeInstance(string $id, array $parameters = []) : object
    {
        if ($_bound = $this->resolveBound($id)) {
            [ $boundType, $bound ] = $_bound;

        } else {
            [ $boundType, $bound ] = [ 'class', $id ];
        }

        if ('instance' === $boundType) {
            $instance = clone $bound;

        } elseif ('factory' === $boundType) {
            $instance = $this->callFunction($bound, $parameters);

        } elseif ('class' === $boundType) {
            $instance = $this->callConstructor($bound, $parameters);

        } else {
            throw new RuntimeException(
                'Unknown `boundType` while making: ' . $boundType
                . ' / ' . $id
            );
        }

        $classmap = [ $id => $id ];
        if (class_exists($id) || interface_exists($id)) {
            $classmap = $classmap
                + class_parents($id)
                + class_implements($id);
        }

        $intersect = array_intersect_key($this->extendParents, $classmap);

        foreach ( $intersect as $idParent => $idCurrent ) {
            foreach ( $this->extend[ $idParent ] ?? [] as $callable ) {
                $this->callFunction($callable, [ $instance ]);
            }
        }

        return $instance;
    }


    public function getLazy(string $id) : LazyService
    {
        $instance = $this->get($id);

        if (! is_a($instance, LazyService::class)) {
            throw new RuntimeException(
                'The `instance` should be instanceof: ' . LazyService::class
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }

    public function makeLazy(string $id, array $parameters = []) : LazyService
    {
        $instance = $this->make($id, $parameters);

        if (! ($instance instanceof LazyService)) {
            throw new RuntimeException(
                'The `instance` should be instanceof: ' . LazyService::class
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T> $generic
     *
     * @return T
     */
    public function getGeneric(string $id, string $generic, bool $forceInstanceOf = null)
    {
        $forceInstanceOf = $forceInstanceOf ?? false;

        $instance = $this->get($id);

        if ($forceInstanceOf && ! ($instance instanceof $generic)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $generic
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T> $generic
     *
     * @return T
     */
    public function makeGeneric(string $id, string $generic, array $parameters = [], bool $forceInstanceOf = null)
    {
        $forceInstanceOf = $forceInstanceOf ?? false;

        $instance = $this->make($id, $parameters);

        if ($forceInstanceOf && ! ($instance instanceof $generic)) {
            throw new RuntimeException(
                'Returned object should be instance of: '
                . $generic
                . ' / ' . _php_dump($instance)
            );
        }

        return $instance;
    }


    /**
     * @template-covariant T
     *
     * @param class-string<T> $generic
     *
     * @return LazyService<T>|T
     */
    public function getGenericLazy(string $id, string $generic, bool $forceInstanceOf = null)
    {
        /** @var LazyService $instance */

        $forceInstanceOf = $forceInstanceOf ?? false;

        $instance = $this->getLazy($id);

        if ($forceInstanceOf
            && (! is_a($instanceClass = $instance->getClass(), $generic, true))
        ) {
            throw new RuntimeException(
                'Returned LazyService must have class: '
                . $generic
                . ' / ' . $instanceClass
            );
        }

        return $instance;
    }

    /**
     * @template-covariant T
     *
     * @param class-string<T> $generic
     *
     * @return LazyService<T>|T
     */
    public function makeGenericLazy(string $id, string $generic, array $parameters = [], bool $forceInstanceOf = null)
    {
        /** @var LazyService $instance */

        $forceInstanceOf = $forceInstanceOf ?? false;

        $instance = $this->makeLazy($id, $parameters);

        if ($forceInstanceOf
            && (! is_a($instanceClass = $instance->getClass(), $generic, true))
        ) {
            throw new RuntimeException(
                'Returned LazyService must have class: '
                . $generic
                . ' / ' . $instanceClass
            );
        }

        return $instance;
    }


    public function bind(string $id, $mixed = null, bool $singleton = null) : void
    {
        $singleton = $singleton ?? false;

        if ($this->has($id)) {
            throw new RuntimeException(
                'Dependency already exists: ' . $id
            );
        }

        $_mixed = $mixed ?? $id;

        if (is_callable($_mixed)) {
            $this->bind[ $id ] = 'factory';
            $this->factory[ $id ] = $_mixed;

        } elseif (is_object($_mixed)) {
            $this->bind[ $id ] = 'instance';
            $this->instance[ $id ] = $_mixed;

        } elseif (is_string($_mixed)) {
            $isAlias = $_mixed !== $id;

            if ($isAlias) {
                if (! $this->has($_mixed)) {
                    throw new RuntimeException(
                        'Missing alias while binding: ' . $_mixed
                        . ' / ' . $id
                    );
                }

                $this->bind[ $id ] = 'alias';
                $this->alias[ $id ] = $_mixed;

            } else {
                if (! (class_exists($_mixed) || interface_exists($_mixed))) {
                    throw new RuntimeException(
                        'Missing class while binding: ' . $_mixed
                        . ' / ' . $id
                    );
                }

                $this->bind[ $id ] = 'class';
                $this->class[ $id ] = $_mixed;
            }

        } else {
            throw new LogicException(
                'The `mixed` should be string|object|callable: '
                . _php_dump($mixed)
            );
        }

        if ($singleton) {
            $this->singleton[ $id ] = true;
        }
    }


    public function bindSingleton(string $id, $mixed = null) : void
    {
        $this->bind($id, $mixed, true);
    }

    public function bindInstance(string $id, object $instance) : void
    {
        $this->bind($id, $instance);
    }

    public function bindAlias(string $id, string $alias, bool $singleton = null) : void
    {
        if ($id === $alias) {
            throw new LogicException(
                'The `id` should be not equal to `alias`: '
                . $id
                . ' / ' . $alias
            );
        }

        $this->bind($id, $alias, $singleton);
    }

    public function bindLazy(string $id, string $class, bool $singleton = null) : void
    {
        if ($id === $class) {
            throw new LogicException(
                'The `id` should be not equal to `class`: '
                . $id
                . ' / ' . $class
            );
        }

        $lazyService = new LazyService($class, function () use ($class) {
            $instance = $this->getInstance($class);

            return $instance;
        });

        $this->bind($class, $class, $singleton);
        $this->bind($id, $lazyService, $singleton);
    }

    /**
     * @param callable $fnFactory
     */
    public function bindFactory(string $id, $fnFactory, bool $singleton = null) : void
    {
        $this->bind($id, $fnFactory, $singleton);
    }


    /**
     * @param callable $fnExtend
     */
    public function extend(string $id, $fnExtend) : void
    {
        $this->extend[ $id ] = $this->extend[ $id ] ?? [];
        $this->extend[ $id ][] = $fnExtend;

        $classmap = [ $id => $id ];
        if (class_exists($id) || interface_exists($id)) {
            $classmap = $classmap
                + class_parents($id)
                + class_implements($id);
        }

        foreach ( $classmap as $class => $devnull ) {
            $this->extendParents[ $class ] = $id;
        }
    }


    protected function callConstructor(string $class, array $parameters = [])
    {
        $_args = $this->resolveArguments($class, $parameters);

        $instance = new $class(...$_args);

        return $instance;
    }

    /**
     * @param callable $fn
     */
    protected function callFunction($fn, array $args = [])
    {
        $_args = $this->resolveArguments($fn, $args);

        $result = call_user_func_array($fn, $_args);

        return $result;
    }


    protected function resolveBound(string $id) : ?array
    {
        if (! isset($this->bind[ $id ])) {
            return null;
        }

        $currentBound = $id;
        $currentPath = [];
        do {
            $currentBoundType = $this->bind[ $currentBound ];
            $currentBound = $this->{$currentBoundType}[ $currentBound ];

            if ($currentBoundType !== 'alias') {
                break;
            }

            if (isset($currentPath[ $currentBound ])) {
                throw new RuntimeException(
                    'Cyclic dependency resolving detected: '
                    . _php_dump($currentPath)
                );
            }

            $currentPath[ $currentBound ] = true;
        } while ( isset($this->bind[ $currentBound ]) );

        $bound = [ $currentBoundType, $currentBound ];

        return $bound;
    }

    protected function resolveArguments($reflectable, array $parameters = []) : array
    {
        $reflectResult = _php_reflect($reflectable);

        [ 'arguments' => $arguments ] = $reflectResult;

        $_args = [];
        foreach ( $arguments as $i => [ $argName, $argRtList, $argRtTree, $argIsNullable ] ) {
            if (isset($parameters[ $i ])) {
                $_args[ $i ] = $parameters[ $i ];

            } elseif (isset($parameters[ $argName ])) {
                $_args[ $i ] = $parameters[ $argName ];

            } else {
                $argRtIsMulti = (count($argRtTree[ '' ]) > 2);

                $argRtType = false;
                $argRtClass = false;
                if (! $argRtIsMulti) {
                    $argRtType = $argRtList[ 0 ][ 0 ] ?? null;
                    $argRtClass = $argRtList[ 0 ][ 1 ] ?? null;
                }

                if (! isset($argRtClass)) {
                    if (! $argIsNullable) {
                        if ($argRtIsMulti) {
                            throw new RuntimeException(
                                'Resolving UNION / INTERSECT parameters is not implemented: '
                                . "[ {$i} ] \${$argName}"
                                . ' / ' . _php_dump($reflectable)
                            );

                        } else {
                            throw new RuntimeException(
                                'Unable to resolve parameter: '
                                . "[ {$i} ] \${$argName} : {$argRtType}"
                                . ' / ' . _php_dump($reflectable)
                            );
                        }
                    }

                    $_args[ $i ] = null;

                } else {
                    $_args[ $i ] = $this->has($argRtClass)
                        ? $this->getInstance($argRtClass)
                        : $this->makeInstance($argRtClass);
                }
            }
        }

        return $_args;
    }
}
