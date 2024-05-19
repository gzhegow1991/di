<?php

namespace Gzhegow\Di;


function _filter_int($value) : ?int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_string($value)) {
        if (! is_numeric($value)) {
            return null;
        }
    }

    $valueOriginal = $value;

    if (! is_scalar($valueOriginal)) {
        if (null === ($_valueOriginal = _filter_str($valueOriginal))) {
            return null;
        }

        if (! is_numeric($_valueOriginal)) {
            return null;
        }

        $valueOriginal = $_valueOriginal;
    }

    $_value = $valueOriginal;
    $status = @settype($_value, 'integer');

    if ($status) {
        if ((float) $valueOriginal !== (float) $_value) {
            return null;
        }

        return $_value;
    }

    return null;
}

function _filter_positive_int($value) : ?int
{
    if (null === ($_value = _filter_int($value))) {
        return null;
    }

    if ($_value <= 0) {
        return null;
    }

    return $_value;
}


function _filter_str($value) : ?string
{
    if (is_string($value)) {
        return $value;
    }

    if (
        is_null($value)
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


function _filter_trim($value) : ?string
{
    if (null === ($_value = _filter_str($value))) {
        return null;
    }

    $_value = trim($_value);

    if ('' === $_value) {
        return null;
    }

    return $_value;
}


function _filter_string($value) : ?string
{
    if (null === ($_value = _filter_str($value))) {
        return null;
    }

    if ('' === $_value) {
        return null;
    }

    return $_value;
}

function _filter_word($value) : ?string
{
    if (null === ($_value = _filter_trim($value))) {
        return null;
    }

    if (false !== strpos($_value, ' ')) {
        return null;
    }

    if (false === preg_match('/[^\p{L}\d_]/u', $_value, $m)) {
        return null;
    }

    if ($m) {
        return null;
    }

    return $_value;
}


function _filter_strlen($value, array $optional = [], array $refs = []) : ?string
{
    $refs[ 0 ] = null; // &$max
    $refs[ 1 ] = null; // &$min

    $max_ =& $refs[ 0 ]; // &$max
    $min_ =& $refs[ 1 ]; // &$min

    $optional[ 0 ] = $optional[ 'max' ] ?? $optional[ 0 ] ?? null;
    $optional[ 1 ] = $optional[ 'min' ] ?? $optional[ 1 ] ?? 1;

    if (null === ($_value = _filter_str($value))) {
        return null;
    }

    $max_ = _filter_positive_int($optional[ 0 ]);
    $min_ = _filter_positive_int($optional[ 1 ]);

    $isMax = isset($max_);
    $isMin = isset($min_);

    if ($isMax || $isMin) {
        $len = strlen($_value);

        if ($isMax && $isMin) {
            if ($max_ < $min_) {
                throw _php_throw(
                    'The `max` should be greater than or equal to `min`: '
                    . _php_dump($optional[ 0 ])
                    . ' / ' . _php_dump($optional[ 1 ])
                );
            }
        }

        if ($isMax && ($len > $max_)) {
            return null;
        }

        if ($isMin && ($len < $min_)) {
            return null;
        }

    } else {
        if ('' === $_value) {
            return null;
        }
    }

    return $_value;
}
