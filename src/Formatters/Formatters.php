<?php

namespace Formatters;

use Hexlet\Code\Enum\OutputFormat;
use RuntimeException;
use stdClass;

/**
 * @param mixed $value
 *
 * @return string
 */
function toString(mixed $value): string
{
    if ($value === null) {
        return 'null';
    }

    return trim(var_export($value, true), "'");
}

/**
 * @param stdClass $diff
 * @param string    $format
 *
 * @return string
 */
function getFormattedDiff(stdClass $diff, string $format = OutputFormat::STYLISH): string
{
    $fn = __NAMESPACE__ . '\\' . $format;
    if (!is_callable($fn) || !isAvailableFormat($format)) {
        throw new RuntimeException(sprintf('Unknown format: %s', $format));
    }

    return $fn($diff);
}

/**
 * @param string $format
 *
 * @return bool
 */
function isAvailableFormat(string $format): bool
{
    return in_array($format, getAvailableFileFormats(), true);
}

/**
 * @return array<string>
 */
function getAvailableFileFormats(): array
{
    return OutputFormat::getAll();
}
