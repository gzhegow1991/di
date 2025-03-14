<?php

namespace Gzhegow\Di;

use Gzhegow\Di\LazyService\DiLazyServiceFactory;
use Gzhegow\Di\LazyService\DiLazyServiceFactoryInterface;


class DiFactory implements DiFactoryInterface
{
    public function newLazyServiceFactory(DiInterface $di) : DiLazyServiceFactoryInterface
    {
        return new DiLazyServiceFactory($di);
    }
}
