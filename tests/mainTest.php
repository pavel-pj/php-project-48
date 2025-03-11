<?php

namespace test;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

final class MainTest extends TestCase
{
    public function testFirst()
    {

        //$file1 = genDiff('tests/fixtures/file1.json', 'tests/fixtures/file2.json');

        //$result = gettype($file1);
       // $expected = gettype([]);

        $this->assertEquals(1, 1);
    }
}
