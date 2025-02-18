<?php

namespace Hexlet\Code;

use Docopt;
use Mockery\Exception;

class Cli
{
    public $params;

    public function __construct($params)
    {
        unset($params[0]);
        $this->params = array_values($params);
    }

    public function runProgram()
    {
        $filesPath = [];

        foreach ($this->params as $param) {
            if ($param === '-h') {
                 if (count($filesPath) > 0) {
                    throw new Exception("Ошибка ввода: сначала должны быть команды");
                 }
                $this->showInfo();
                exit;
            }
             else if (substr($param, 0, 1) !== '-') {
                $filesPath [] = $param;
            }
        }

        $filesData = [];

        $filesData[] =  $this->parsing($filesPath[0]);
        $filesData[] =  $this->parsing($filesPath[1]);


        $diff = $this->genDiff($filesData[0],$filesData[1]);
        echo $diff;


    }

    public function showInfo()
    {
        $doc = <<<'DOCOPT'
Generate diff

Usage:
  gendiff (-h|--help)
  gendiff (-v|--version)
  gendiff [--format <fmt>] <firstFile> <secondFile>

Options:
  -h --help                     Show this screen
  -v --version                  Show version
  --format <fmt>                Report format [default: stylish]

DOCOPT;

        $result = Docopt::handle($doc, array('version' => '1.0.0rc2'));
        foreach ($result as $k => $v) {
            echo $k . ': ' . json_encode($v) . PHP_EOL;
        }
    }

    public function parsing($filePath):array {

        $content = '';
        $file = fopen($filePath,'r');
        if ($file) {

            $content = fread($file, filesize($filePath)); // Читаем содержимое файла

            fclose($file); // Закрываем файл
        } else {
            echo "Невозможно открыть файл";
            exit;
        }

        return json_decode($content,true);

    }


    public function genDiff (array $file1, array $file2) {

        ksort($file1);
        ksort($file2);

        $result = [];
        foreach ($file1 as $key=>$value) {

            //одинаковы
            if (array_key_exists($key,$file2) and $file2[$key]===$value) {

                $result [$key] =[$key =>$value];
               }
            else if(array_key_exists($key,$file2)){
                $result [$key] = [
                    '- '.$key => $value,
                    '+ '.$key => $file2[$key]

                ];
            }
            else {
                $result [$key] = ['- '.$key => $value];
            }
        }

       foreach ($file2 as $key=>$value){
           if (!array_key_exists($key, $file1)){
               $result [$key] = ['+ '.$key =>$value];
           }
       }

       ksort($result);

       //flat
       $result2 = [];
       foreach($result as $item){
           foreach ($item as $key=>$value) {
               $result2 [$key] = $value;
           }
       }


       return json_encode($result2);

    }

}
