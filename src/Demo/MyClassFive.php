<?php

namespace Gzhegow\Di\Demo;

class MyClassFive
{
    /**
     * @var MyClassFour
     */
    public $four;


    public function __construct()
    {
        // > gzhegow, пустой конструктор, чтобы протестировать, что для пустых функций аргументы равны пустому массиву
    }


    public function __autowire(MyClassFour $four)
    {
        $this->four = $four;
    }
}
