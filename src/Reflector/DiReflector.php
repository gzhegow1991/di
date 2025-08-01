<?php

namespace Gzhegow\Di\Reflector;

use Gzhegow\Lib\Lib;
use Gzhegow\Di\Exception\RuntimeException;


class DiReflector implements DiReflectorInterface
{
    /**
     * @var DiReflectorCacheInterface
     */
    protected $cache;


    public function __construct(
        DiReflectorCacheInterface $cache
    )
    {
        $this->cache = $cache;
    }


    /**
     * @return static
     */
    public function resetCache()
    {
        $this->cache->resetCache();

        return $this;
    }

    /**
     * @return static
     */
    public function saveCache()
    {
        $this->cache->saveCache();

        return $this;
    }

    /**
     * @return static
     */
    public function clearCache()
    {
        $this->cache->clearCache();

        return $this;
    }


    /**
     * @param callable|object|array|string $callableOrMethod
     */
    public function reflectArguments($callableOrMethod) : array
    {
        $result = null
            ?? $this->reflectArgumentsObject($callableOrMethod)
            ?? $this->reflectArgumentsArray($callableOrMethod)
            ?? $this->reflectArgumentsString($callableOrMethod);

        return $result;
    }

    /**
     * @param callable|object $object
     */
    protected function reflectArgumentsObject($object) : ?array
    {
        if (! is_object($object)) return null;

        $reflectResult = null
            ?? $this->reflectArgumentsObjectClosure($object)
            ?? $this->reflectArgumentsObjectInvokable($object);

        return $reflectResult;
    }

    /**
     * @param callable|object $object
     */
    protected function reflectArgumentsObjectClosure($object) : ?array
    {
        $theCache = $this->cache;
        $thePhp = Lib::php();

        if (! $thePhp->type_callable_object_closure($object, $newScope = null)->isOk()) {
            return null;
        }

        $reflectNamespace = null;

        try {
            $rf = new \ReflectionFunction($object);
        }
        catch ( \ReflectionException $e ) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $reflectKey = '{ \Closure(' . $rf->getFileName() . "\0" . $rf->getStartLine() . "\0" . $rf->getEndLine() . ') }';

        if ($theCache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $theCache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            $theCache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }

    /**
     * @param callable|object $object
     */
    protected function reflectArgumentsObjectInvokable($object) : ?array
    {
        $theCache = $this->cache;
        $thePhp = Lib::php();
        $theType = Lib::type();

        if (! $thePhp->type_callable_object_invokable($object, $newScope = null)->isOk([ &$invokable ])) {
            return null;
        }

        $class = get_class($object);

        $reflectKey = $class . '::__invoke';

        $reflectNamespace = $theType->struct_namespace($class)->orNull();

        if ($theCache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $theCache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            try {
                $rm = new \ReflectionMethod($object, '__invoke');
            }
            catch ( \ReflectionException $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);

            $theCache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }

    /**
     * @param callable|array $array
     */
    protected function reflectArgumentsArray($array) : ?array
    {
        if (! is_array($array)) return null;
        if (! count($array)) return null;

        $reflectResult = null
            ?? $this->reflectArgumentsArrayMethod($array);

        return $reflectResult;
    }

    /**
     * @param callable|array $array
     */
    protected function reflectArgumentsArrayMethod($array) : ?array
    {
        $theCache = $this->cache;
        $thePhp = Lib::php();
        $theType = Lib::type();

        if (! $thePhp->type_method($array, [ &$methodArray, &$methodString ])->isOk()) {
            return null;
        }

        $reflectKey = $methodString;

        $reflectNamespace = $theType->struct_namespace($methodArray[ 0 ])->orNull();

        if ($theCache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $theCache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            try {
                $rf = new \ReflectionMethod($methodArray[ 0 ], $methodArray[ 1 ]);
            }
            catch ( \ReflectionException $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            $theCache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }

    /**
     * @param callable|string|class-string $string
     */
    protected function reflectArgumentsString($string) : ?array
    {
        if (! is_string($string)) return null;
        if ('' === $string) return null;

        $reflectResult = null
            ?? $this->reflectArgumentsStringCallable($string)
            ?? $this->reflectArgumentsStringInvokableClass($string);

        return $reflectResult;
    }

    /**
     * @param callable|string $string
     */
    protected function reflectArgumentsStringCallable($string) : ?array
    {
        $theCache = $this->cache;
        $thePhp = Lib::php();
        $theType = Lib::type();

        if (! $thePhp->type_callable_string($string, $newScope = null)->isOk([ &$callableString ])) {
            return null;
        }

        $isFunction = function_exists($callableString);

        $reflectKey = $callableString;

        $reflectNamespace = null;

        $theClass = null;
        $theMethod = null;
        if ($isFunction) {
            $reflectKey = $string;

        } else {
            // static method

            [ $theClass, $theMethod ] = explode('::', $callableString);

            $reflectNamespace = $theType->struct_namespace($theClass)->orNull();
        }

        if ($theCache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $theCache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            if ($isFunction) {
                try {
                    $rf = new \ReflectionFunction($callableString);
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            } else {
                // > static method

                try {
                    $rm = new \ReflectionMethod($theClass, $theMethod);
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);
            }

            $theCache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
        }

        return $reflectResult;
    }

    /**
     * @param callable|class-string $string
     */
    protected function reflectArgumentsStringInvokableClass($string) : ?array
    {
        if (! class_exists($string)) return null;
        if (! method_exists($string, '__invoke')) return null;

        $theCache = $this->cache;
        $theType = Lib::type();

        $theClass = $string;
        $theMethod = '__invoke';

        $reflectKey = "{$theClass}->__invoke";

        $reflectNamespace = $theType->struct_namespace($theClass)->orNull();

        if ($theCache->hasReflectionResult($reflectKey, $reflectNamespace)) {
            $reflectResult = $theCache->getReflectionResult($reflectKey, $reflectNamespace);

        } else {
            try {
                $rm = new \ReflectionMethod($theClass, $theMethod);
            }
            catch ( \ReflectionException $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $reflectResult = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);

            $theCache->setReflectionResult($reflectResult, $reflectKey, $reflectNamespace);
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

        $cache = $this->cache;
        $theType = Lib::type();

        $reflectKey = $class . '::__construct';

        $reflectNamespace = $theType->struct_namespace($class)->orNull();

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
