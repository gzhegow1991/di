<?php

namespace Gzhegow\Di;

class Lib
{
    /**
     * > gzhegow, выводит короткую и наглядную форму содержимого переменной в виде строки
     */
    public static function php_dump($value, int $maxlen = null) : string
    {
        if (is_string($value)) {
            $_value = ''
                . '{ '
                . 'string(' . strlen($value) . ')'
                . ' "'
                . ($maxlen
                    ? (substr($value, 0, $maxlen) . '...')
                    : $value
                )
                . '"'
                . ' }';

        } elseif (! is_iterable($value)) {
            $_value = null
                ?? (($value === null) ? '{ NULL }' : null)
                ?? (($value === false) ? '{ FALSE }' : null)
                ?? (($value === true) ? '{ TRUE }' : null)
                ?? (is_object($value) ? ('{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }') : null)
                ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
                //
                ?? (is_int($value) ? (var_export($value, 1)) : null) // INF
                ?? (is_float($value) ? (var_export($value, 1)) : null) // NAN
                //
                ?? null;

        } else {
            foreach ( $value as $k => $v ) {
                $value[ $k ] = null
                    ?? (is_array($v) ? '{ array(' . count($v) . ') }' : null)
                    ?? (is_iterable($v) ? '{ iterable(' . get_class($value) . ' # ' . spl_object_id($value) . ') }' : null)
                    // ! recursion
                    ?? static::php_dump($v, $maxlen);
            }

            $_value = var_export($value, true);

            $_value = str_replace("\n", ' ', $_value);
            $_value = preg_replace('/\s+/', ' ', $_value);
        }

        if (null === $_value) {
            throw static::php_throw(
                'Unable to dump variable'
            );
        }

        return $_value;
    }

    /**
     * > gzhegow, перебрасывает исключение на "тихое", если из библиотеки внутреннее постоянно подсвечивается в PHPStorm
     *
     * @return \LogicException|null
     */
    public static function php_throw($error = null, ...$errors) : ?object
    {
        if (is_a($error, \Closure::class)) {
            $error = $error(...$errors);
        }

        if (
            is_a($error, \LogicException::class)
            || is_a($error, \RuntimeException::class)
        ) {
            return $error;
        }

        $throwErrors = static::php_throw_errors($error, ...$errors);

        $message = $throwErrors[ 'message' ] ?? __FUNCTION__;
        $code = $throwErrors[ 'code' ] ?? -1;
        $previous = $throwErrors[ 'previous' ] ?? null;

        return $previous
            ? new \RuntimeException($message, $code, $previous)
            : new \LogicException($message, $code);
    }

    /**
     * > gzhegow, парсит ошибки для передачи результата в конструктор исключения
     *
     * @return array{
     *     message: string,
     *     code: int,
     *     previous: string,
     *     messageData: array,
     *     messageObject: object,
     * }
     */
    public static function php_throw_errors($error = null, ...$errors) : array
    {
        $_message = null;
        $_code = null;
        $_previous = null;
        $_messageData = null;
        $_messageObject = null;

        array_unshift($errors, $error);

        foreach ( $errors as $err ) {
            if (is_int($err)) {
                $_code = $err;

                continue;
            }

            if (is_a($err, \Throwable::class)) {
                $_previous = $err;

                continue;
            }

            if (null !== ($_string = static::filter_string($err))) {
                $_message = $_string;

                continue;
            }

            if (
                is_array($err)
                || is_a($err, \stdClass::class)
            ) {
                $_messageData = (array) $err;

                if (isset($_messageData[ 0 ])) {
                    $_message = static::filter_string($_messageData[ 0 ]);
                }
            }
        }

        $_message = $_message ?? null;
        $_code = $_code ?? null;
        $_previous = $_previous ?? null;

        $_messageObject = null
            ?? ((null !== $_messageData) ? (object) $_messageData : null)
            ?? ((null !== $_message) ? (object) [ $_message ] : null);

        if (null !== $_messageData) {
            unset($_messageData[ 0 ]);

            $_messageData = $_messageData ?: null;
        }

        $result = [];
        $result[ 'message' ] = $_message;
        $result[ 'code' ] = $_code;
        $result[ 'previous' ] = $_previous;
        $result[ 'messageData' ] = $_messageData;
        $result[ 'messageObject' ] = $_messageObject;

        return $result;
    }


    /**
     * @param callable|array|object|class-string     $mixed
     *
     * @param array{0: class-string, 1: string}|null $resultArray
     * @param callable|string|null                   $resultString
     *
     * @return array{0: class-string|object, 1: string}|null
     */
    public static function php_method_exists(
        $mixed, $method = null,
        array &$resultArray = null, string &$resultString = null
    ) : ?array
    {
        $resultArray = null;
        $resultString = null;

        $method = $method ?? '';

        $_class = null;
        $_object = null;
        $_method = null;
        if (is_object($mixed)) {
            $_object = $mixed;

        } elseif (is_array($mixed)) {
            $list = array_values($mixed);

            /** @noinspection PhpWrongStringConcatenationInspection */
            [ $classOrObject, $_method ] = $list + [ '', '' ];

            is_object($classOrObject)
                ? ($_object = $classOrObject)
                : ($_class = $classOrObject);

        } elseif (is_string($mixed)) {
            [ $_class, $_method ] = explode('::', $mixed) + [ '', '' ];

            $_method = $_method ?? $method;
        }

        if (isset($_method) && ! is_string($_method)) {
            return null;
        }

        if ($_object) {
            if ($_object instanceof \Closure) {
                return null;
            }

            if (method_exists($_object, $_method)) {
                $class = get_class($_object);

                $resultArray = [ $class, $_method ];
                $resultString = $class . '::' . $_method;

                return [ $_object, $_method ];
            }

        } elseif ($_class) {
            if (method_exists($_class, $_method)) {
                $resultArray = [ $_class, $_method ];
                $resultString = $_class . '::' . $_method;

                return [ $_class, $_method ];
            }
        }

        return null;
    }


    /**
     * > gzhegow, вызывает произвольный колбэк с аргументами, если колбэк вернул не TRUE, бросает исключение, иначе $args[0]
     * > _assert_true('is_int', [ $input ], 'Переменная `input` должна быть числом')
     *
     * @param callable|bool $fn
     */
    public static function assert_true($fn, array $args = [], $error = '', ...$errors) // : mixed
    {
        $bool = is_bool($fn)
            ? $fn
            : (bool) call_user_func_array($fn, $args);

        if (true !== $bool) {
            if (('' === $error) || (null === $error)) {
                if (! $errors) {
                    $error = [ '[ ' . __FUNCTION__ . ' ] ' . static::php_dump($fn), $args ];
                }
            }

            throw static::php_throw($error, ...$errors);
        }

        return $args[ 0 ] ?? null;
    }


    public static function filter_str($value) : ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (
            (null === $value)
            || is_array($value)
            || is_resource($value)
        ) {
            return null;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $_value = (string) $value;

                return $_value;
            }

            return null;
        }

        $_value = $value;
        $status = @settype($_value, 'string');

        if ($status) {
            return $_value;
        }

        return null;
    }

    public static function filter_string($value) : ?string
    {
        if (null === ($_value = static::filter_str($value))) {
            return null;
        }

        if ('' === $_value) {
            return null;
        }

        return $_value;
    }


    public static function filter_path(
        $value, array $optional = [],
        array &$pathinfo = null
    ) : ?string
    {
        $pathinfo = null;

        $optional[ 0 ] = $optional[ 'with_pathinfo' ] ?? $optional[ 0 ] ?? false;

        if (null === ($_value = Lib::filter_string($value))) {
            return null;
        }

        if (false !== strpos($_value, "\0")) {
            return null;
        }

        $withPathInfoResult = (bool) $optional[ 0 ];

        if ($withPathInfoResult) {
            try {
                $pathinfo = pathinfo($_value);
            }
            catch ( \Throwable $e ) {
                return null;
            }
        }

        return $_value;
    }

    public static function filter_dirpath(
        $value, array $optional = [],
        array &$pathinfo = null
    ) : ?string
    {
        $_value = static::filter_path(
            $value, $optional,
            $pathinfo
        );

        if (null === $_value) {
            return null;
        }

        if (file_exists($_value) && ! is_dir($_value)) {
            return null;
        }

        return $_value;
    }


    public static function filter_filename($value) : ?string
    {
        if (null === ($_value = Lib::filter_string($value))) {
            return null;
        }

        $forbidden = [ "\0", "/", "\\", DIRECTORY_SEPARATOR ];

        foreach ( $forbidden as $f ) {
            if (false !== strpos($_value, $f)) {
                return null;
            }
        }

        return $_value;
    }
}
