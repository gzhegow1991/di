<?php

namespace Gzhegow\Di\Exception;

use Psr\Container\ContainerExceptionInterface;


interface ExceptionInterface extends
    \Throwable,
    //
    ContainerExceptionInterface
{
}
