<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Cli;

class GendiffTest extends TestCase
{
    public function testGendiff(): void
    {
         $cli = new Cli();
         $file1 = '{"host":"hexlet.io","timeout":50,"proxy":"123.234.53.22","follow":false}';
         $file2  = '{"timeout":20,"verbose":true,"host":"hexlet.io"}';
         $example = [
            "- follow" => false,
            "host" => "hexlet.io",
            "- proxy" => "123.234.53.22",
            "- timeout" => 50,
            "+ timeout" => 20,
            "+ verbose" => true
         ];
         $fileExample = json_encode($example, JSON_PRETTY_PRINT);
         $result = $cli->genDiff($file1, $file2);

         $this->assertEquals($result, $fileExample);
    }
}
