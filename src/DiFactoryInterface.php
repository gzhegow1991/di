<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Injector\InjectorInterface;
use Gzhegow\Di\Reflector\ReflectorInterface;


interface DiFactoryInterface
{
    public function newDi(InjectorInterface $injector = null) : DiInterface;

    public function newInjector(ReflectorInterface $reflector = null) : InjectorInterface;
}
