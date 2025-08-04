<?php

namespace Formatters;

use Hexlet\Code\Differ\Diff;
use stdClass;

/**
 * @param stdClass $diff
 *
 * @return string
 */
function stylish(stdClass $diff): string
{
    $replacer = ' ';
    $spacesCount = 2;

    $iter = static function ($value, $depth) use (&$iter, $replacer, $spacesCount) {
        if (!is_object($value)) {
            return toString($value);
        }

        $indentSize = $spacesCount * $depth + $spacesCount * ($depth - 1);
        $getIndent = static fn($val) => is_object($val) && property_exists($val, 'status') && in_array(
            $val->status,
            [Diff::ADDED, Diff::REMOVED, Diff::UPDATED],
            true
        )
            ? str_repeat($replacer, $indentSize)
            : str_repeat($replacer, $indentSize + $spacesCount);
        $bracketIndent = str_repeat($replacer, $indentSize - $spacesCount);

        $lines = array_map(
            static function ($key, $val) use ($getIndent, $iter, $depth) {
                $is_object = false;
                if (!($val instanceof stdClass) || !property_exists($val, 'status')) {
                    $valueToShow = $val;
                } else {
                    $valueToShow = match ($val->status) {
                        Diff::ADDED => $val->newValue,
                        Diff::REMOVED, Diff::UNCHANGED => $val->oldValue,
                        Diff::NESTED => $val->children,
                        default => null
                    };

                    $is_object = true;
                }

                if ($is_object) {
                    if ($val->status === Diff::REMOVED) {
                        return "{$getIndent($val)}- {$key}: {$iter($valueToShow, $depth + 1)}";
                    }

                    if ($val->status === Diff::ADDED) {
                        return "{$getIndent($val)}+ {$key}: {$iter($valueToShow, $depth + 1)}";
                    }

                    if ($val->status === Diff::UPDATED) {
                        $lineBefore = "{$getIndent($val)}- {$key}: {$iter($val->oldValue, $depth + 1)}";
                        $lineAfter = "{$getIndent($val)}+ {$key}: {$iter($val->newValue, $depth + 1)}";

                        return "$lineBefore\n$lineAfter";
                    }

                    return "{$getIndent($val)}$key: {$iter($valueToShow, $depth + 1)}";
                }

                return "{$getIndent($val)}$key: {$iter($valueToShow, $depth + 1)}";
            },
            array_keys((array) $value),
            (array) $value
        );

        $result = ['{', ...$lines, "{$bracketIndent}}"];

        return implode("\n", $result);
    };

    return $iter($diff, 1);
}
