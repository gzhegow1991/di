<?php

namespace Gzhegow\Di;

use Gzhegow\Di\LazyService\LazyServiceFactory;
use Gzhegow\Di\LazyService\LazyServiceFactoryInterface;


class DiFactory implements DiFactoryInterface
{
    public function newLazyServiceFactory(DiInterface $di) : LazyServiceFactoryInterface
    {
        return new LazyServiceFactory($di);
    }
}
