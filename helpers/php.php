<?php

namespace Gzhegow\Di;


/**
 * > gzhegow, выводит короткую и наглядную форму содержимого переменной в виде строки
 */
function _php_dump($value, int $maxlen = null) : string
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
                ?? _php_dump($v, $maxlen);
        }

        $_value = var_export($value, true);

        $_value = str_replace("\n", ' ', $_value);
        $_value = preg_replace('/\s+/', ' ', $_value);
    }

    if (null === $_value) {
        throw _php_throw(
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
function _php_throw($error = null, ...$errors) : ?object
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

    $throwErrors = _php_throw_errors($error, ...$errors);

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
function _php_throw_errors($error = null, ...$errors) : array
{
    $_message = null;
    $_code = null;
    $_previous = null;
    $_messageData = null;
    $_messageObject = null;

    array_unshift($errors, $error);

    foreach ( $errors as $error ) {
        if (is_int($error)) {
            $_code = $error;

            continue;
        }

        if (is_a($error, \Throwable::class)) {
            $_previous = $error;

            continue;
        }

        if (null !== ($_string = _filter_string($error))) {
            $_message = $_string;

            continue;
        }

        if (
            is_array($error)
            || is_a($error, \stdClass::class)
        ) {
            $_messageData = (array) $error;

            if (isset($_messageData[ 0 ])) {
                $_message = _filter_string($_messageData[ 0 ]);
            }
        }
    }

    $_message = $_message ?? null;
    $_code = $_code ?? null;
    $_previous = $_previous ?? null;

    $_messageObject = null
        ?? (isset($_messageData) ? (object) $_messageData : null)
        ?? (isset($_message) ? (object) [ $_message ] : null);

    if (isset($_messageData)) {
        array_shift($_messageData);

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
function _php_method_exists(
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
