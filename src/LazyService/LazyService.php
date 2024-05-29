<?php

namespace Gzhegow\Di\LazyService;

use Gzhegow\Di\Struct\Id;


/**
 * @template T of object
 */
class LazyService
{
    /**
     * @var Id
     */
    public $id;
    /**
     * @var T
     */
    public $instance;

    /**
     * @var callable() : T
     */
    protected $fnFactory;
    /**
     * @var array
     */
    protected $fnFactoryArguments = [];


    public function __construct(Id $lazyId, callable $fnFactory, array $fnFactoryArguments = [])
    {
        $this->id = $lazyId;

        $this->fnFactory = $fnFactory;
        $this->fnFactoryArguments = $fnFactoryArguments;
    }


    public function __call($name, $arguments)
    {
        if (null === $this->instance) {
            $this->instance = call_user_func(
                $this->fnFactory,
                $this->id, $this->fnFactoryArguments
            );

            unset($this->fnFactory);
            unset($this->fnFactoryArguments);
        }

        $result = call_user_func_array([ $this->instance, $name ], $arguments);

        return $result;
    }
}
