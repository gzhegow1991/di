<?php

namespace Gzhegow\Di\Exception;


use Gzhegow\Di\Lib;


class LogicException extends \LogicException implements
    ExceptionInterface
{
    public $message;
    public $code;
    public $file;
    public $line;
    public $previous;

    public $messageData;
    public $messageObject;

    public function __construct(...$errors)
    {
        foreach ( Lib::php_throwable_args()(...$errors) as $k => $v ) {
            $this->{$k} = $v;
        }

        parent::__construct($this->message, $this->code, $this->previous);
    }
}
