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

        if (is_a($from, static::class)) {
            $instance = $from;

        } elseif (is_string($from)) {
            $instance = static::tryFromString($from);
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

        $isInterface = interface_exists($id);
        $isClass = ! $isInterface && class_exists($id);

        $isContract = $isInterface || $isClass;

        $_id = $id;
        if ($isContract) {
            $_id = ltrim($_id, '\\');
        }

        $instance->value = $_id;

        $instance->isClass = $isClass;
        $instance->isInterface = $isInterface;
        $instance->isContract = $isContract;

        return $instance;
    }


    public function __toString()
    {
        return $this->value;
    }


    /**
     * @param static $id
     *
     * @return bool
     */
    public function isSame($id) : bool
    {
        if (! static::tryFrom($id)) {
            return false;
        }

        return $id->getValue() === $this->getValue();
    }


    public function getValue() : string
    {
        return $this->value;
    }


    public function isClass() : bool
    {
        return $this->isClass;
    }

    public function isInterface() : bool
    {
        return $this->isClass;
    }

    public function isContract() : bool
    {
        return $this->isContract;
    }
}
