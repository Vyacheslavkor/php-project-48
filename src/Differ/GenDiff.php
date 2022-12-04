<?php

namespace Differ;

use Docopt;
use Docopt\Response;
use Exception;

use function Parsers\parseFileData;

/**
 * @param array<string, mixed> $params
 *
 * @return \Docopt\Response
 */
function getArgs(array $params = []): Docopt\Response
{
    return Docopt::handle(getDoc(), $params);
}

function getDoc(): string
{
    $doc = <<<DOC
Generate diff

Usage:
    gendiff (-h|--help)
    gendiff (-v|--version)
    gendiff [--format <fmt>] <firstFile> <secondFile>

Options:
    -h --help                     Show this screen
    -v --version                  Show version
    --format <fmt>                Report format [default: stylish]
DOC;

    return trim($doc);
}

/**
 * @param \Docopt\Response $args
 *
 * @return array<int, string>
 */
function getFilePathsFromArgs(Response $args): array
{
    $firstFile = $args->offsetGet('<firstFile>');
    $secondFile = $args->offsetGet('<secondFile>');

    return [$firstFile, $secondFile];
}

/**
 * @param string $firstFile
 * @param string $secondFile
 * @param string $format
 *
 * @return string
 * @throws \Exception
 */
function genDiff(string $firstFile, string $secondFile, string $format = 'stylish'): string
{
    [$firstFileData, $secondFileData] = parseFileData($firstFile, $secondFile);

    $diff = getDiffResult($firstFileData, $secondFileData);

    return getFormattedDiff($diff, $format);
}

/**
 * @param object $first
 * @param object $second
 *
 * @return array<string, mixed>
 */
function getDiffResult(object $first, object $second): array
{
    $fields = getListKeys($first, $second);

    return array_reduce($fields, static function ($acc, $field) use ($first, $second) {
        if (!property_exists($first, $field) && property_exists($second, $field)) {
            $acc["+ $field"] = objectToArray($second->$field);
        } elseif (property_exists($first, $field) && !property_exists($second, $field)) {
            $acc["- $field"] = objectToArray($first->$field);
        } elseif ($first->$field === $second->$field) {
            $acc[$field] = objectToArray($second->$field);
        } else {
            if (!is_object($first->$field) || !is_object($second->$field)) {
                $acc["- $field"] = objectToArray($first->$field);
                $acc["+ $field"] = objectToArray($second->$field);
            }

            if (is_object($first->$field) && is_object($second->$field)) {
                $acc[$field] = getDiffResult($first->$field, $second->$field);
            }
        }

        return $acc;
    }, []);
}

/**
 * @param object $firstFileData
 * @param object $secondFileData
 *
 * @return array<int, string>
 */
function getListKeys(object $firstFileData, object $secondFileData): array
{
    $firstKeys = array_keys((array) $firstFileData);
    $secondKeys = array_keys((array) $secondFileData);

    $allUniqueFields = array_unique(array_merge($firstKeys, $secondKeys));

    sort($allUniqueFields);

    return $allUniqueFields;
}

/**
 * @param mixed $value
 *
 * @return string
 */
function toString($value): string
{
    if ($value === null) {
        return 'null';
    }

    return trim(var_export($value, true), "'");
}

/**
 * @param array<string, mixed> $diff
 *
 * @return string
 */
function stylish(array $diff): string
{
    $replacer = ' ';
    $spacesCount = 2;

    $iter = static function ($value, $depth) use (&$iter, $replacer, $spacesCount) {
        if (!is_array($value)) {
            return toString($value);
        }

        $indentSize = $spacesCount * $depth + $spacesCount * ($depth - 1);
        $getIndent = static fn($val) => strpos($val, '+') === 0 || strpos($val, '-') === 0
            ? str_repeat($replacer, $indentSize)
            : str_repeat($replacer, $indentSize + $spacesCount);
        $bracketIndent = str_repeat($replacer, $indentSize - $spacesCount);
        $getSpace = static fn($val) => $val === ''
            ? ''
            : ' ';

        $lines = array_map(
            static fn($key, $val) => "{$getIndent($key)}{$key}:{$getSpace($val)}{$iter($val, $depth + 1)}",
            array_keys($value),
            $value
        );

        $result = ['{', ...$lines, "{$bracketIndent}}"];

        return implode("\n", $result);
    };

    return $iter($diff, 1);
}

/**
 * @param array<string, mixed> $diff
 * @param string               $format
 *
 * @return string
 * @throws \Exception
 */
function getFormattedDiff(array $diff, string $format = 'stylish'): string
{
    $fn = "\\Differ\\{$format}";
    if (!is_callable($fn) || !in_array($format, ['stylish', 'plain'])) {
        throw new Exception(sprintf('Unknown format: %s', $format));
    }

    return $fn($diff);
}

/**
 * @param string|object $object
 *
 * @return mixed
 */
function objectToArray($object)
{
    return is_object($object)
        ? json_decode((string) json_encode($object), true)
        : $object;
}

/**
 * @param \Docopt\Response $args
 *
 * @return string|bool
 */
function getFormat(Response $args)
{
    return $args->offsetGet('--format');
}

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
