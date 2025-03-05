<?php

namespace Hexlet\Code;

use Hexlet\Code\Cli;
use Hexlet\Code\FileType;
use Hexlet\Code\TreeService;
use Error;

class Format
{
    public const SYMBOL = " ";
    public const FOLDER_INDENT = 4;
    public const FILE_INDENT = 2;

    public TreeService $treeService;
    public function __construct()
    {
        $this->treeService = new TreeService();
    }

    public function formatData(array $data)
    {
         $result = $this->normalize($data);
         print_r($result);
       //  $toPrint = $this->print($result);
       //  print_r($toPrint);
    }

    public function normalize($data)
    {
        //При слиянии массивов инфо по директории находится в item[0] ( К примеру, +- name)
        //Удаляем строки-дубликаты, которые появились как папки ( array[0], т.е удалить те, что имеют ключ 0)
        $result = [];

        foreach ($data as $item) {
            if ($this->treeService->isFile($item)) {
                // echo "ФАЙЛ : ".$item['value']." ; path : ". implode('/', $item['path']). "\n\n";
                $result [] = $this->createFold($item['path'], $item['value'], 'file');
            }
            if ($this->treeService->isDirectory($item)) {
                //echo "Папка : ". $item['value']." ; path : ". implode('/', $item['path']). "\n\n";
                $result [] = $this->createFold($item['path'], $item['value'], 'folder');
            }
        }
        return array_merge_recursive(...$result);
    }

    public function createFold($path, $item, $type)
    {
        //Стандартный отступ - 2

        $symbol = str_repeat(self::SYMBOL, self::FILE_INDENT);
        $indent = str_repeat($symbol, count($path));
        if ($type == 'folder') {
            $symbol = str_repeat(self::SYMBOL, self::FOLDER_INDENT);
            //4 * вложенность - 2
            $indent = str_repeat($symbol, count($path) - 1);
            $indent = substr($indent, 0, strlen($indent) - 2);
        }

        //Проверяем , не являетя ли значение именем дректории.
        //Имя директории хранится в элементе с ключом 0, т.е item[0], и так же будет последни в path

        //Создаётся новый массив с вложенными элементами, который сольётся с другими

        //Если для item два значения. (строки -+)
       // $rows = explode('|', $item);
       // $newItem = implode("\n{$indent}", $rows);

        //Пустые значения, появившиеся после слияния второго файла

        if (!$item) {
             return ['root' => []];
        }

        $value = $indent . $newItem  ;
        for ($i = count($path) - 1; $i >= 0; $i--) {
            $value = array($path[$i] => $value);
        }

            return $value;
    }

    public function print(array $data)
    {
        $res = $data['root'];
        unset($data[0]);
       // print_r($res);
        print_r($this->printIterate($res));
    }

    public function printIterate(array|string $node)
    {
        if (!is_array($node)) {
            return $node;
        }

        $childs = array_map(function ($item) {
            return $this->printIterate($item);
        }, $node);

        $folderN = $node[0];

         $files = array_filter($childs, function ($item) use ($folderN) {
             return $item !== $folderN;
         });

         $files = implode("\n", $files);

        $folderName = "{$node[0]}: {";
        //Для корня
        if ($node[0] === '  root') {
            $folderName = "{";
        }

        $closetString = $this->getCLosedBraseString($folderName) . "}";

        $folderTxt = "{$folderName}\n" . $files . "\n" . $closetString;
        return  $folderTxt;
    }

    public function getCLosedBraseString(string $folderName)
    {
        //по количеству символов вычисляется отсуп закрывающей скобки.
        // **  folder
        // **- folder
        // ******+ folder
        // space(2 + 4*x) + 2 спецсимвола
        // $folderName - первая строка с открывающимися скобками. Отступ кратен self::FOLDER_INDENT
        $symbols = [self::SYMBOL, '+', '-', ' '];
        $iterator = 0;
        while (in_array($folderName[$iterator], $symbols)) {
            $iterator += 1;
        }

        /*
        //Проверка на правильный результат
        if ($iterator % self::FOLDER_INDENT !== 0) {
            throw new Error("Ошибка. Неправильный расчет закрывающей скобки.\n
             folderName =|{$folderName}|=\n
             отступ = {$iterator}\n");
        }
        */
        return str_repeat(self::SYMBOL, $iterator);
    }
}
