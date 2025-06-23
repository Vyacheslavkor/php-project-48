<?php

namespace Parsers;

use Hexlet\Code\Enum\FileFormat;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

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
 * @throws RuntimeException
 */
function parseFileData(string $firstFile, string $secondFile): array
{
    if (!file_exists($firstFile) || !file_exists($secondFile)) {
        throw new RuntimeException(sprintf('File %s or %s not exists.', $firstFile, $secondFile));
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
function checkFilesFormat(string $firstFileFormat, string $secondFileFormat): bool
{
    return isAvailableFileFormat($firstFileFormat)
        && isAvailableFileFormat($secondFileFormat)
        && $firstFileFormat === $secondFileFormat;
}

/**
 * @param string $format
 *
 * @return bool
 */
function isAvailableFileFormat(string $format): bool
{
    $supportedFormats = getSupportedFileFormats();

    return in_array($format, $supportedFormats, true);
}

/**
 * @param string $firstFile
 * @param string $secondFile
 *
 * @return array<int, mixed>
 * @throws RuntimeException
 */
function getParsedFilesData(string $firstFile, string $secondFile): array
{
    $firstFileFormat = getFileFormat($firstFile);
    $secondFileFormat = getFileFormat($secondFile);

    if (!checkFilesFormat($firstFileFormat, $secondFileFormat)) {
        throw new RuntimeException('Unsupported file format.');
    }

    $firstFileData = (string) file_get_contents($firstFile);
    $secondFileData = (string) file_get_contents($secondFile);

    $format = $firstFileFormat;

    $map = [
        FileFormat::JSON => fn() => [parseJson($firstFileData), parseJson($secondFileData)],
        FileFormat::YAML => fn() => [parseYaml($firstFileData), parseYaml($secondFileData)],
    ];

    return $map[$format]();
}

/**
 * @param string $file
 *
 * @return string
 */
function getFileFormat(string $file): string
{
    return pathinfo($file, PATHINFO_EXTENSION);
}

/**
 * @param string $yaml
 *
 * @return object
 */
function parseYaml(string $yaml): object
{
    return Yaml::parse($yaml, Yaml::PARSE_OBJECT_FOR_MAP);
}

/**
 * @return string[]
 */
function getSupportedFileFormats(): array
{
    return FileFormat::getAll();
}
