<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use  function Differ\genDiff;

class GenDiffTest extends TestCase
{
    public function getFixtureFullPath($fixtureName)
    {
        $parts = [__DIR__, 'fixtures', $fixtureName];
        return realpath(implode('/', $parts));
    }

    public function testDiff(): void
    {
        $expected = file_get_contents($this->getFixtureFullPath('diff'));

        $this->assertEquals($expected, genDiff('files/file1.json', 'files/file2.json'));
    }
}
