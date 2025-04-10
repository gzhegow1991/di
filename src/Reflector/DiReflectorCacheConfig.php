<?php

/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di\Reflector;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Config\AbstractConfig;
use Gzhegow\Lib\Exception\LogicException;


/**
 * @property string|null                                   $cacheMode
 * @property object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
 * @property string|null                                   $cacheDirpath
 */
class DiReflectorCacheConfig extends AbstractConfig
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


    protected function validation(array &$context = []) : bool
    {
        if (! isset(DiReflectorCache::LIST_CACHE_MODE[ $this->cacheMode ])) {
            throw new LogicException(
                [
                    ''
                    . 'The `cacheMode` should be one of: '
                    . implode('|', array_keys(DiReflectorCache::LIST_CACHE_MODE)),
                    //
                    $this,
                ]
            );
        }

        if (null !== $this->cacheAdapter) {
            if (! is_a($this->cacheAdapter, $class = '\Psr\Cache\CacheItemPoolInterface')) {
                throw new LogicException(
                    [
                        'The `cacheAdapter` should be instance of: ' . $class,
                        $this,
                    ]
                );
            }
        }

        if (null !== $this->cacheDirpath) {
            if (! Lib::type()->dirpath($dirpath, $this->cacheDirpath, true)) {
                throw new LogicException(
                    [
                        'The `cacheDirpath` should be valid directory path',
                        $this,
                    ]
                );
            }
        }

        return true;
    }
}
