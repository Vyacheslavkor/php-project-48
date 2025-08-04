<?php

namespace Formatters;

use Hexlet\Code\Differ\Diff;
use stdClass;

/**
 * @param \stdClass $diff
 *
 * @return string
 */
function plain(stdClass $diff): string
{
    $iter = static function ($currentDepthDiff, $path) use (&$iter) {
        $keys = getKeysFromDiff($currentDepthDiff);

        $lines = array_reduce($keys, static function ($acc, $key) use ($currentDepthDiff, $iter, $path) {
            if (is_object($currentDepthDiff->$key) && property_exists($currentDepthDiff->$key, 'status')) {
                if (
                    $currentDepthDiff->$key->status === Diff::ADDED
                    && property_exists($currentDepthDiff->$key, 'newValue')
                ) {
                    $value = getPlainValue($currentDepthDiff->$key->newValue);
                    $diff = ["Property '{$path}{$key}' was added with value: {$value}"];
                } elseif ($currentDepthDiff->$key->status === Diff::REMOVED) {
                    $diff = ["Property '{$path}{$key}' was removed"];
                } elseif (
                    $currentDepthDiff->$key->status === Diff::UPDATED
                    && property_exists($currentDepthDiff->$key, 'oldValue')
                    && property_exists($currentDepthDiff->$key, 'newValue')
                ) {
                    $oldValue = getPlainValue($currentDepthDiff->$key->oldValue);
                    $newValue = getPlainValue($currentDepthDiff->$key->newValue);
                    $diff = ["Property '{$path}{$key}' was updated. From {$oldValue} to {$newValue}"];
                } elseif (
                    $currentDepthDiff->$key->status === Diff::NESTED
                    && property_exists($currentDepthDiff->$key, 'children')
                ) {
                    $diff = [$iter($currentDepthDiff->$key->children, "{$path}{$key}.")];
                }
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
 * @param \stdClass $diff
 *
 * @return array<string>
 */
function getKeysFromDiff(stdClass $diff): array
{
    return array_keys((array) $diff);
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

    return is_object($value) ? '[complex value]' : toString($value);
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
