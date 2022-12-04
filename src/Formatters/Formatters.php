<?php

namespace Formatters;

use Exception;

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
 * @param string               $format
 *
 * @return string
 * @throws \Exception
 */
function getFormattedDiff(array $diff, string $format = 'stylish'): string
{
    $fn = __NAMESPACE__ . '\\' . $format;
    if (!is_callable($fn) || !in_array($format, ['stylish', 'plain', 'json'], true)) {
        throw new Exception(sprintf('Unknown format: %s', $format));
    }

    return $fn($diff);
}
