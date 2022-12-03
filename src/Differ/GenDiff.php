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

function genDiff(string $firstFile, string $secondFile): array
{
    [$firstFileData, $secondFileData] = parseFileData($firstFile, $secondFile);

    return getDiffResult($firstFileData, $secondFileData);
}

function getDiffResult($first, $second): array
{
    if (!is_array($first) || !is_array($second)) {
        return [];
    }

    $fields = getListKeys($first, $second);

    $result = array_reduce($fields, static function ($acc, $field) use ($first, $second) {
        if (!array_key_exists($field, $first) && array_key_exists($field, $second)) {
            $acc["+ $field"] = $second[$field];
        } elseif (array_key_exists($field, $first) && !array_key_exists($field, $second)) {
            $acc["- $field"] = $first[$field];
        } elseif ($first[$field] === $second[$field]) {
            $acc[$field] = $second[$field];
        } else {
            if (!is_array($first[$field]) || !is_array($second[$field])) {
                $acc["- $field"] = $first[$field];
                $acc["+ $field"] = $second[$field];
            }

            if (is_array($first[$field]) && is_array($second[$field])) {
                $acc[$field] = getDiffResult($first[$field], $second[$field]);
            }
        }

        return $acc;
    }, []);

    return $result;
}

/**
 * @param array<string, mixed> $firstFileData
 * @param array<string, mixed> $secondFileData
 *
 * @return array<int, string>
 */
function getListKeys(array $firstFileData, array $secondFileData): array
{
    $firstKeys = array_keys($firstFileData);
    $secondKeys = array_keys($secondFileData);
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

function stylish($diff): string
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
        $getSpace = static fn ($val) => $val === '' ? '' : ' ';

        $lines = array_map(
            static fn($key, $val) => "{$getIndent($key)}{$key}:{$getSpace($val)}{$iter($val, $depth + 1)}",
            array_keys($value),
            $value
        );

        $result = ['{' , ...$lines, "{$bracketIndent}}"];

        return implode("\n", $result);
    };

    $res = $iter($diff, 1);

    return $res;
}

function isAssoc(array $array): bool
{
    if (empty($array)) {
        return false;
    }

    return array_keys($array) !== range(0, count($array) - 1);
}

function getFormattedDiff($diff, $format = 'stylish')
{
    if (!function_exists("\\Differ\\{$format}") || $format !== 'stylish') {
        throw new Exception(sprintf('Unknown format: %s', $format));
    }

    return call_user_func("\\Differ\\{$format}", $diff);
}
