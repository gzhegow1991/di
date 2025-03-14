<?php

namespace Gzhegow\Di\Demo;

use Gzhegow\Di\LazyService\DiLazyService;


interface MyClassTwoAwareInterface
{
    /**
     * @param DiLazyService<MyClassTwo>|MyClassTwo $two
     */
    public function setTwo($two);
}
