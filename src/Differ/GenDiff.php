<?php

namespace Differ;

use Docopt;
use Docopt\Response;

function getArgs(): Docopt\Response
{
    return Docopt::handle(getDoc());
}

function getDoc(): string
{
    return <<<DOC
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
    $firstFileData = getAsArray($firstFile);
    $secondFileData = getAsArray($secondFile);

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
        $getBoolAsString = static function ($value) {
            if ($value === true) {
                return 'true';
            }

            if ($value === false) {
                return 'false';
            }

            return $value;
        };

        $firstValue = isset($firstFileData[$field])
            ? $getBoolAsString($firstFileData[$field])
            : null;
        $secondValue = isset($secondFileData[$field])
            ? $getBoolAsString($secondFileData[$field])
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
 * @param string $filePath
 *
 * @return array<string, mixed>
 */
function getAsArray(string $filePath): array
{
    $json = file_get_contents($filePath);

    if (!$json) {
        return [];
    }

    return json_decode($json, true);
}
