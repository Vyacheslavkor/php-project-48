<?php

namespace Formatters;

/**
 * @param array<string, mixed> $diff
 *
 * @return string
 */
function plain(array $diff): string
{
    $iter = static function ($currentDepthDiff, $path) use (&$iter) {
        $keys = getKeysFromDiff($currentDepthDiff);

        $lines = array_reduce($keys, static function ($acc, $key) use ($currentDepthDiff, $iter, $path) {
            if (keyExists("+ {$key}", $currentDepthDiff) && !keyExists("- {$key}", $currentDepthDiff)) {
                $value = getPlainValue($currentDepthDiff["+ {$key}"]);
                $diff = ["Property '{$path}{$key}' was added with value: {$value}"];
            } elseif (keyExists("- {$key}", $currentDepthDiff) && !keyExists("+ {$key}", $currentDepthDiff)) {
                $diff = ["Property '{$path}{$key}' was removed"];
            } elseif (keyExists("- {$key}", $currentDepthDiff) && keyExists("+ {$key}", $currentDepthDiff)) {
                $oldValue = getPlainValue($currentDepthDiff["- {$key}"]);
                $newValue = getPlainValue($currentDepthDiff["+ {$key}"]);
                $diff = ["Property '{$path}{$key}' was updated. From {$oldValue} to {$newValue}"];
            } elseif (array_key_exists($key, $currentDepthDiff) && is_array($currentDepthDiff[$key])) {
                $diff = [$iter($currentDepthDiff[$key], "{$path}{$key}.")];
            }

            return array_merge($acc, $diff ?? []);
        }, []);

        return implode("\n", $lines);
    };

    return $iter($diff, '');
}

function getFieldName(string $field): string
{
    if (strpos($field, '+') === 0 || strpos($field, '-') === 0) {
        [, $fieldName] = explode(' ', $field);
        return $fieldName;
    }

    return $field;
}

/**
 * @param array<string, mixed> $diff
 *
 * @return array<string>
 */
function getKeysFromDiff(array $diff): array
{
    return array_unique(array_reduce(array_keys($diff), static function ($acc, $value) {
        $fieldName = getFieldName($value);
        return array_merge($acc, [$fieldName]);
    }, []));
}

/**
 * @param mixed $value
 *
 * @return string
 */
function getPlainValue($value): string
{
    if (is_string($value)) {
        return "'{$value}'";
    }

    return is_array($value) ? '[complex value]' : toString($value);
}

/**
 * @param string               $foundKey
 * @param array<string, mixed> $array
 *
 * @return bool
 */
function keyExists(string $foundKey, array $array): bool
{
    return array_key_exists($foundKey, $array);
}
