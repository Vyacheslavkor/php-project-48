<?php

namespace Hexlet\Code\Enum;

use ReflectionClass;

class FileFormat
{
    public const JSON = 'json';
    public const YAML = 'yaml';

    /**
     * @return array<string>
     */
    public static function getAll(): array
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }
}
