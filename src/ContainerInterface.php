<?php

namespace Gzhegow\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;


interface ContainerInterface extends PsrContainerInterface
{
    public function getDi() : DiInterface;
}
