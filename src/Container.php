<?php

namespace Gzhegow\Di;


class Container implements ContainerInterface
{
    /** > gzhegow, это адаптер для Psr\Container\ContainerInterface */


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

    public function get(string $id) // : mixed
    {
        return $this->di->get($id);
    }
}
