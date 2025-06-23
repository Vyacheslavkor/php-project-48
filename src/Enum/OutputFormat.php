<?php

namespace Hexlet\Code\Enum;

use ReflectionClass;

class OutputFormat
{
    public const STYLISH = 'stylish';
    public const PLAIN = 'plain';
    public const JSON = 'json';

    /**
     * @return array<string>
     */
    public static function getAll(): array
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    }
}
