<?php

namespace Gzhegow\Di;


trait DiAwareTrait
{
    /**
     * @var DiInterface
     */
    protected $di;


    public function setDi(DiInterface $di) : void
    {
        $this->di = $di;
    }
}
