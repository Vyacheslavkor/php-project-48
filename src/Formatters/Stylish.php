<?php

namespace Formatters;

/**
 * @param array<string, mixed> $diff
 *
 * @return string
 */
function stylish(array $diff): string
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
        $getSpace = static fn($val) => $val === ''
            ? ''
            : ' ';

        $lines = array_map(
            static fn($key, $val) => "{$getIndent($key)}{$key}:{$getSpace($val)}{$iter($val, $depth + 1)}",
            array_keys($value),
            $value
        );

        $result = ['{', ...$lines, "{$bracketIndent}}"];

        return implode("\n", $result);
    };

    return $iter($diff, 1);
}
