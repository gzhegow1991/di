<?php

namespace Gzhegow\Di\Reflector;

use Gzhegow\Lib\Lib;
use Gzhegow\Di\Exception\RuntimeException;


class Reflector implements ReflectorInterface
{
    /**
     * @var ReflectorCacheInterface
     */
    protected $cache;


    public function __construct(
        ReflectorCacheInterface $cache
    )
    {
        $this->cache = $cache;
    }


    /**
     * @return static
     */
    public function resetCache() // : static
    {
        $this->cache->resetCache();

        return $this;
    }

    /**
     * @return static
     */
    public function saveCache() // : static
    {
        $this->cache->saveCache();

        return $this;
    }

    /**
     * @return static
     */
    public function clearCache() // : static
    {
        $this->cache->clearCache();

        return $this;
    }


    /**
     * @param callable|object|array|string $callable
     */
    public function reflectArgumentsCallable($callable) : array
    {
        $result = null
            ?? $this->reflectArgumentsCallableObject($callable)
            ?? $this->reflectArgumentsCallableArray($callable)
            ?? $this->reflectArgumentsCallableString($callable);

        return $result;
    }

    /**
     * @param callable|object $object
     */
    protected function reflectArgumentsCallableObject($object) : ?array
    {
        if (! is_object($object)) return null;

        $isClosure = false;
        $isInvokable = false;

        false
        || ($isClosure = ($object instanceof \Closure))
        || ($isInvokable = method_exists($object, '__invoke'));

        if (! ($isClosure || $isInvokable)) return null;

        $reflectKey = null;
        $reflectNamespace = null;
        $reflectResult = null;

        if ($isClosure) {
            try {
                $rf = new \ReflectionFunction($object);
            }
            catch ( \ReflectionException $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $reflectKey = '{ \Closure(' . $rf->getFileName() . "\0" . $rf->getStartLine() . "\0" . $rf->getEndLine() . ') }';

        } else {
            // } elseif ($isInvokable) {

            $class = get_class($object);

            $reflectKey = $class . '::__invoke';
            $reflectNamespace = Lib::php_dirname($class, '\\');
        }

        $cache = $this->cache;

        if ($cache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $cache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            if ($isClosure) {
                $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            } else {
                // } elseif ($isInvokable) {

                try {
                    $rm = new \ReflectionMethod($object, '__invoke');
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);
            }

            $cache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }

    /**
     * @param callable|array $array
     */
    protected function reflectArgumentsCallableArray($array) : ?array
    {
        if (! is_array($array)) return null;
        if (! Lib::php_method_exists($array, '', $methodArray, $methodString)) return null;

        $reflectKey = $methodString;
        $reflectNamespace = Lib::php_dirname($methodArray[ 0 ], '\\');
        $reflectResult = null;

        $cache = $this->cache;

        if ($cache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $cache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            try {
                $rf = new \ReflectionMethod($methodArray[ 0 ], $methodArray[ 1 ]);
            }
            catch ( \ReflectionException $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            $cache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }

    /**
     * @param callable|string|class-string $string
     */
    protected function reflectArgumentsCallableString($string) : ?array
    {
        if (! is_string($string)) return null;

        $isFunction = false;
        $isInvokable = false;

        false
        || ($isFunction = function_exists($string))
        || ($isInvokable = method_exists($string, '__invoke'));

        if (! ($isFunction || $isInvokable)) return null;

        $reflectKey = null;
        $reflectNamespace = null;
        $reflectResult = null;

        if ($isFunction) {
            $reflectKey = $string;

        } else {
            // } elseif ($isInvokable) {

            $reflectKey = "{$string}::__invoke";
            $reflectNamespace = Lib::php_dirname($string, '\\');
        }

        $cache = $this->cache;

        if ($cache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $cache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            if ($isFunction) {
                try {
                    $rf = new \ReflectionFunction($string);
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            } else {
                // } elseif ($isInvokable) {

                try {
                    $rm = new \ReflectionMethod($string, '__invoke');
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);
            }

            $cache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }


    /**
     * @param object|class-string $objectOrClass
     */
    public function reflectArgumentsConstructor($objectOrClass) : array
    {
        $result = null
            ?? $this->reflectArgumentsConstructorObject($objectOrClass)
            ?? $this->reflectArgumentsConstructorClass($objectOrClass);

        return $result;
    }

    /**
     * @param object $object
     */
    protected function reflectArgumentsConstructorObject($object) : ?array
    {
        if (! is_object($object)) return null;

        $class = get_class($object);

        $result = $this->reflectArgumentsConstructorClass($class);

        return $result;
    }

    /**
     * @param class-string $class
     */
    protected function reflectArgumentsConstructorClass($class) : ?array
    {
        if (! is_string($class)) return null;
        if (! class_exists($class)) return null;

        $reflectKey = $class . '::__construct';
        $reflectNamespace = Lib::php_dirname($class, '\\');
        $reflectResult = null;

        $cache = $this->cache;

        if ($cache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $cache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            try {
                $rc = new \ReflectionClass($class);
            }
            catch ( \Throwable $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $rm = $rc->getConstructor();

            $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);

            $cache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }


    protected function resolveReflectionFunctionAbstract(string $reflectionKey, ?\ReflectionFunctionAbstract $reflectionFunctionAbstract) : array
    {
        $result = [];
        $result[ 'key' ] = $reflectionKey;

        if (! $reflectionFunctionAbstract) {
            $result[ 'name' ] = null;
            $result[ 'file' ] = null;
            $result[ 'line' ] = null;
            $result[ 'arguments' ] = null;
            $result[ 'return' ] = null;

        } else {
            $rfName = $reflectionFunctionAbstract->getName();
            $rfFile = $reflectionFunctionAbstract->getFileName();
            $rfLine = $reflectionFunctionAbstract->getStartLine();
            $rfParams = $reflectionFunctionAbstract->getParameters();
            $rfReturn = $reflectionFunctionAbstract->getReturnType();

            $rfParamsResolved = [];
            foreach ( $rfParams as $i => $reflectionParameter ) {
                $reflectionType = $reflectionParameter->getType();

                [
                    'list'       => $rtList,
                    'tree'       => $rtTree,
                    'isNullable' => $rtIsNullable,
                ] = $this->resolveReflectionType($reflectionType);

                $rpName = $reflectionParameter->getName();
                $rpIsNullable = $rtIsNullable || $reflectionParameter->isOptional();

                $rfParamsResolved[ $i ] = [ $rpName, $rtList, $rtTree, $rpIsNullable ];
            }

            $rfReturnResolved = $rfReturn
                ? $this->resolveReflectionType($rfReturn)
                : null;

            $result = [];
            $result[ 'name' ] = $rfName;
            $result[ 'file' ] = $rfFile ?: null;
            $result[ 'line' ] = $rfLine ?: null;
            $result[ 'arguments' ] = $rfParamsResolved;
            $result[ 'return' ] = $rfReturnResolved;
        }

        return $result;
    }

    protected function resolveReflectionType(?\ReflectionType $reflectionType) : array
    {
        $list = [];
        $tree = [];
        $isNullable = false;

        if (! $reflectionType) {
            $root = '';
            $id = '0';

            $list[ $id ] = [
                'name'       => 'mixed',
                'class'      => null,
                'allowsNull' => true,
            ];

            $tree[ $root ][ "\0" ] = 'and';
            $tree[ $root ][ $id ] = true;

            $isNullable = true;

        } else {
            $stack = [];
            $stack[] = [ $reflectionType, $fullpath = [ '0' ], $logic = 'and' ];

            while ( $stack ) {
                [ $reflectionType, $fullpath, $logic ] = array_pop($stack);

                $isReflectionTypeNamed = $reflectionType && is_a($reflectionType, '\ReflectionNamedType');
                $isReflectionTypeUnion = $reflectionType && is_a($reflectionType, '\ReflectionUnionType');
                $isReflectionTypeIntersection = $reflectionType && is_a($reflectionType, '\ReflectionIntersectionType');

                if ($isReflectionTypeUnion || $isReflectionTypeIntersection) {
                    if ($isReflectionTypeUnion) {
                        $logic = 'or';

                    } elseif ($isReflectionTypeIntersection) {
                        $logic = 'and';
                    }

                    $array = $reflectionType->getTypes();

                    end($array);
                    while ( null !== ($k = key($array)) ) {
                        $fullpathChild = $fullpath;
                        $fullpathChild[] = $k;

                        $stack[] = [ $reflectionType, $fullpath, $logic ];

                        prev($array);
                    }

                } elseif ($isReflectionTypeNamed) {
                    $isRtNamedClass = ! $reflectionType->isBuiltin();

                    $reflectionTypeName = $reflectionType->getName();
                    $reflectionTypeClass = $isRtNamedClass ? $reflectionTypeName : null;
                    $reflectionTypeAllowsNull = $reflectionType->allowsNull();

                    $key = implode('.', $fullpath);

                    $path = array_slice($fullpath, 0, -1);
                    $keyParent = implode('.', $path);

                    $list[ $key ] = [
                        'name'       => $reflectionTypeName,
                        'class'      => $reflectionTypeClass,
                        'allowsNull' => $reflectionTypeAllowsNull,
                    ];

                    $tree[ $keyParent ][ "\0" ] = $logic;
                    $tree[ $keyParent ][ $key ] = true;

                    $isNullable = $isNullable || $reflectionTypeAllowsNull;

                } else {
                    $root = '';
                    $id = '0';

                    $list[ $id ] = [
                        'name'       => 'mixed',
                        'class'      => null,
                        'allowsNull' => true,
                    ];

                    $tree[ $root ][ "\0" ] = $logic;
                    $tree[ $root ][ $id ] = true;

                    $isNullable = true;
                }
            }
        }

        return [
            'list'       => $list,
            'tree'       => $tree,
            'isNullable' => $isNullable,
        ];
    }
}
