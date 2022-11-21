<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use  function Differ\genDiff;

class GenDiffTest extends TestCase
{
    public function getFixtureFullPath(string $fixtureName): string
    {
        $parts = [__DIR__, 'fixtures', $fixtureName];
        return (string) realpath(implode('/', $parts));
    }

    public function testDiff(): void
    {
        $expected = file_get_contents($this->getFixtureFullPath('diff'));

        $this->assertEquals(true, true);
    }
}
