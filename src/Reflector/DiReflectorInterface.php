<?php

namespace Gzhegow\Di\Reflector;


interface DiReflectorInterface
{
    /**
     * @return static
     */
    public function resetCache();

    /**
     * @return static
     */
    public function saveCache();

    /**
     * @return static
     */
    public function clearCache();


    /**
     * @param callable|object|array|string $callableOrMethod
     */
    public function reflectArguments($callableOrMethod) : array;

    /**
     * @param object|class-string $objectOrClass
     */
    public function reflectArgumentsConstructor($objectOrClass) : array;
}
