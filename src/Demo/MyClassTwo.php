<?php

namespace Gzhegow\Di\Demo;

class MyClassTwo implements MyClassTwoInterface
{
    /**
     * @var MyClassOneInterface
     */
    protected $a;

    /**
     * @var string
     */
    protected $hello;


    // public function __construct(MyClassOneInterface $a, string $hello = null)
    // {
    //     // > gzhegow, long init example
    //     sleep(3);
    //
    //     $this->a = $a;
    //     $this->hello = $hello;
    // }

    public function __construct(string $hello = null)
    {
        // > gzhegow, long init example
        sleep(3);

        $this->hello = $hello;
    }


    public function do() : void
    {
        echo "Hello, [ {$this->hello} ]" . PHP_EOL;
    }
}
