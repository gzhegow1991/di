<?php

namespace Gzhegow\Di\Exception\Runtime;

use Gzhegow\Di\Exception\RuntimeException;
use Psr\Container\NotFoundExceptionInterface as PsrNotFoundExceptionInterface;


class NotFoundException extends RuntimeException implements
    PsrNotFoundExceptionInterface
{
}
