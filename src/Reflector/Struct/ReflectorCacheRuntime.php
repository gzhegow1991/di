<?php

namespace Gzhegow\Di\Reflector\Struct;

use Gzhegow\Di\Exception\RuntimeException;


class ReflectorCacheRuntime implements \Serializable
{
    /**
     * @var array<string, array>
     */
    protected $items = [];

    /**
     * @var bool
     */
    protected $isChanged = false;


    /**
     * @return static
     */
    public function reset() // : static
    {
        $this->items = [];

        $this->isChanged = false;

        return $this;
    }


    public function isChanged() : bool
    {
        return $this->isChanged;
    }


    public function has(string $key, array &$result = null) : bool
    {
        $result = null;

        $status = array_key_exists($key, $this->items);

        if ($status) {
            $result = $this->items[ $key ];
        }

        return $status;
    }

    public function get(string $key, array $fallback = []) : array
    {
        $status = $this->has($key, $result);

        if (! $status) {
            if ($fallback) {
                [ $fallback ] = $fallback;

                return $fallback;
            }

            throw new RuntimeException(
                'Missing cache key: ' . $key
            );
        }

        return $result;
    }


    /**
     * @return static
     */
    public function set(string $reflectKey, array $reflectResult) // : static
    {
        if (array_key_exists($reflectKey, $this->items)) {
            throw new RuntimeException(
                'Cache key already exists: ' . $reflectKey
            );
        }

        $this->items[ $reflectKey ] = $reflectResult;

        $this->isChanged = true;

        return $this;
    }


    public function __serialize() : array
    {
        return [ 'items' => $this->items ];
    }

    public function __unserialize(array $data) : void
    {
        $this->items = $data[ 'items' ];
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
