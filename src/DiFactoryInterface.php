<?php

namespace Gzhegow\Di;

use Gzhegow\Di\LazyService\LazyServiceFactoryInterface;


interface DiFactoryInterface
{
    public function newLazyServiceFactory(DiInterface $di) : LazyServiceFactoryInterface;
}
