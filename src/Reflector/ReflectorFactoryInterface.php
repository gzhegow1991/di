<?php

namespace Gzhegow\Di\Reflector;

use Gzhegow\Di\Reflector\Struct\ReflectorCacheRuntime;


interface ReflectorFactoryInterface
{
    public function newReflector() : ReflectorInterface;

    public function newReflectorCacheRuntime() : ReflectorCacheRuntime;
}
