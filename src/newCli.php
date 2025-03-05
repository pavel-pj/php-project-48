<?php

namespace Hexlet\Code;

use Docopt;
use Mockery\Exception;
use PHPUnit\Framework\Error;
use Symfony\Component\Yaml\Yaml;
use Hexlet\Code\newFormat;
use Illuminate\Support\Collection;
use Hexlet\Code\FileType;
use Hexlet\Code\TreeService;

class newCli
{
    public $params;
    public const NOT_EXIST = 'not_exists';
    public const FIRST_FILE = 'first_file';
    public const SECOND_FILE = 'second_file';

    public array $file01;
    public array $file02;

    public $checkedFile;

    public newFormat $formater;
    public TreeService $treeService;

    public function __construct(array|null $params = [])
    {
        $this->params = array_values($params);
        $this->treeService = new TreeService();
        $this->formater = new newFormat();
    }

    public static function cli($params)
    {
        unset($params[0]);
        return new self($params);
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
            } elseif (substr($param, 0, 1) !== '-') {
                $filesPath [] = $param;
            }
        }

        $filesData = [];
        $filesData[] =  $this->parse($filesPath[0]);
         $filesData[] =  $this->parse($filesPath[1]);

       $result = $this->greatDiff($filesData[0], $filesData[1]);

       $resultFlatten =  $this->flat($result);

       // print_r($resultFlatten);

        $this->formater->formatData($resultFlatten);
    }
    public function greatDiff(array $file1, array $file2)
    {

        $this->file01 = $this->sortDiff($file1);
        $this->file02 = $this->sortDiff($file2);

        $this->checkedFile = self::FIRST_FILE;
        $res1 = $this->iterateFile(
            $this->file01,

            true,

            ['root']
        );

        $this->checkedFile = self::SECOND_FILE;
        $res2 = $this->iterateFile(
            $this->file02,

            true,

            ['root']
        );

        $res03 = array_merge_recursive($res1, $res2);

        return $res03;
    }

    public function iterateFile(
        $node1, // Элемент первого файл ( узел)
        bool  $isPreviousFolderExists  , // если корневая директория НЕ НАЙДЕНА(false) - не проверяем вложенные
        array $accPath = [], //массив пути до файла/директории
        $key1 = null
    )
    {

        $node2 = $this->getFileToCompare();
        $firstOrSecondFile = $this->checkedFile;

        //При проверке второго файла добавляем только не найденное со знаком +, т.е наоборот.



        //Для файла мы не увеличиваем путь
        if ($key1) {
            $accPath[] = $key1;
        }

        //Если узел
        if (!is_array($node1)) {
            return $this->getDiffFile(
                $node1,
                $isPreviousFolderExists,
                $accPath,
                $key1
            );
        }

        //Если папка не существует во 2 файле, вложенные файлы не анализируются
        //По умолчанию текущую папку и вложенные не проверяем, если предыдущая не найдена
        $isFolderExists = false;
        if ($isPreviousFolderExists) {
            $fileToCheck = $this->getFileToCompare();
            $isFolderExists = $this->treeService->isNodeInOtherFile($accPath, FileType::Directory->value,$fileToCheck);

        }

        $node1Childs = array_map(function ($value, $key) use ($node2, $accPath, $isFolderExists ) {
            $result = $this->iterateFile(
                $value,
                $isFolderExists,
                $accPath,
                $key
            );
            return $result;
        }, $node1, array_keys($node1));

        // Для проверки узлом при 2 рекрусии, т.е из 2 json в первый - узлый(файлы),
        // которые совпадают по ключу и значению
        // маркируются 00, т.е в итоговом массиве будет 00myFile.
        // на этом этапе исключаем такие файлы, т.к они уже есть при первой проверке

         $filteredResult = array_filter($node1Childs, function ($item) {
            //return substr($item['value'], 0, 2) !== '00';
             return $item;
        });

        //текущая директория
        $result = $this->getDiffDirectory(
            $node1,
            $isPreviousFolderExists,
            $filteredResult,
            $accPath,

        );
        return $result;
    }


    //Получение ОБЩЕГО файл при проверке
    //При первой проверке вхождений узлов второго файла в первый просто получаем JSON2
    public function getFileToCompare () {
        $node = $this->file02;
        if ($this->checkedFile === self::SECOND_FILE)
        {
            $node = $this->file01;
        }
        return $node;
    }

    public function getDiffDirectory(
        $node1,
        bool $isPreviousFolderExists,
        array $childs,
        array $path,

    ) {
        //node1 - искомая директория
        //node2 - весь массив другого файла
        //path - массив ключей
        //Для корневой директории $path = root

        $node2 = $this->getFileToCompare();
        $firstOrSecondFile = $this->checkedFile;

        $dir1Val = $this->getNormalizeValue($path[count($path) - 1]);

        $fileToCheck = $this->getFileToCompare();
        $isNodeInOtherFile = $this->treeService->isNodeInOtherFile($path,FileType::Directory->value,$fileToCheck);

        $comparison = $this->getTypeOfComparingFileName($dir1Val, $isNodeInOtherFile,$isPreviousFolderExists);

        return [
            'path' => $path,
            'type' => FileType::Directory->value,
            'childs' =>  $childs,
            //'value' =>  $path[count($path)-1],
            'comparison' => $comparison

        ];
    }

    public function getDiffFile(
        $node1,
        bool $isPreviousFolderExists,
        $accPath,
        $key1 = null
    ) {
        $resultToReturn = '';
        //Если папка, в которой находится данный файл не найдена во 2 файле, то не выполняем проверку

        $node2 = $this->getFileToCompare();
        $firstOrSecondFile = $this->checkedFile;

        $file1Val = $this->getNormalizeValue($node1);
        $fileToCheck = $this->getFileToCompare();
        $isNodeInOtherFile = $this->treeService->isNodeInOtherFile( $accPath ,FileType::File->value,$fileToCheck);



        $comparison = $this->getTypeOfComparingFileName(
            $file1Val,
            $isNodeInOtherFile,
            $isPreviousFolderExists,
            $accPath
        );

        return [
            'path' => $accPath,
            'type' => FileType::File->value,
            'value' => $file1Val,
            'comparison' => $comparison

        ];
    }

    public function getTypeOfComparingFileName(
        string $fileName01,
        bool $isNodeInOtherFile,
        bool $isPreviousFolderExists,
        array $accPath = [])
    {
        /** $isPreviousFolderExists - если true, то данный файл не проверяется.
         *  Проверка не требуется, если имя корневой директории - !matched
         */

        /**
         * matched - полностью сходятся ключ и значение
         * changed - найден ключ, значение поменялось
         * deleted - ключ удален
         * added   - добавлен новый ключ
         */


        if (!$isPreviousFolderExists) {
            return $comparison = 'matched';
        }

        $comparison = 'matched';

        if ($this->checkedFile === self::FIRST_FILE ) {

           //Для найденных ключей, но разных значений, в первом файле
           if ($isNodeInOtherFile) {
               //Если ключи файлов совпадают, но их значения - нет.
               $isFileValueChanged = $this->isFileValueChanged($accPath);
                  if (!$isFileValueChanged) {
                      $comparison = 'deleted';
                  }

           } elseif(!$isNodeInOtherFile) {
               $comparison = 'deleted';
           }
        }

        if ($this->checkedFile === self::SECOND_FILE ) {

            //Для найденных ключей, но разных значений, в первом файле
            if ($isNodeInOtherFile) {
                //Если ключи файлов совпадают, но их значения - нет.
                $isFileValueChanged = $this->isFileValueChanged($accPath);
                if (!$isFileValueChanged) {
                    $comparison = 'added';
                }

            } elseif(!$isNodeInOtherFile ) {
                $comparison = 'added';
            }
        }

        return $comparison;

    }

    //Проверяет, совпадают ли два файла с одинаковым ключим и path
    public function isFileValueChanged (array $path): bool {


        if (!$path) return true;

        $file01Value = $this->getFileByPath($path, self::FIRST_FILE);
        $file02Value = $this->getFileByPath($path, self::SECOND_FILE);

        return ($file01Value === $file02Value);
    }

    public function getFileByPath(array $path, string $fileType): string {
        $node = $this->file01;
        if ($fileType=== self::SECOND_FILE)
        {
            $node = $this->file02;
        }

        $result = $node;
        if (!empty($path)) {
            foreach ($path as $key) {
                //Нашли ключ
                 if (array_key_exists($key, $result)) {
                    $result = $result[$key];
                }
            }
        }

       // echo "Возращаем результат";

        return $result;

    }



    public function flat(array $node)
    {
        /**  выравнивание в одну плоскость
         *
         * Все Директории имеют вид :
         * [
         * 'path' => ['root','common'],
         * 'type' => 'directory',
         * 'childs'=>  array
         * ]
         *
         * Файлы :
         * [
         * 'path' => ['root','common'],
         * 'type' => 'file',
         * 'value' =>"-+ common'
         * ]
         */
        $format = [];
        $result = $this->flatIterate($node, $format);
        return $format;
    }

    public function flatIterate($node, &$acc)
    {
        if (!is_array($node)) {
            return $node;
        }

        $childs = array_map(function ($item) use (&$acc) {
            return $this->flatIterate($item, $acc);
        }, $node);

        if ($this->treeService->isFile($node)) {
            $acc[] = [
                'path' => $node['path'],
                'type' => $node['type'],
                'value' => $node['value'],
                'comparison' => $node['comparison']
            ];
        } elseif ($this->treeService->isDirectory($node)) {
            $acc[] = [
                'path' => $node['path'],
                'type' => $node['type'],
                'value' => $node['value'],
                'comparison' => $node['comparison']
            ];
        }
        return $childs ;
    }
    //Приводит "неудобные значения" true,false,null  - к их строковым аналогам
    public function getNormalizeValue($value)
    {
        $result = $value;

        if ($value === true) {
            $result = 'true';
        } elseif ($value === false) {
            $result = 'false';
        } elseif ($value === null) {
            $result = 'null';
        }
        return $result;
    }

    public function sortDiff($node)
    {
        if (!is_array($node)) {
            return $node;
        }

        $childs = array_map(function ($value) {
            return $this->sortDiff($value);
        }, $node);

        ksort($childs, 3);
        return $childs;
    }

    //Открывает файл и переводит в универсальный набор массивов
    // данные любого формата

    public function parse(string $filePath): array
    {

        $content = '';
        $file = fopen($filePath, 'r');
        if ($file) {
            $content = fread($file, filesize($filePath)); // Читаем содержимое файла
            fclose($file); // Закрываем файл
        } else {
            echo "Невозможно открыть файл";
            exit;
        }

        $arrType = explode('.', $filePath);
        $type = $arrType[count($arrType) - 1];

        $result = [];

        switch ($type) {
            case 'json':
                $result = json_decode($content, true);
                break;
            case 'yaml':
                $result = Yaml::parse($content);
                break;
            default:
                $result = [];
                break;
        }

        return $result;
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
}
