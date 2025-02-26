<?php

namespace Hexlet\Code;

use Docopt;
use Mockery\Exception;
use Symfony\Component\Yaml\Yaml;
use Hexlet\Code\Format;
use Illuminate\Support\Collection;
use Hexlet\Code\FileType;
use Hexlet\Code\TreeService;

class Cli
{
    public $params;
    public const NOT_EXIST = 'not_exists';
    public const FIRST_FILE = 'first_file';
    public const SECOND_FILE = 'second_file';
    public Format $formater;
    public TreeService $treeService;

    public function __construct(array|null $params = [])
    {
        $this->params = array_values($params);
        $this->treeService = new TreeService();
        $this->formater = new Format();
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


       // $fileNorm1 = $this->fileNorm1($filesData[0]);

      //  print_r($fileNorm1);



        $result = $this->greatDiff($filesData[0], $filesData[1]);

        $resultFlatten =  $this->flat($result);

        // print_r($resultFlatten);

        $this->formater->formatData($resultFlatten);
    }
    public function greatDiff(array $file1, array $file2)
    {
        $sortedFile1 = $this->sortDiff($file1);
        $sortedFile2 = $this->sortDiff($file2);


        print_r($sortedFile1);


        $res1 = $this->iterateFile(
            $sortedFile1,
            $sortedFile2,
            true,
            self::FIRST_FILE,
            ['root']
        );

        $res2 = $this->iterateFile(
            $sortedFile2,
            $sortedFile1,
            true,
            self::SECOND_FILE,
            ['root']
        );

        $res03 = array_merge_recursive($res1, $res2);

        return $res03;
    }

    public function iterateFile(
        $node1, // Элемент первого файл ( узел)
        $node2, // Второй файл целиком
        bool $isPreviousFolderExists, // если корневая директория НЕ НАЙДЕНА(false) - не проверяем вложенные
        string $firstOrSecondFile, //Первый файл = self::FIRST_FILE, второй - self::SECOND_FILE
        array $accPath = [], //массив пути до файла/директории
        $key1 = null
    ) {      // текущий ключ

         //При проверке второго файла добавляем только не найденное со знаком +, т.е наоборот.

         //Для файла мы не увеличиваем путь
        if ($key1) {
            $accPath[] = $key1;
        }

        //Если узел
        if (!is_array($node1)) {
            return $this->getDiffFile(
                $node1,
                $node2,
                $isPreviousFolderExists,
                $firstOrSecondFile,
                $accPath,
                $key1
            );
        }

        //Если папка не существует во 2 файле, вложенные файлы не анализируются
        //По умолчанию текущую папку и вложенные не проверяем, если предыдущая не найдена
        $isFolderExists = false;
        if ($isPreviousFolderExists) {
            $isFolderExists = $this->isFolderExists($node2, $accPath);
        }

        $node1Childs = array_map(function ($value, $key) use ($node2, $accPath, $isFolderExists, $firstOrSecondFile) {
             $result = $this->iterateFile(
                 $value,
                 $node2,
                 $isFolderExists,
                 $firstOrSecondFile,
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
            return substr($item['value'], 0, 2) !== '00';
        });

        //текущая директория
        $result = $this->getDiffDirectory(
            $node1,
            $node2,
            $isPreviousFolderExists,
            $filteredResult,
            $accPath,
            $firstOrSecondFile
        );
        return $result;
    }
    public function isFolderExists($node2, $path = []): bool
    {
        $result = $this->getNodeOrFalse($node2, $path);
        if ($result === self::NOT_EXIST) {
            return false;
        }
        //Если директория сущесвтует во втором файле, true
        return true;
    }

    public function getDiffDirectory(
        $node1,
        $node2,
        bool $isFolderExists,
        array $childs,
        array $path,
        string $firstOrSecondFile //Первый файл = self::FIRST_FILE, второй - self::SECOND_FILE
    ) {
        //node1 - искомая директория
        //node2 - весь массив другого файла
        //path - массив ключей
        //Для корневой директории $path = root

        //Имя директории  - последний элемент массива path

        $dir1Value = $this->getNormalizeValue($path[count($path) - 1]);
        $dir2Value = $this->getNodeOrFalse($node2, $path);

        //Директория найдена, или же родитель не найден, значит не анализируется текущая директория
        $result = "  " . $dir1Value;
        if ($firstOrSecondFile === self::FIRST_FILE) {
            //Директория анализируется если Родительская директория найдена
            if ($isFolderExists) {
                //Директория найдена во 2 файле
                if ($dir2Value !== self::NOT_EXIST) {
                    //Директория найдена
                    $result = '  ' . $dir1Value;
                } else {
                    $result = " - {$dir1Value}";
                }
            }
        } elseif ($firstOrSecondFile === self::SECOND_FILE) {
            if ($isFolderExists) {
                //Директория найдена во 2 файле
                if ($dir2Value !== self::NOT_EXIST) {
                    //Директория найдена
                    $result = '  ' . $dir1Value;
                } else {
                    $result = "- {$dir1Value}";
                }
            }
        }

        return [
            'path' => $path,
            'type' => FileType::Directory->value,
            'childs' =>  $childs,
            'value' => $result
        ];
    }

    public function getDiffFile(
        $node1,
        $node2,
        bool $isFolderExists,
        string $firstOrSecondFile,
        $accPath,
        $key1 = null
    ) {
        $resultToReturn = '';
        //Если папка, в которой находится данный файл не найдена во 2 файле, то не выполняем проверку



        $file1Value = $this->getNormalizeValue($node1);
        $resultToReturn = "  {$key1}: {$file1Value}";

        //ДЛЯ ПЕРВОГО ФАЙЛА
        if ($firstOrSecondFile === self::FIRST_FILE) {
            if ($isFolderExists) {
                $file2Value = $this->getNodeOrFalse($node2, $accPath);
                //Если нашли

                if ($file2Value !== self::NOT_EXIST) {
                    $file2Value = $this->getNormalizeValue($file2Value);
                    //  echo "ключ : {$key1}, знч file1: {$file1Value} ; file2: {$file2Value}\n\n";

                    if ($file1Value === $file2Value) {
                        //Если значение узлов совпадают
                        $result = "  {$key1}: {$file1Value}";
                    } else {
                        //значения узлов НЕ совпадают
                        $result = "- {$key1}: {$file1Value} |";
                        $result .= "+ {$key1}: {$file2Value}";
                    }
                    $resultToReturn = $result;
                } else {
                    //ЕСЛИ УЗЕЛ НЕ НАЙДЕН ВО 2 ФАЙЛЕ
                    $resultToReturn = "- {$key1}: {$file1Value}";
                }
            }
        } elseif ($firstOrSecondFile == self::SECOND_FILE) {
            //ДЛЯ ВТОРОГО ФАЙЛА
            $file2Value = $this->getNodeOrFalse($node2, $accPath);
          //  echo "\n\n ПОСЛЕ ПРОВЕРКИ :\n";
          //  echo "ПУТЬ :\n";
          //  print_r($accPath);
          //  echo "ЗНАЧЕНИЕ :\n";
          //  print_r($file2Value);

           // echo "сравниваем значение {$file2Value} и ". self::NOT_EXIST . "\n";
            //echo "если сравнение пошло на следующей строке АПА\n";
            if ($file2Value === self::NOT_EXIST) {
               // echo "АПА\n\n\n";
               // echo "\nФАЙЛ 2. Значение узла в первом файле с ключом {$key1} :{$file2Value}\n";
                $resultToReturn = "+ {$key1}: {$file1Value}";
            } elseif ($file1Value === $file2Value) {
                //флаг, впоследсви такие файлы удаляются, так как они уже есть при первой проверке
                $resultToReturn = "00{$key1}: {$file1Value}";
            } else {
                $resultToReturn = "00{$key1}: {$file1Value}";
            }
        }

        return [
            'path' => $accPath,
            'type' => FileType::File->value,
            'value' => $resultToReturn
        ];
    }

    public function getNodeOrFalse($node, array $path)
    {
        //path  - массив со списками ключей
        //Для корневой директории $path = root
        $normPath = array_filter($path, function ($item) {
            return $item !== 'root';
        });

        $result = $node;

        if (!empty($normPath)) {
            foreach ($normPath as $key) {
                 //Нашли ключ

                if (!is_array($result)) {
                    return self::NOT_EXIST;
                }
                if (array_key_exists($key, $result)) {
                     $result = $result[$key];
                } else {
                    return self::NOT_EXIST;
                }
            }
        }

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
                'value' => $node['value']
            ];
        } elseif ($this->treeService->isDirectory($node)) {
            $acc[] = [
                'path' => $node['path'],
                'type' => $node['type'],
                'value' => $node['value']
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
