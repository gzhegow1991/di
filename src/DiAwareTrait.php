<?php

namespace Gzhegow\Di;


trait DiAwareTrait
{
    /**
     * @var Di
     */
    protected $di;


    public function setDi(DiInterface $di) : void
    {
        $this->di = $di;
    }
}
