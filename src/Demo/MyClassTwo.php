<?php

namespace Gzhegow\Di\Demo;

class MyClassTwo implements MyClassTwoInterface
{
    /**
     * @var MyClassOneInterface
     */
    protected $a;


    public function __construct(MyClassOneInterface $a)
    {
        // > gzhegow, long init
        sleep(3);

        $this->a = $a;
    }


    public function do() : void
    {
        echo 'Hello, World!' . PHP_EOL;
    }
}
