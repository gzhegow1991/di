<?php

namespace Gzhegow\Di\Lazy;

use Gzhegow\Di\Struct\Id;
use Gzhegow\Di\Exception\LogicException;
use function Gzhegow\Di\_php_dump;


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


    /**
     * @param callable $fnFactory
     */
    public function __construct($id, $fnFactory, array $fnFactoryArguments = [])
    {
        $id = Id::from($id);

        if (! is_callable($fnFactory)) {
            throw new LogicException(
                'The `fnFactory` should be callable: ' . _php_dump($fnFactory)
            );
        }

        $this->id = $id;

        $this->fnFactory = $fnFactory;
        $this->fnFactoryArguments = $fnFactoryArguments;
    }


    public function __call($name, $arguments)
    {
        if (null === $this->instance) {
            $this->instance = call_user_func($this->fnFactory, $this->id, $this->fnFactoryArguments);

            unset($this->fnFactory);
            unset($this->fnFactoryArguments);
        }

        $result = call_user_func_array([ $this->instance, $name ], $arguments);

        return $result;
    }
}
