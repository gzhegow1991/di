<?php

namespace Gzhegow\Di;

use Gzhegow\Di\LazyService\DiLazyServiceFactoryInterface;


interface DiFactoryInterface
{
    public function newLazyServiceFactory(DiInterface $di) : DiLazyServiceFactoryInterface;
}
