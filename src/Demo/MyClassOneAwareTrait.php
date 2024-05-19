<?php

namespace Gzhegow\Di\Demo;

trait MyClassOneAwareTrait
{
    /**
     * @var MyClassOne
     */
    protected $one;

    
    public function setOne(MyClassOneInterface $one) : void
    {
        $this->one = $one;
    }
}
