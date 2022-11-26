<?php

namespace Differ\Tests;

use Docopt\Response;
use PHPUnit\Framework\TestCase;

use function Differ\genDiff;
use function Differ\getDoc;
use function Differ\getFilePathsFromArgs;
use function Differ\getArgs;

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

        $this->assertEquals($expected, genDiff('files/file1.json', 'files/file2.json'));

        $response = new Response(['<firstFile>' => 'files/file1.json', '<secondFile>' => 'files/file2.json']);
        $expected2 = ['files/file1.json', 'files/file2.json'];

        $this->assertEquals($expected2, getFilePathsFromArgs($response));

        $this->expectException(\Exception::class);
        genDiff('files/file1.empty', 'files/file2.empty');
    }

    public function testDoc(): void
    {
        $output = file_get_contents($this->getFixtureFullPath('help'));
        if ($output) {
            $output = trim($output);
        }

        $expected = new Response([], 1, (string) $output);

        $args = getArgs(['exit' => false, 'exitFullUsage' => true]);

        $this->assertEquals($expected, $args);
    }
}
