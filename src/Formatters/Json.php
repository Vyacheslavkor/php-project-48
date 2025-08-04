<?php

namespace Formatters;

use Hexlet\Code\Differ\Diff;
use stdClass;

/**
 * @param \stdClass $diff
 *
 * @return string
 */
function json(stdClass $diff): string
{
    $iter = static function ($value) use (&$iter) {
        $keys = array_keys((array) $value);

        return array_reduce($keys, static function ($result, $key) use ($value, $iter) {
            $status = $value->$key->status ?? null;
            if ($status === Diff::NESTED) {
                $new_result[$key] = $iter($value->$key->children);
            } elseif ($status === Diff::ADDED) {
                $new_result['+ ' . $key] = is_object($value->$key->newValue)
                    ? $iter($value->$key->newValue)
                    : $value->$key->newValue;
            } elseif ($status === Diff::UNCHANGED) {
                $new_result[$key] = is_object($value->$key->oldValue)
                    ? $iter($value->$key->oldValue)
                    : $value->$key->oldValue;
            } elseif ($status === Diff::REMOVED) {
                $new_result['- ' . $key] = is_object($value->$key->oldValue)
                    ? $iter($value->$key->oldValue)
                    : $value->$key->oldValue;
            } elseif ($status === Diff::UPDATED) {
                $new_result['- ' . $key] = is_object($value->$key->oldValue)
                    ? $iter($value->$key->oldValue)
                    : $value->$key->oldValue;
                $new_result['+ ' . $key] = is_object($value->$key->newValue)
                    ? $iter($value->$key->newValue)
                    : $value->$key->newValue;
            } else {
                $new_result[$key] = $value->$key;
            }

            return array_merge($result, $new_result);
        }, []);
    };

    return (string) json_encode((array) $iter($diff), JSON_PRETTY_PRINT);
}
