<?php

namespace Gzhegow\Di\Demo;

use Gzhegow\Di\Lazy\LazyService;


interface MyClassTwoAwareInterface
{
    public function setTwo(LazyService $two);
}
