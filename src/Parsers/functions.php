<?php

namespace Parsers;

use Exception;
use Symfony\Component\Yaml\Yaml;

const JSON = 'json';
const YAML = 'yaml';

/**
 * @param string $json
 *
 * @return object
 */
function parseJson(string $json): object
{
    return json_decode($json);
}

/**
 * @param string $firstFile
 * @param string $secondFile
 *
 * @return array<int, mixed>
 * @throws \Exception
 */
function parseFileData(string $firstFile, string $secondFile): array
{
    if (!file_exists($firstFile) || !file_exists($secondFile)) {
        throw new Exception(sprintf('File %s or %s not exists.', $firstFile, $secondFile));
    }

    [$firstParsedData, $secondParsedData] = getParsedFilesData($firstFile, $secondFile);

    return [$firstParsedData, $secondParsedData];
}

/**
 * @param string $firstFileFormat
 * @param string $secondFileFormat
 *
 * @return bool
 */
function checkFileFormats(string $firstFileFormat, string $secondFileFormat): bool
{
    $supportedFormats = getSupportedFileFormats();

    if (
        !in_array($firstFileFormat, $supportedFormats, true)
        || !in_array($secondFileFormat, $supportedFormats, true)
    ) {
        return false;
    }

    return ($firstFileFormat === $secondFileFormat && $firstFileFormat === JSON)
        || ($firstFileFormat !== JSON && $secondFileFormat !== JSON);
}

/**
 * @param string $firstFile
 * @param string $secondFile
 *
 * @return array<int, mixed>
 * @throws \Exception
 */
function getParsedFilesData(string $firstFile, string $secondFile): array
{
    $firstFileFormat = pathinfo($firstFile, PATHINFO_EXTENSION);
    $secondFileFormat = pathinfo($secondFile, PATHINFO_EXTENSION);

    if (!checkFileFormats($firstFileFormat, $secondFileFormat)) {
        throw new Exception('Unsupported file format.');
    }

    $firstFileData = (string) file_get_contents($firstFile);
    $secondFileData = (string) file_get_contents($secondFile);

    if ($firstFileFormat === JSON) {
        return [parseJson($firstFileData), parseJson($secondFileData)];
    }

    return [parseYaml($firstFileData), parseYaml($secondFileData)];
}

/**
 * @param string $yaml
 *
 * @return array<string|int, mixed>
 */
function parseYaml(string $yaml)
{
    return Yaml::parse($yaml, Yaml::PARSE_OBJECT_FOR_MAP);
}

/**
 * @return string[]
 */
function getSupportedFileFormats(): array
{
    return [YAML, JSON];
}
