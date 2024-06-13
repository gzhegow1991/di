<?php

namespace Gzhegow\Di\Struct;

use Gzhegow\Di\Lib;
use Gzhegow\Di\Exception\LogicException;


class Id
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var bool
     */
    protected $isClass;
    /**
     * @var bool
     */
    protected $isInterface;
    /**
     * @var bool
     */
    protected $isContract;


    private function __construct()
    {
    }


    public static function from($from)
    {
        $instance = null;

        if (is_a($from, static::class)) {
            $instance = $from;

        } elseif (is_string($from)) {
            $instance = static::fromString($from);
        }

        if (null === $instance) {
            throw new LogicException(
                'Unknown `from`: ' . Lib::php_dump($from)
            );
        }

        return $instance;
    }

    protected static function fromString($string)
    {
        if (null === ($instance = static::tryFromString($string))) {
            throw new LogicException(
                'Invalid `from`: ' . Lib::php_dump($string)
            );
        }

        return $instance;
    }


    public static function tryFrom($from)
    {
        $instance = null;

        if (is_string($from)) {
            $instance = static::tryFromString($from);

        } elseif (is_a($from, static::class)) {
            $instance = $from;
        }

        if (null === $instance) {
            return null;
        }

        return $instance;
    }

    protected static function tryFromString($id)
    {
        $instance = new static();

        if (! is_string($id)) {
            return null;
        }

        if ('' === $id) {
            return null;
        }

        $_id = ltrim($id, '\\');

        $instance->value = $_id;

        return $instance;
    }


    public function __toString()
    {
        return $this->value;
    }


    public function getValue() : string
    {
        return $this->value;
    }


    public function isClass() : bool
    {
        if (null === $this->isClass) {
            $this->isClass = class_exists($this->value);
        }

        return $this->isClass;
    }

    public function isInterface() : bool
    {
        if (null === $this->isInterface) {
            $this->isInterface = interface_exists($this->value);
        }

        return $this->isInterface;
    }

    public function isContract() : bool
    {
        if (null === $this->isContract) {
            $this->isContract = $this->isInterface() || $this->isClass();
        }

        return $this->isContract;
    }
}
