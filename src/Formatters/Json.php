<?php

namespace Formatters;

/**
 * @param array<string, mixed> $diff
 *
 * @return string
 */
function json(array $diff): string
{
    return (string) json_encode($diff, JSON_PRETTY_PRINT);
}
