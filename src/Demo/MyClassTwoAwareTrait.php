<?php

namespace Gzhegow\Di\Demo;

use Gzhegow\Di\LazyService\LazyService;


trait MyClassTwoAwareTrait
{
    /**
     * @var LazyService<MyClassTwo>|MyClassTwo
     */
    protected $two;

    /**
     * @param LazyService<MyClassTwo>|MyClassTwo $two
     */
    public function setTwo($two) : void
    {
        $this->two = $two;
    }
}
