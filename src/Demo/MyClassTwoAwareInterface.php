<?php

namespace Gzhegow\Di\Demo;

use Gzhegow\Di\Lazy\LazyService;


interface MyClassTwoAwareInterface
{
    /**
     * @param LazyService<MyClassTwo>|MyClassTwo $two
     */
    public function setTwo($two);
}
