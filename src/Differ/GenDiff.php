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

function genDiff(string $firstFile, string $secondFile): string
{
    [$firstFileData, $secondFileData] = parseFileData($firstFile, $secondFile);

    $fields = getListKeys($firstFileData, $secondFileData);

    return getDiffResult($firstFileData, $secondFileData, $fields);
}

/**
 * @param array<string, string> $firstFileData
 * @param array<string, string> $secondFileData
 * @param array<int, string>    $fields
 *
 * @return string
 */
function getDiffResult(array $firstFileData, array $secondFileData, array $fields): string
{
    $result = array_reduce($fields, static function ($acc, $field) use ($firstFileData, $secondFileData) {
        $firstValue = isset($firstFileData[$field])
            ? convertBoolToString($firstFileData[$field])
            : null;
        $secondValue = isset($secondFileData[$field])
            ? convertBoolToString($secondFileData[$field])
            : null;

        if (array_key_exists($field, $firstFileData) && array_key_exists($field, $secondFileData)) {
            if ($firstFileData[$field] === $secondFileData[$field]) {
                $acc = "$acc    $field: $firstValue\n";
            } else {
                $acc = "$acc  - $field: $firstValue\n";
                $acc = "$acc  + $field: $secondValue\n";
            }
        } elseif (array_key_exists($field, $firstFileData)) {
            $acc = "$acc  - $field: $firstValue\n";
        } else {
            $acc = "$acc  + $field: $secondValue\n";
        }

        return $acc;
    }, '');

    return "{\n$result}\n";
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
function convertBoolToString($value): string
{
    if ($value === true) {
        return 'true';
    }

    if ($value === false) {
        return 'false';
    }

    return $value;
}
