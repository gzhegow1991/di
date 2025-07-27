<?php

namespace Gzhegow\Di\Struct;


use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Type\Ret;


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
     * @return static|Ret<static>
     */
    public static function from($from, ?array $fallback = null)
    {
        $ret = Ret::new();

        $instance = null
            ?? static::fromStatic($from, $fallback)->orNull($ret)
            ?? static::fromString($from, $fallback)->orNull($ret);

        if ($ret->isFail()) {
            return Ret::throw($fallback, $ret);
        }

        return Ret::ok($fallback, $instance);
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromStatic($from, ?array $fallback = null)
    {
        if ($from instanceof static) {
            return Ret::ok($fallback, $from);
        }

        return Ret::throw(
            $fallback,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromString($from, ?array $fallback = null)
    {
        $theType = Lib::type();

        if (! $theType->string_not_empty($from)->isOk([ &$fromString ])) {
            return Ret::throw(
                $fallback,
                [ 'The `from` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $id = ltrim($fromString, '\\');

        if ('' === $id) {
            return Ret::throw(
                $fallback,
                [ 'The `id` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->value = $id;

        return Ret::ok($fallback, $instance);
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
