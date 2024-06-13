<?php

namespace Gzhegow\Di\Reflector;


class ReflectorFactory implements ReflectorFactoryInterface
{
    public function newReflector() : ReflectorInterface
    {
        $reflectorCache = $this->newReflectorCache();

        $reflector = new Reflector($this, $reflectorCache);

        return $reflector;
    }

    public function newReflectorCache() : ReflectorCacheInterface
    {
        $reflectorCacheRuntime = new ReflectorCache();

        return $reflectorCacheRuntime;
    }
}
