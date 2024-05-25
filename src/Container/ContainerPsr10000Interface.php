<?php

namespace Gzhegow\Di\Container;

use Gzhegow\Di\DiInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;


interface ContainerPsr10000Interface extends PsrContainerInterface
{
    public function getDi() : DiInterface;
}
