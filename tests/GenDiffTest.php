<?php

namespace Differ\Tests;

use Docopt\Response;
use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;
use function Differ\Differ\getFilePathsFromInput;
use function Differ\Differ\parseInput;
use function Differ\Differ\getFormat;

class GenDiffTest extends TestCase
{
    public function getFixtureFullPath(string $fixtureName): string
    {
        $parts = [__DIR__, 'fixtures', $fixtureName];

        return (string) realpath(implode('/', $parts));
    }

    public function testDiffJson(): void
    {
        $expected = file_get_contents($this->getFixtureFullPath('diff'));

        $this->assertEquals($expected, genDiff('files/file1.json', 'files/file2.json'));
    }

    public function testDoc(): void
    {
        $output = file_get_contents($this->getFixtureFullPath('help'));
        if ($output) {
            $output = trim($output);
        }

        $expected = new Response([], 1, (string) $output);

        $args = parseInput(['exit' => false, 'exitFullUsage' => true]);

        $this->assertEquals($expected, $args);
    }

    public function testDiffUnknownFileFormat(): void
    {
        $this->expectException(\Exception::class);
        genDiff('files/file.empty', 'files/file2.empty');
    }

    public function testNotExistsFile(): void
    {
        $this->expectException(\Exception::class);
        genDiff('files/file4325.empty', 'files/file2345.empty');
    }

    public function testParseInput(): void
    {
        $response = new Response(['<firstFile>' => 'files/file1.json', '<secondFile>' => 'files/file2.json']);
        $expected = ['files/file1.json', 'files/file2.json'];

        $this->assertEquals($expected, getFilePathsFromInput($response));
    }

    public function testDiffYaml(): void
    {
        $expected = file_get_contents($this->getFixtureFullPath('diff'));

        $this->assertEquals($expected, genDiff('files/file1.yaml', 'files/file2.yaml'));
    }

    public function testUnknownDiffFormat(): void
    {
        $this->expectException(\Exception::class);
        genDiff('files/file1.json', 'files/file2.json', 'unknown');
    }

    public function testGetFormat(): void
    {
        $response = new Response([
            '--help'       => false,
            '--version'    => false,
            '--format'     => 'stylish',
            '<firstFile>'  => 'files/file1.json',
            '<secondFile>' => 'files/file2.json',
        ], 1, '');

        $this->assertEquals('stylish', getFormat($response));
    }

    public function testFlatDiff(): void
    {
        $expected = file_get_contents($this->getFixtureFullPath('flat_diff'));

        $this->assertEquals($expected, genDiff('files/file1.json', 'files/file2.json', 'plain'));
        $this->assertEquals($expected, genDiff('files/file1.yaml', 'files/file2.yaml', 'plain'));
    }

    public function testJsonDiff(): void
    {
        $expected = file_get_contents($this->getFixtureFullPath('json_diff'));

        $this->assertEquals($expected, genDiff('files/file1.json', 'files/file2.json', 'json'));
        $this->assertEquals($expected, genDiff('files/file1.yaml', 'files/file2.yaml', 'json'));
    }
}
