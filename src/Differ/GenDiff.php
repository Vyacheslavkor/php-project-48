<?php

namespace Differ\Differ;

use Docopt;
use Docopt\Response;
use Hexlet\Code\Differ\Diff;
use Hexlet\Code\Enum\OutputFormat;
use RuntimeException;
use stdClass;

use function Parsers\parseFileData;
use function Formatters\getFormattedDiff;
use function Functional\sort;

/**
 * @param array<string, mixed> $params
 *
 * @return \Docopt\Response
 */
function parseInput(array $params = []): Docopt\Response
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
function getFilePathsFromInput(Response $args): array
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
 * @throws RuntimeException
 */
function genDiff(string $firstFile, string $secondFile, string $format = OutputFormat::STYLISH): string
{
    [$firstFileData, $secondFileData] = parseFileData($firstFile, $secondFile);

    $diff = getDiff($firstFileData, $secondFileData);

    return getFormattedDiff($diff, $format);
}

/**
 * @param object $first
 * @param object $second
 *
 * @return stdClass
 */
function getDiff(object $first, object $second): stdClass
{
    $fields = getListKeys($first, $second);

    return array_reduce($fields, static function ($acc, $field) use ($first, $second) {
        $diffNode = new stdClass();

        if (!property_exists($first, $field)) {
            $diffNode->status = Diff::ADDED;
            $diffNode->newValue = $second->$field;
        } elseif (!property_exists($second, $field)) {
            $diffNode->status = Diff::REMOVED;
            $diffNode->oldValue = $first->$field;
        } elseif ($first->$field === $second->$field) {
            $diffNode->status = Diff::UNCHANGED;
            $diffNode->oldValue = $first->$field;
        } elseif (!is_object($first->$field) || !is_object($second->$field)) {
            $diffNode->status = Diff::UPDATED;
            $diffNode->oldValue = $first->$field;
            $diffNode->newValue = $second->$field;
        } else {
            $diffNode->status = Diff::NESTED;
            $diffNode->children = getDiff($first->$field, $second->$field);
        }

        return (object) array_merge(
            (array) $acc,
            [$field => $diffNode]
        );
    }, new stdClass());
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
function getFormat(Response $args): bool|string
{
    return $args->offsetGet('--format');
}
