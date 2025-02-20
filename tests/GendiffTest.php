<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Cli;

class GendiffTest extends TestCase
{
    public function testGendiff(): void
    {
         $cli = new Cli();

         $file1 = $cli->parsing('files/file1.json');
         $file2  = $cli->parsing('files/file2.json');
         $fileExample = json_encode($cli->parsing('tests/fixtures/example.json'), JSON_PRETTY_PRINT);

         $result = $cli->genDiff($file1, $file2);

         $this->assertEquals($result, $fileExample);
    }
}
