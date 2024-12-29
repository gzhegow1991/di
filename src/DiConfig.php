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
        $this->__sections[ 'injector' ] = $this->injector = new DiInjectorConfig();
        $this->__sections[ 'reflectorCache' ] = $this->reflectorCache = new DiReflectorCacheConfig();
    }

    public function validate() : void
    {
        $this->injector->validate();
        $this->reflectorCache->validate();
    }
}
