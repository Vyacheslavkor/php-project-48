<?php

namespace Differ\Differ;

use Docopt;
use Docopt\Response;

use function Parsers\parseFileData;
use function Formatters\getFormattedDiff;
use function Functional\sort;

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
            $diff = ["+ $field" => objectToArray($second->$field)];
        } elseif (property_exists($first, $field) && !property_exists($second, $field)) {
            $diff = ["- $field" => objectToArray($first->$field)];
        } elseif ($first->$field === $second->$field) {
            $diff = [$field => objectToArray($second->$field)];
        } else {
            if (!is_object($first->$field) || !is_object($second->$field)) {
                $diff = [
                    "- $field" => objectToArray($first->$field),
                    "+ $field" => objectToArray($second->$field)
                ];
            }

            if (is_object($first->$field) && is_object($second->$field)) {
                $diff = [$field => getDiffResult($first->$field, $second->$field)];
            }
        }

        return array_merge($acc, $diff ?? []);
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

    return sort($allUniqueFields, fn ($left, $right) => strcmp($left, $right));
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
