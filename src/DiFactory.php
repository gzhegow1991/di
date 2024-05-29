<?php

namespace Gzhegow\Di;

use Gzhegow\Di\Injector\Injector;
use Gzhegow\Di\Reflector\ReflectorFactory;
use Gzhegow\Di\Injector\InjectorInterface;
use Gzhegow\Di\Reflector\ReflectorInterface;


class DiFactory implements DiFactoryInterface
{
    public function newDi(InjectorInterface $injector = null) : DiInterface
    {
        $injector = $injector ?? $this->newInjector();

        $di = new Di(
            $this,
            $injector,
            $injector->getReflector()
        );

        return $di;
    }

    public function newInjector(ReflectorInterface $reflector = null) : InjectorInterface
    {
        $reflector = $reflector ?? (new ReflectorFactory())->newReflector();

        $injector = new Injector($reflector);

        return $injector;
    }
}
