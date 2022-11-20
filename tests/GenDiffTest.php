<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use  function Differ\genDiff;

class GenDiffTest extends TestCase
{
    public function testDiff(): void
    {
        $result = <<<HEDEROC
{
  - follow: false
    host: hexlet.io
  - proxy: 123.234.53.22
  - timeout: 50
  + timeout: 20
  + verbose: true
}

HEDEROC;

        $this->assertEquals($result, genDiff('files/file1.json', 'files/file2.json'));
    }
}
