<?php

namespace Gzhegow\Di\Struct;

use Gzhegow\Di\Exception\LogicException;
use function Gzhegow\Di\_php_dump;


class Id
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var bool
     */
    protected $isStruct;


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
                'Unknown `from`: ' . _php_dump($from)
            );
        }

        return $instance;
    }

    protected static function fromString($string)
    {
        if (null === ($instance = static::tryFromString($string))) {
            throw new LogicException(
                'Invalid `from`: ' . _php_dump($string)
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

        $isStruct = interface_exists($id) || class_exists($id);

        $_id = $id;
        if ($isStruct) {
            $_id = ltrim($_id, '\\');
        }

        $instance->value = $_id;

        $instance->isStruct = $isStruct;

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


    public function isStruct() : bool
    {
        return $this->isStruct;
    }
}
