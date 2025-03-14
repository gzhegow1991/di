<?php

namespace Gzhegow\Di\Demo;

use Gzhegow\Di\LazyService\DiLazyService;


trait MyClassTwoAwareTrait
{
    /**
     * @var DiLazyService<MyClassTwo>|MyClassTwo
     */
    protected $two;

    /**
     * @param DiLazyService<MyClassTwo>|MyClassTwo $two
     */
    public function setTwo($two) : void
    {
        $this->two = $two;
    }
}
