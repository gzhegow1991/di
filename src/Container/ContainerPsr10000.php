<?php

namespace Gzhegow\Di\Container;

use Gzhegow\Di\DiInterface;


class ContainerPsr10000 implements ContainerPsr10000Interface
{
    /**
     * @var DiInterface
     */
    protected $di;


    public function __construct(DiInterface $di)
    {
        $this->di = $di;
    }


    public function getDi() : DiInterface
    {
        return $this->di;
    }


    public function has($id) : bool
    {
        return $this->di->has($id);
    }

    public function get($id) // : mixed
    {
        return $this->di->fetch($id);
    }
}
