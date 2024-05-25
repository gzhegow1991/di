<?php

namespace Gzhegow\Di\Container;

use Gzhegow\Di\DiInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;


interface ContainerPsrInterface extends PsrContainerInterface
{
    public function getDi() : DiInterface;
}
