<?php

namespace Gzhegow\Di\Exception\Runtime;

use Psr\Container\NotFoundExceptionInterface;


class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
