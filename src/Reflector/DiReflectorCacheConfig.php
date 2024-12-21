<?php

/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di\Reflector;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Config\Config;


/**
 * @property string|null                                   $cacheMode
 *
 * @property object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
 *
 * @property string|null                                   $cacheDirpath
 */
class DiReflectorCacheConfig extends Config
{
    /**
     * > тип кеширования - кешировать или не использовать кэш
     *
     * @see DiReflectorCache::LIST_CACHE_MODE
     *
     * @var string|null
     */
    protected $cacheMode = DiReflectorCache::CACHE_MODE_NO_CACHE;

    /**
     * > можно установить пакет `composer require symfony/cache` и использовать адаптер, чтобы хранить кэш в redis или любым другим способом
     *
     * @noinspection PhpFullyQualifiedNameUsageInspection
     * @noinspection PhpUndefinedClassInspection
     * @noinspection PhpUndefinedNamespaceInspection
     *
     * @var object|\Psr\Cache\CacheItemPoolInterface|null
     */
    protected $cacheAdapter;

    /**
     * > для кэша можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
     *
     * @var string|null
     */
    protected $cacheDirpath = __DIR__ . '/../var/cache/gzhegow.di';


    public function validate() : void
    {
        if (! isset(DiReflectorCache::LIST_CACHE_MODE[ $this->cacheMode ])) {
            throw new LogicException(
                [
                    'The `cacheMode` should be one of: '
                    . implode('|', array_keys(DiReflectorCache::LIST_CACHE_MODE)),
                    $this,
                ]
            );
        }

        if ((null !== $this->cacheAdapter)
            && ! is_a($this->cacheAdapter, $class = '\Psr\Cache\CacheItemPoolInterface')
        ) {
            throw new LogicException(
                [
                    'The `cacheAdapter` should be instance of: ' . $class,
                    $this,
                ]
            );
        }

        if ((null !== $this->cacheDirpath)
            && (null === Lib::parse_dirpath($this->cacheDirpath))
        ) {
            throw new LogicException(
                [
                    'The `cacheDirpath` should be valid directory path',
                    $this,
                ]
            );
        }
    }
}
