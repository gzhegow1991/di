<?php

namespace Gzhegow\Di\Lazy;

use Gzhegow\Di\Exception\LogicException;


/**
 * @template T
 */
class LazyService
{
    /**
     * @var class-string<T>|T
     */
    protected $class;
    /**
     * @var callable() : T
     */
    protected $fnFactory;

    /**
     * @var T
     */
    protected $instance;


    /**
     * @param class-string<T> $class
     */
    public function __construct(string $class, $fnFactory)
    {
        if (! class_exists($class)) {
            throw new LogicException(
                'Missing class: ' . $class
            );
        }

        $this->class = $class;
        $this->fnFactory = $fnFactory;
    }


    /**
     * @return class-string<T>
     */
    public function getClass() : string
    {
        return $this->class;
    }


    public function __call($name, $arguments)
    {
        if (! isset($this->instance)) {
            $this->instance = call_user_func($this->fnFactory);

            unset($this->fnFactory);
        }

        return call_user_func_array([ $this->instance, $name ], $arguments);
    }
}
