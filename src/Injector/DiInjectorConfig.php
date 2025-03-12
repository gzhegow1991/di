<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Lib\Config\AbstractConfig;


/**
 * @property string $fetchFunc
 */
class DiInjectorConfig extends AbstractConfig
{
    /**
     * > использовать get()/take() в качестве основной функции во время рекрурсивного разбора
     *
     * @see DiInjector::LIST_FETCH_FUNC
     *
     * @var string
     */
    protected $fetchFunc = DiInjector::FETCH_FUNC_GET;


    protected function validateValue($value, string $key, array $path = [], array $context = []) : array
    {
        $errors = [];

        if ($key === 'fetchFunc') {
            if (! isset(DiInjector::LIST_FETCH_FUNC[ $value ])) {
                $error = [
                    ''
                    . 'The `fetchFunc` should be one of: '
                    . implode('|', array_keys(DiInjector::LIST_FETCH_FUNC)),
                    //
                    $this,
                ];

                $errors[] = [ $path, $error ];
            }
        }

        return $errors;
    }
}
