<?php

namespace Gzhegow\Di\Struct;

use Gzhegow\Lib\Lib;
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


    /**
     * @return static
     */
    public static function from($from) // : static
    {
        $instance = static::tryFrom($from, $error);

        if (null === $instance) {
            throw $error;
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from, \Throwable &$last = null) // : ?static
    {
        $last = null;

        Lib::php()->errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromString($from);

        $errors = Lib::php()->errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, $last);
            }
        }

        return $instance;
    }


    /**
     * @return static|null
     */
    public static function tryFromInstance($instance) // : ?static
    {
        if (! is_a($instance, static::class)) {
            return Lib::php()->error(
                [ 'The `from` should be instance of: ' . static::class, $instance ]
            );
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFromString($string) // : ?static
    {
        $_id = Lib::parse()->string_not_empty($string);

        $_id = ltrim($_id, '\\');

        if ('' === $_id) {
            return Lib::php()->error(
                [ 'The `from` should be non-empty string', $string ]
            );
        }

        $instance = new static();
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
