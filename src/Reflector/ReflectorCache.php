<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di\Reflector;

use Gzhegow\Di\Lib;
use Gzhegow\Di\Exception\LogicException;
use Gzhegow\Di\Exception\RuntimeException;


class ReflectorCache implements ReflectorCacheInterface
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
     * @var string
     */
    protected $cacheMode = self::CACHE_MODE_RUNTIME;
    /**
     * @var object|\Psr\Cache\CacheItemPoolInterface
     */
    protected $cacheAdapter;
    /**
     * @var string
     */
    protected $cacheDirpath = __DIR__ . '/../../var/cache/app.di';

    /**
     * @var array<string, array<string, array>>
     */
    protected $cacheDict = [];

    /**
     * @var bool
     */
    protected $isChanged = false;


    /**
     * @param string|null                                   $cacheMode
     * @param object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
     * @param string|null                                   $cacheDirpath
     *
     * @return static
     */
    public function setCacheSettings(
        string $cacheMode = null,
        object $cacheAdapter = null,
        string $cacheDirpath = null
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
                . ' / ' . Lib::php_dump($cacheAdapter)
            );
        }

        if ((null !== $cacheDirpath) && (null === Lib::filter_dirpath($cacheDirpath))) {
            throw new LogicException(
                'The `cacheDirpath` should be valid directory path: ' . $cacheDirpath
            );
        }

        $this->cacheMode = $cacheMode ?? static::CACHE_MODE_RUNTIME;
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheDirpath = $cacheDirpath ?? __DIR__ . '/../../var/cache/app.di';

        $this->reset();

        return $this;
    }


    /**
     * @return static
     */
    public function reset() // : static
    {
        $this->cacheDict = [];

        $this->isChanged = false;

        return $this;
    }

    /**
     * @return static
     */
    public function clear() // : static
    {
        $isStorage = ($this->cacheMode === static::CACHE_MODE_STORAGE);
        $isAdapter = (null !== $this->cacheAdapter);

        $this->reset();

        if (! $isStorage) return $this;

        if ($isStorage) {
            if ($isAdapter) {
                $this->cacheAdapter->clear();

            } else {
                Lib::fs_clear_dir($this->cacheDirpath);
            }
        }

        return $this;
    }

    /**
     * @return static
     */
    public function flush() // : static
    {
        if (! $this->isChanged) return $this;

        $isStorage = ($this->cacheMode === static::CACHE_MODE_STORAGE);
        $isAdapter = (null !== $this->cacheAdapter);

        if (! $isStorage) return $this;

        if ($isStorage) {
            if ($isAdapter) {
                foreach ( $this->cacheDict as $reflectNamespace => $cacheData ) {
                    $cacheItem = $this->cacheAdapterGetItem($reflectNamespace);

                    $cacheItem->set($cacheData);

                    $this->cacheAdapter->saveDeferred($cacheItem);
                }

                $this->cacheAdapter->commit();

            } else {
                foreach ( $this->cacheDict as $reflectNamespace => $cacheData ) {
                    $cacheKey = $reflectNamespace;

                    $cacheFilename = $this->cacheFilename($cacheKey);
                    $cacheFilepath = "{$this->cacheDirpath}/{$cacheFilename}";

                    $content = $this->serializeData($cacheData);

                    Lib::fs_file_put_contents($cacheFilepath, $content);
                }
            }
        }

        return $this;
    }


    public function hasReflectResult(string $reflectKey, string $reflectNamespace = null, array &$result = null) : bool
    {
        $result = null;

        $reflectNamespace = $reflectNamespace ?? '-';

        if (! isset($this->cacheDict[ $reflectNamespace ][ $reflectKey ])) {
            $isStorage = ($this->cacheMode === static::CACHE_MODE_STORAGE);
            $isAdapter = (null !== $this->cacheAdapter);

            if ($isStorage) {
                $cacheKey = $reflectNamespace;

                if ($isAdapter) {
                    $cacheItem = $this->cacheAdapterGetItem($cacheKey);

                    if ($cacheItem->isHit()) {
                        $this->cacheDict += $cacheItem->get();
                    }

                } else {
                    $cacheFilename = $this->cacheFilename($cacheKey);
                    $cacheFilepath = "{$this->cacheDirpath}/{$cacheFilename}";

                    $content = Lib::fs_file_get_contents($cacheFilepath);

                    if (null !== $content) {
                        $this->cacheDict += $this->unserializeData($content) ?? [];
                    }
                }
            }
        }

        $status = isset($this->cacheDict[ $reflectNamespace ][ $reflectKey ]);

        if ($status) {
            $result = $this->cacheDict[ $reflectNamespace ][ $reflectKey ];
        }

        return $status;
    }

    public function getReflectResult(string $reflectKey, string $reflectNamespace = null, array $fallback = []) : array
    {
        $status = $this->hasReflectResult($reflectKey, $reflectNamespace, $result);

        if (! $status) {
            if ($fallback) {
                [ $fallback ] = $fallback;

                return $fallback;
            }

            throw new RuntimeException(
                'Missing cache key: ' . $reflectKey
            );
        }

        return $result;
    }


    /**
     * @return static
     */
    public function setReflectResult(array $reflectResult, string $reflectKey, string $reflectNamespace = null) // : static
    {
        $reflectNamespace = $reflectNamespace ?? '-';

        $current = $this->cacheDict[ $reflectNamespace ][ $reflectKey ] ?? null;

        if (null !== $current) {
            throw new RuntimeException(
                'Cache key already exists: ' . $reflectKey
            );
        }

        $this->cacheDict[ $reflectNamespace ][ $reflectKey ] = $reflectResult;

        $this->isChanged = true;

        return $this;
    }


    /**
     * @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection
     */
    protected function serializeData(array $data) : ?string
    {
        $before = error_reporting(0);

        $result = @serialize($data);

        if (false === $result) {
            throw new RuntimeException(
                'Unable to serialize data: ' . Lib::php_dump($data)
            );
        }

        error_reporting($before);

        return $result;
    }

    protected function unserializeData(string $data) : ?array
    {
        $result = null;

        $unserialized = @unserialize($data);

        if (! (
            (false === $unserialized)
            || (get_class($unserialized) === '__PHP_Incomplete_Class')
        )) {
            $result = $unserialized;
        }

        return $result;
    }


    protected function cacheFilename(string $string) : string
    {
        $hash = hash('sha256', $string, true);

        $parts = [];
        $parts[ 0 ] = substr($hash, 0, 8);
        $parts[ 1 ] = substr($hash, 8, 8);
        $parts[ 2 ] = substr($hash, 16);

        $parts[ 0 ] = strtoupper(substr(base64_encode($parts[ 0 ]), 0, 1));
        $parts[ 1 ] = strtoupper(substr(base64_encode($parts[ 1 ]), 0, 1));

        $val = base64_encode($parts[ 2 ]);
        $val = strtr($val, '+/', '-_');
        $val = rtrim($val, '=');
        $parts[ 2 ] = $val;

        $result = implode('/', $parts) . ".cache";

        return $result;
    }


    /**
     * @param string $key
     *
     * @return object|\Psr\Cache\CacheItemInterface
     */
    protected function cacheAdapterGetItem(string $key) : object
    {
        try {
            $cacheItem = $this->cacheAdapter->getItem($key);
        }
        catch ( \Psr\Cache\InvalidArgumentException $e ) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $cacheItem;
    }
}
