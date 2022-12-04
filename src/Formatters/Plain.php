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
            if (array_key_exists("+ {$key}", $currentDepthDiff) && !array_key_exists("- {$key}", $currentDepthDiff)) {
                $value = getPlainValue($currentDepthDiff["+ {$key}"]);
                $acc[] = "Property '{$path}{$key}' was added with value: {$value}";
            }

            if (array_key_exists("- {$key}", $currentDepthDiff) && !array_key_exists("+ {$key}", $currentDepthDiff)) {
                $acc[] = "Property '{$path}{$key}' was removed";
            }

            if (array_key_exists("- {$key}", $currentDepthDiff) && array_key_exists("+ {$key}", $currentDepthDiff)) {
                $oldValue = getPlainValue($currentDepthDiff["- {$key}"]);
                $newValue = getPlainValue($currentDepthDiff["+ {$key}"]);
                $acc[] = "Property '{$path}{$key}' was updated. From {$oldValue} to {$newValue}";
            }

            if (array_key_exists($key, $currentDepthDiff) && is_array($currentDepthDiff[$key])) {
                $acc[] = $iter($currentDepthDiff[$key], "{$path}{$key}.");
            }

            return $acc;
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
        $acc[] = getFieldName($value);
        return $acc;
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
