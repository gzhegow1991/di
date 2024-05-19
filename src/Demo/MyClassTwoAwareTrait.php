<?php

namespace Gzhegow\Di\Demo;

use Gzhegow\Di\Lazy\LazyService;


trait MyClassTwoAwareTrait
{
    /**
     * @var LazyService<MyClassTwo>|MyClassTwo
     */
    protected $two;


    public function setTwo(LazyService $two) : void
    {
        $this->two = $two;
    }
}
