<?php

namespace Gzhegow\Di\Reflector;

use Gzhegow\Di\Reflector\Struct\ReflectorCacheRuntime;


class ReflectorFactory implements ReflectorFactoryInterface
{
    public function newReflector() : ReflectorInterface
    {
        $reflector = new Reflector($this);

        return $reflector;
    }

    public function newReflectorCacheRuntime() : ReflectorCacheRuntime
    {
        $reflectorCacheRuntime = new ReflectorCacheRuntime();

        return $reflectorCacheRuntime;
    }
}
