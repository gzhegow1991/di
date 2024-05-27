<?php

namespace Gzhegow\Di\Demo;

class MyClassFive
{
    /**
     * @var MyClassFour
     */
    public $four;


    public function __autowire(MyClassFour $four)
    {
        $this->four = $four;
    }
}
