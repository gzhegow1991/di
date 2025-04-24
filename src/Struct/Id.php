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
    public static function from($from, array $refs = [])
    {
        $withErrors = array_key_exists(0, $refs);

        $refs[ 0 ] = $refs[ 0 ] ?? null;

        $instance = null
            ?? static::fromInstance($from, $refs)
            ?? static::fromString($from, $refs);

        if (! $withErrors) {
            if (null === $instance) {
                throw $refs[ 0 ];
            }
        }

        return $instance;
    }

    /**
     * @return static|bool|null
     */
    public static function fromInstance($from, array $refs = [])
    {
        if ($from instanceof static) {
            return Lib::refsResult($refs, $from);
        }

        return Lib::refsError(
            $refs,
            new LogicException(
                [ 'The `from` should be instance of: ' . static::class, $from ]
            )
        );
    }

    /**
     * @return static|bool|null
     */
    public static function fromString($from, array $refs = [])
    {
        if (! (is_string($from) && ('' !== $from))) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be non-empty string', $from ]
                )
            );
        }

        $id = ltrim($from, '\\');

        if ('' === $id) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `id` should be non-empty string', $from ]
                )
            );
        }

        $instance = new static();
        $instance->value = $id;

        return Lib::refsResult($refs, $instance);
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
