<?php

/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di\Reflector;

use Gzhegow\Lib\Lib;
use Gzhegow\Di\Exception\RuntimeException;


class ReflectorCache implements ReflectorCacheInterface
{
    const CACHE_MODE_NO_CACHE = 'NO_CACHE';
    const CACHE_MODE_RUNTIME  = 'RUNTIME';
    const CACHE_MODE_STORAGE  = 'STORAGE';

    const LIST_CACHE_MODE = [
        self::CACHE_MODE_NO_CACHE => true,
        self::CACHE_MODE_RUNTIME  => true,
        self::CACHE_MODE_STORAGE  => true,
    ];


    /**
     * @var ReflectorCacheConfig
     */
    protected $config;

    /**
     * @var array<string, array<string, array>>
     */
    protected $reflectionResults = [];
    /**
     * @var bool
     */
    protected $isChanged = false;


    public function __construct(ReflectorCacheConfig $config)
    {
        $this->config = $config;
        $this->config->validate();
    }


    public function hasReflectionResult(string $reflectionKey, string $reflectionNamespace = null, array &$result = null) : bool
    {
        $result = null;

        $reflectionNamespace = $reflectionNamespace ?? '-';

        $isNoCache = $this->config->cacheMode === static::CACHE_MODE_NO_CACHE;
        $isRuntime = $this->config->cacheMode === static::CACHE_MODE_RUNTIME;
        $isStorage = $this->config->cacheMode === static::CACHE_MODE_STORAGE;

        if ($isNoCache) {
            return false;
        }

        if (! isset($this->reflectionResults[ $reflectionNamespace ][ $reflectionKey ])) {
            if ($isStorage) {
                $cacheKey = $reflectionNamespace;

                if (null !== $this->config->cacheAdapter) {
                    $cacheItem = $this->cacheAdapterGetItem($cacheKey);

                    if ($cacheItem->isHit()) {
                        $unserializedArray = $cacheItem->get();

                        $this->reflectionResults += $unserializedArray;
                    }

                } else {
                    $cacheFilename = $this->cacheFilename($cacheKey);
                    $cacheFilepath = "{$this->config->cacheDirpath}/{$cacheFilename}";

                    $content = null;
                    if (is_file($cacheFilepath)) {
                        $content = Lib::fs_file_get_contents($cacheFilepath);
                    }

                    if (null !== $content) {
                        $unserializedArray = Lib::php_unserialize($content);
                        $unserializedArray = $unserializedArray ?? [];

                        $this->reflectionResults += $unserializedArray;
                    }
                }
            }
        }

        $status = isset($this->reflectionResults[ $reflectionNamespace ][ $reflectionKey ]);

        if ($status) {
            $result = $this->reflectionResults[ $reflectionNamespace ][ $reflectionKey ];
        }

        return $status;
    }

    public function getReflectionResult(string $reflectionKey, string $reflectionNamespace = null, array $fallback = []) : array
    {
        $status = $this->hasReflectionResult($reflectionKey, $reflectionNamespace, $result);

        if (! $status) {
            if ($fallback) {
                [ $fallback ] = $fallback;

                return $fallback;
            }

            throw new RuntimeException(
                'Missing cache key: ' . $reflectionKey
            );
        }

        return $result;
    }

    /**
     * @return static
     */
    public function setReflectionResult(array $reflectionResult, string $reflectionKey, string $reflectionNamespace = null) // : static
    {
        $reflectionNamespace = $reflectionNamespace ?? '-';

        $current = $this->reflectionResults[ $reflectionNamespace ][ $reflectionKey ] ?? null;

        if (null !== $current) {
            throw new RuntimeException(
                'Cache key already exists: ' . $reflectionKey
            );
        }

        $this->reflectionResults[ $reflectionNamespace ][ $reflectionKey ] = $reflectionResult;

        $this->isChanged = true;

        return $this;
    }


    /**
     * @return static
     */
    public function resetCache() // : static
    {
        $this->reflectionResults = [];

        $this->isChanged = false;

        return $this;
    }

    /**
     * @return static
     */
    public function saveCache() // : static
    {
        if (! $this->isChanged) {
            return $this;
        }

        if ($this->config->cacheMode !== static::CACHE_MODE_STORAGE) {
            return $this;
        }

        if (null !== $this->config->cacheAdapter) {
            foreach ( $this->reflectionResults as $reflectNamespace => $cacheData ) {
                $cacheItem = $this->cacheAdapterGetItem($reflectNamespace);
                $cacheItem->set($cacheData);

                $this->config->cacheAdapter->saveDeferred($cacheItem);
            }

            $this->config->cacheAdapter->commit();

        } else {
            foreach ( $this->reflectionResults as $reflectNamespace => $cacheData ) {
                $cacheKey = $reflectNamespace;

                $cacheFilename = $this->cacheFilename($cacheKey);
                $cacheFilepath = "{$this->config->cacheDirpath}/{$cacheFilename}";

                $content = Lib::php_serialize($cacheData);

                Lib::fs_file_put_contents($cacheFilepath, $content, [ 0755, true ]);
            }
        }

        return $this;
    }

    /**
     * @return static
     */
    public function clearCache() // : static
    {
        $this->resetCache();

        if ($this->config->cacheMode !== static::CACHE_MODE_STORAGE) {
            return $this;
        }

        if (null !== $this->config->cacheAdapter) {
            $this->config->cacheAdapter->clear();

        } else {
            foreach ( Lib::fs_dir_walk($this->config->cacheDirpath) as $spl ) {
                $spl->isDir()
                    ? rmdir($spl->getRealPath())
                    : unlink($spl->getRealPath());
            }
        }

        return $this;
    }


    /**
     * @param string $key
     *
     * @return object|\Psr\Cache\CacheItemInterface
     */
    protected function cacheAdapterGetItem(string $key) : object
    {
        try {
            $cacheItem = $this->config->cacheAdapter->getItem($key);
        }
        catch ( \Psr\Cache\InvalidArgumentException $e ) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $cacheItem;
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
}
