<?php

namespace Gzhegow\Di\Reflector\Struct;

use Gzhegow\Di\Exception\RuntimeException;


class ReflectorCacheRuntime implements \Serializable
{
    /**
     * @var array<string, array>
     */
    protected $reflectResultDict = [];

    /**
     * @var bool
     */
    protected $isChanged = false;


    /**
     * @return static
     */
    public function reset() // : static
    {
        $this->reflectResultDict = [];

        $this->isChanged = false;

        return $this;
    }


    public function isChanged() : bool
    {
        return $this->isChanged;
    }


    public function hasReflectResult(string $reflectKey, array &$result = null) : bool
    {
        $result = null;

        $status = array_key_exists($reflectKey, $this->reflectResultDict);

        if ($status) {
            $result = $this->reflectResultDict[ $reflectKey ];
        }

        return $status;
    }

    public function getReflectResult(string $reflectKey, array $fallback = []) : array
    {
        $status = $this->hasReflectResult($reflectKey, $result);

        if (! $status) {
            if ($fallback) {
                [ $fallback ] = $fallback;

                return $fallback;
            }

            throw new RuntimeException(
                'Missing cache key: ' . $reflectKey
            );
        }

        return $result;
    }


    /**
     * @return static
     */
    public function setReflectResult(string $reflectKey, array $reflectResult) // : static
    {
        if (array_key_exists($reflectKey, $this->reflectResultDict)) {
            throw new RuntimeException(
                'Cache key already exists: ' . $reflectKey
            );
        }

        $this->reflectResultDict[ $reflectKey ] = $reflectResult;

        $this->isChanged = true;

        return $this;
    }


    public function __serialize() : array
    {
        return [
            'reflectResultDict' => $this->reflectResultDict,
        ];
    }

    public function __unserialize(array $data) : void
    {
        $this->reflectResultDict = $data[ 'reflectResultDict' ];
    }

    public function serialize()
    {
        $array = $this->__serialize();

        return serialize($array);
    }

    public function unserialize($data)
    {
        $array = unserialize($data);

        $this->__unserialize($array);
    }
}
