<?php

namespace Gzhegow\Di\Reflector;

use Gzhegow\Di\Exception\LogicException;
use Gzhegow\Di\Exception\RuntimeException;
use Gzhegow\Di\Reflector\Struct\ReflectorCacheRuntime;
use function Gzhegow\Di\_php_dump;
use function Gzhegow\Di\_filter_dirpath;
use function Gzhegow\Di\_filter_filename;
use function Gzhegow\Di\_php_method_exists;


class Reflector implements ReflectorInterface
{
    const CACHE_MODE_RUNTIME  = 'RUNTIME';
    const CACHE_MODE_NO_CACHE = 'NO_CACHE';
    const CACHE_MODE_STORAGE  = 'STORAGE';

    const LIST_CACHE_MODE = [
        self::CACHE_MODE_RUNTIME  => true,
        self::CACHE_MODE_NO_CACHE => true,
        self::CACHE_MODE_STORAGE  => true,
    ];


    /**
     * @var ReflectorFactoryInterface
     */
    protected $factory;

    /**
     * @var string
     */
    protected $cacheMode = self::CACHE_MODE_RUNTIME;

    /**
     * @var object|\Psr\Cache\CacheItemPoolInterface
     *
     * @noinspection PhpUndefinedNamespaceInspection
     * @noinspection PhpUndefinedClassInspection
     */
    protected $cacheAdapter;

    /**
     * @var string
     */
    protected $cacheDirpath = __DIR__ . '/../../var/cache/app.di';
    /**
     * @var string
     */
    protected $cacheFilename = 'reflector.cache';

    /**
     * @var ReflectorCacheRuntime
     */
    protected $cache;
    /**
     * @var object|\Psr\Cache\CacheItemInterface
     *
     * @noinspection PhpUndefinedNamespaceInspection
     * @noinspection PhpUndefinedClassInspection
     */
    protected $cacheItem;


    public function __construct(ReflectorFactoryInterface $factory)
    {
        $this->factory = $factory;
    }


    public function resetCache() // : static
    {
        $this->cache = null;
        $this->cacheItem = null;

        return $this;
    }

    public function loadCache(bool $readData = null) : ReflectorCacheRuntime
    {
        $readData = $readData ?? true;

        if ($this->cache) {
            return $this->cache;
        }

        if ($this->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->cacheAdapter) {
                try {
                    $cacheItem = $this->cacheAdapter->getItem(__CLASS__);

                    $this->cacheItem = $cacheItem;

                    if ($readData) {
                        if ($cacheItem->isHit()) {
                            $cache = $cacheItem->get();
                        }
                    }
                }
                catch ( \Throwable $e ) {
                    $cache = null;
                }

                $cache = $cache ?? $this->factory->newReflectorCacheRuntime();

            } elseif ($this->cacheDirpath) {
                $cacheFilepath = "{$this->cacheDirpath}/{$this->cacheFilename}";

                $cache = null;

                if ($readData) {
                    $before = error_reporting(0);
                    if (@is_file($cacheFilepath)) {
                        if (false !== ($content = @file_get_contents($cacheFilepath))) {
                            $cache = $content;

                        } else {
                            throw new RuntimeException(
                                'Unable to read file: ' . $cacheFilepath
                            );
                        }

                        $cache = unserialize($cache);

                        if (get_class($cache) === '__PHP_Incomplete_Class') {
                            $cache = null;
                        }
                    }
                    error_reporting($before);
                }

                $cache = $cache ?? $this->factory->newReflectorCacheRuntime();

            } else {
                $cache = $this->factory->newReflectorCacheRuntime();
            }

        } else {
            $cache = $this->factory->newReflectorCacheRuntime();
        }

        $this->cache = $cache;

        return $this->cache;
    }

    public function clearCache() // : static
    {
        $cache = $this->loadCache(true);

        $cache->reset();

        if ($this->cacheMode === static::CACHE_MODE_STORAGE) {
            if ($this->cacheAdapter) {
                $cacheAdapter = $this->cacheAdapter;

                $cacheAdapter->clear();

            } elseif ($this->cacheDirpath) {
                $cacheFilepath = "{$this->cacheDirpath}/{$this->cacheFilename}";

                $before = error_reporting(0);
                $status = true;
                if (@is_file($cacheFilepath)) {
                    $status = @unlink($cacheFilepath);
                }
                error_reporting($before);

                if (! $status) {
                    throw new RuntimeException(
                        'Unable to delete file: ' . $cacheFilepath
                    );
                }
            }
        }

        return $this;
    }

    public function flushCache() // : static
    {
        $cache = $this->loadCache();

        if ($this->cacheMode === static::CACHE_MODE_NO_CACHE) {
            $cache->reset();

            return $this;
        }

        if ($this->cacheMode === static::CACHE_MODE_STORAGE) {
            if (! $cache->isChanged()) {
                return $this;
            }

            if ($this->cacheAdapter) {
                $cacheAdapter = $this->cacheAdapter;
                $cacheItem = $this->cacheItem;

                $cacheItem->set($cache);

                $cacheAdapter->save($cacheItem);

            } elseif ($this->cacheDirpath) {
                $cacheFilepath = "{$this->cacheDirpath}/{$this->cacheFilename}";

                $content = serialize($cache);

                $before = error_reporting(0);
                if (! @is_dir($this->cacheDirpath)) {
                    @mkdir($this->cacheDirpath, 0775, true);
                }
                $status = @file_put_contents($cacheFilepath, $content);
                error_reporting($before);

                if (! $status) {
                    throw new RuntimeException(
                        'Unable to write file: ' . $cacheFilepath
                    );
                }
            }
        }

        return $this;
    }


    /**
     * @param string|null                                   $cacheMode
     * @param object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
     * @param string|null                                   $cacheDirpath
     * @param string|null                                   $cacheFilename
     *
     * @noinspection PhpUndefinedNamespaceInspection
     * @noinspection PhpUndefinedClassInspection
     */
    public function setCacheSettings(
        string $cacheMode = null,
        object $cacheAdapter = null,
        string $cacheDirpath = null,
        string $cacheFilename = null
    ) // : static
    {
        if ((null !== $cacheMode) && ! isset(static::LIST_CACHE_MODE[ $cacheMode ])) {
            throw new LogicException(
                'The `cacheMode` should be one of: ' . implode('|', array_keys(static::LIST_CACHE_MODE))
                . ' / ' . $cacheMode
            );
        }

        if ((null !== $cacheAdapter) && ! is_a($cacheAdapter, $class = '\Psr\Cache\CacheItemPoolInterface')) {
            throw new LogicException(
                'The `cacheAdapter` should be instance of: ' . $class
                . ' / ' . _php_dump($cacheAdapter)
            );
        }

        if ((null !== $cacheDirpath) && (null === _filter_dirpath($cacheDirpath))) {
            throw new LogicException(
                'The `cacheDirpath` should be valid directory path: ' . $cacheDirpath
            );
        }

        if ((null !== $cacheFilename) && (null === _filter_filename($cacheFilename))) {
            throw new LogicException(
                'The `cacheFilename` should be valid filename: ' . $cacheFilename
            );
        }

        $this->cacheMode = $cacheMode ?? static::CACHE_MODE_RUNTIME;
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheDirpath = $cacheDirpath ?? __DIR__ . '/../../var/cache/app.di';
        $this->cacheFilename = $cacheFilename ?? 'reflector.cache';

        $this->resetCache();

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
            $reflectKey = get_class($object) . '::__invoke';
        }

        $cache = $this->loadCache();

        if ($cache->has($reflectKey)) {
            $result = $cache->get($reflectKey);

        } else {
            if ($isClosure) {
                $result = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            } else {
                // } elseif ($isInvokable) {
                try {
                    $rm = new \ReflectionMethod($object, '__invoke');
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $result = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);
            }

            $cache->set($reflectKey, $result);
        }

        return $result;
    }

    /**
     * @param callable|array $array
     */
    protected function reflectArgumentsCallableArray($array) : ?array
    {
        if (! is_array($array)) return null;
        if (! _php_method_exists($array, '', $methodArray, $methodString)) return null;

        $reflectKey = $methodString;

        $cache = $this->loadCache();

        if ($cache->has($reflectKey)) {
            $result = $cache->get($reflectKey);

        } else {
            try {
                $rf = new \ReflectionMethod($methodArray[ 0 ], $methodArray[ 1 ]);
            }
            catch ( \ReflectionException $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $result = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            $cache->set($reflectKey, $result);
        }

        return $result;
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

        if ($isFunction) {
            $reflectKey = $string;

        } else {
            // } elseif ($isInvokable) {
            $reflectKey = "{$string}::__invoke";
        }

        $cache = $this->loadCache();

        if ($cache->has($reflectKey)) {
            $result = $cache->get($reflectKey);

        } else {
            if ($isFunction) {
                try {
                    $rf = new \ReflectionFunction($string);
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $result = $this->resolveReflectionFunctionAbstract($reflectKey, $rf);

            } else {
                // } elseif ($isInvokable) {
                try {
                    $rm = new \ReflectionMethod($string, '__invoke');
                }
                catch ( \ReflectionException $e ) {
                    throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
                }

                $result = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);
            }

            $cache->set($reflectKey, $result);
        }

        return $result;
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

        $cache = $this->loadCache();

        if ($cache->has($reflectKey)) {
            $result = $cache->get($reflectKey);

        } else {
            try {
                $rc = new \ReflectionClass($class);
            }
            catch ( \Throwable $e ) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $rm = $rc->getConstructor();

            $result = $this->resolveReflectionFunctionAbstract($reflectKey, $rm);

            $cache->set($reflectKey, $result);
        }

        return $result;
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


    public static function getInstance() // : static
    {
        $instance = static::$instances[ static::class ];

        if (! is_a($instance, static::class)) {
            throw new RuntimeException(
                'No instance bound. Please, call Di::setInstance() first.'
            );
        }

        return $instance;
    }

    /**
     * @param static $reflector
     *
     * @return void
     */
    public static function setInstance($reflector) : void
    {
        if (! is_a($reflector, static::class)) {
            throw new RuntimeException(
                'The `reflector` should be instance of: ' . static::class
                . ' / ' . _php_dump($reflector)
            );
        }

        static::$instances[ get_class($reflector) ] = $reflector;
    }

    /**
     * @var array<class-string, static>
     */
    protected static $instances = [];
}
