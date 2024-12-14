<?php

namespace Gzhegow\Di;

use Gzhegow\Lib\Config\Config;
use Gzhegow\Di\Injector\InjectorConfig;
use Gzhegow\Di\Reflector\ReflectorCacheConfig;


/**
 * @property InjectorConfig       $injector
 * @property ReflectorCacheConfig $reflectorCache
 */
class DiConfig extends Config
{
    /**
     * @var InjectorConfig
     */
    protected $injector;
    /**
     * @var ReflectorCacheConfig
     */
    protected $reflectorCache;


    public function __construct()
    {
        $this->__sections[ 'injector' ] = $this->injector = new InjectorConfig();
        $this->__sections[ 'reflectorCache' ] = $this->reflectorCache = new ReflectorCacheConfig();
    }

    public function validate() : void
    {
        $this->injector->validate();
        $this->reflectorCache->validate();
    }
}
