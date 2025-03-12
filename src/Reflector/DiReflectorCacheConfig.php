<?php

/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Di\Reflector;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Config\AbstractConfig;


/**
 * @property string|null                                   $cacheMode
 *
 * @property object|\Psr\Cache\CacheItemPoolInterface|null $cacheAdapter
 *
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


    public function validateValue($value, string $key, array $path = [], array $context = []) : array
    {
        $errors = [];

        switch ( $key ):
            case 'cacheMode':
                if (! isset(DiReflectorCache::LIST_CACHE_MODE[ $value ])) {
                    $error = [
                        ''
                        . 'The `cacheMode` should be one of: '
                        . implode('|', array_keys(DiReflectorCache::LIST_CACHE_MODE)),
                        //
                        $this,
                    ];

                    $errors[] = [ $path, $error ];
                }

                break;

            case 'cacheAdapter':
                if (null !== $value) {
                    if (! is_a($value, $class = '\Psr\Cache\CacheItemPoolInterface')) {
                        $error = [
                            'The `cacheAdapter` should be instance of: ' . $class,
                            $this,
                        ];

                        $errors[] = [ $path, $error ];
                    }
                }

                break;

            case 'cacheDirpath':
                if (null !== $value) {
                    if (null === Lib::parse()->dirpath($value)) {
                        $error = [
                            'The `cacheDirpath` should be valid directory path',
                            $this,
                        ];

                        $errors[] = [ $path, $error ];
                    }
                }

                break;

        endswitch;

        return $errors;
    }
}
