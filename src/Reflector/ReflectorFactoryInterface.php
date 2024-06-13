<?php

namespace Gzhegow\Di\Reflector;


interface ReflectorFactoryInterface
{
    public function newReflector() : ReflectorInterface;

    public function newReflectorCache() : ReflectorCacheInterface;
}
