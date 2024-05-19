<?php

namespace Gzhegow\Di\Demo;

class MyClassOne implements MyClassOneInterface
{
    protected $a;

    public function __construct(string $a)
    {
        $this->a = $a;
    }
}
