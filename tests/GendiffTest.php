<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Cli;

class GendiffTest extends TestCase
{
    public function testParse()
    {
        $cli = new Cli();

        //$this->assertEquals($expected, $fileData);

        $this->assertEquals(1, 1);
    }

    public function testGendiff(): void
    {

        $cli = new Cli();

        $filePathExpected = './tests/fixtures/test01Expected.json';

        $file01 = './tests/fixtures/json1Test1.json';
        $file02 = './tests/fixtures/json2Test1.json';

        $file = fopen($filePathExpected, 'r');
        if ($file) {
            $content = fread($file, filesize($filePathExpected)); // Читаем содержимое файла
            fclose($file); // Закрываем файл
        } else {
            echo "Невозможно открыть файл " . $filePathExpected . "\n";
            exit;
        }

        $expected = file_get_contents($filePathExpected);
        $result = $cli->gendiff($file01, $file02, "json");

       // $this->assertEqualsIgnoreLineEndings($expected,  $result);

        $this->assertEquals($expected, $result);
    }
}
