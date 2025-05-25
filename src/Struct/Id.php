<?php

namespace Gzhegow\Di\Struct;

use Gzhegow\Lib\Modules\Php\Result\Ret;
use Gzhegow\Lib\Modules\Php\Result\Result;


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
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function from($from, $ret = null)
    {
        $retCur = Result::asValue();

        $instance = null
            ?? static::fromStatic($from, $retCur)
            ?? static::fromString($from, $retCur);

        if ($retCur->isErr()) {
            return Result::err($ret, $retCur);
        }

        return Result::ok($ret, $instance);
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromStatic($from, $ret = null)
    {
        if ($from instanceof static) {
            return Result::ok($ret, $from);
        }

        return Result::err(
            $ret,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromString($from, $ret = null)
    {
        if (! (is_string($from) && ('' !== $from))) {
            return Result::err(
                $ret,
                [ 'The `from` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $id = ltrim($from, '\\');

        if ('' === $id) {
            return Result::err(
                $ret,
                [ 'The `id` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->value = $id;

        return Result::ok($ret, $instance);
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
