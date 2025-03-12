<?php

namespace Gzhegow\Di;

use Gzhegow\Lib\Config\AbstractConfig;
use Gzhegow\Di\Injector\DiInjectorConfig;
use Gzhegow\Di\Reflector\DiReflectorCacheConfig;


/**
 * @property DiInjectorConfig       $injector
 * @property DiReflectorCacheConfig $reflectorCache
 */
class DiConfig extends AbstractConfig
{
    /**
     * @var DiInjectorConfig
     */
    protected $injector;
    /**
     * @var DiReflectorCacheConfig
     */
    protected $reflectorCache;


    public function __construct()
    {
        $this->injector = new DiInjectorConfig();
        $this->reflectorCache = new DiReflectorCacheConfig();

        parent::__construct();
    }


    public function validate(array $context = [])
    {
        $this->injector->validate();
        $this->reflectorCache->validate();
    }
}
