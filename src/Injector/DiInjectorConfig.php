<?php

namespace Gzhegow\Di\Injector;

use Gzhegow\Lib\Config\AbstractConfig;
use Gzhegow\Di\Exception\LogicException;


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


    protected function validation(array &$context = []) : bool
    {
        if (! isset(DiInjector::LIST_FETCH_FUNC[ $this->fetchFunc ])) {
            throw new LogicException(
                [
                    ''
                    . 'The `fetchFunc` should be one of: '
                    . implode('|', array_keys(DiInjector::LIST_FETCH_FUNC)),
                    //
                    $this,
                ]
            );
        }

        return true;
    }
}
