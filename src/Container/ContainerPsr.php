<?php

namespace Gzhegow\Di\Container;

use Gzhegow\Di\DiInterface;


class ContainerPsr implements ContainerPsrInterface
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


    public function has(string $id) : bool
    {
        return $this->di->has($id);
    }

    public function get(string $id)
    {
        return $this->di->fetch($id);
    }
}
