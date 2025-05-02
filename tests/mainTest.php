<?php

namespace test;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

final class MainTest extends TestCase
{
    public function testFirst()
    {

        $gendiff = genDiff('tests/fixtures/file1.json', 'tests/fixtures/file2.json');

        $fileName = 'tests/fixtures/result.txt';
        $expected = file_get_contents($fileName);

        if ($fileName === false) {
            throw new \Exception('Не удалось открыть файл для чтения.');
        }
        $this->assertEquals($expected, $gendiff);
    }
}
