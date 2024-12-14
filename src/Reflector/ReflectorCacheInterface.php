<?php
/**
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpUndefinedNamespaceInspection
 */

namespace Gzhegow\Di\Reflector;

interface ReflectorCacheInterface
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


    public function hasReflectionResult(string $reflectionKey, string $reflectionNamespace = null, array &$result = null) : bool;

    public function getReflectionResult(string $reflectionKey, string $reflectionNamespace = null, array $fallback = []) : array;

    /**
     * @return static
     */
    public function setReflectionResult(array $reflectionResult, string $reflectionKey, string $reflectionNamespace = null);
}
