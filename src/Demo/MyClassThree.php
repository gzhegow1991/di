<?php

namespace Gzhegow\Di\Demo;

class MyClassThree implements
    MyClassOneAwareInterface,
    MyClassTwoAwareInterface
{
    use MyClassOneAwareTrait;
    use MyClassTwoAwareTrait;


    /**
     * @return MyClassOneInterface
     */
    public function getOne() : object
    {
        return $this->one;
    }

    /**
     * @return MyClassTwoInterface
     */
    public function getTwo() : object
    {
        return $this->two;
    }
}
