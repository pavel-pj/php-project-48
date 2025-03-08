<?php

namespace Hexlet\Code;

use App\Http\Controllers\Auth\PasswordController;
use Docopt;
use Mockery\Exception;
use PHPUnit\Framework\Error;
use Symfony\Component\Yaml\Yaml;
use Hexlet\Code\Formaters\DiffFormat;
use Hexlet\Code\Formaters\PlainFormat;
use Illuminate\Support\Collection;
use Hexlet\Code\FileType;
use Hexlet\Code\TreeService;

class Cli
{
    public $params;


    public array $file01;
    public array $file02;

    public DiffFormat $formater;
    public TreeService $treeService;
    public PlainFormat $plainFormater;

    public function __construct(array|null $params = [])
    {
        $this->params = array_values($params);
        $this->treeService = new TreeService();
        $this->formater = new DiffFormat();
        $this->plainFormater = new PlainFormat();
    }

    public static function cli($params)
    {
        unset($params[0]);
        return new self($params);
    }

    public function runProgram()
    {

        $filesPath = [];
        $format = "diff";

        for ($i = 0; $i < count($this->params); $i++) {
            if ($this->params[$i] === '-h') {
                if (count($filesPath) > 0) {
                    throw new Exception("Ошибка ввода: сначала должны быть команды");
                }
                $this->showInfo();
                exit;
            } elseif ($this->params[$i] !== '-h') {
                //Выбор формата
                if ($this->params[$i] === "--format") {
                    if ($this->params[$i + 1] === "plain") {
                        $format = "plain";
                    }
                    $i += 1;
                } else {
                    $filesPath [] = $this->params[$i];
                }
            }
        }

        $result = $this->genDiff($filesPath[0], $filesPath[1], $format);
        echo $result;
    }

    public function genDiff(string $filePath1, string $filePath2, $format)
    {

        $filesData = [];
        $filesData[] =  $this->parse($filePath1);
        $filesData[] =  $this->parse($filePath2);

        $this->file01 = $this->makeNormalTree($filesData[0]);
        $this->file02 = $this->makeNormalTree($filesData[1]);

        $result = $this->diffTree();
        $sortResult = $this->sortTreeByComparison($result);

        if ($format === "diff") {
            $this->formater->formatData($sortResult);
        } elseif ($format === "plain") {
            $this->plainFormater->formatPlain($sortResult);
        }
    }

    public function sortTreeByComparison(&$node)
    {
        //Сортирует Дифф ( файл после сравнения.
        //1 По алфавиту
        // Сначала -(deleted), затем +(added)

        if (!is_array($node)) {
            return $node;
        }

        $childs = array_map(function ($item) {
            return $this->sortTreeByComparison($item);
        }, $node);

        if ($this->treeService->isDirectory($node)) {
            $arrs = $childs['childs'];

            usort($arrs, function ($a, $b) {

                if ($a['name'] > $b['name']) {
                    return 1;
                } elseif ($a['name'] < $b['name']) {
                    return -1;
                } else {
                    if ($a['comparison'] === 'deleted' && $b['comparison'] === 'added') {
                        return -1;
                    } elseif ($b['comparison'] === 'deleted' && $a['comparison'] === 'added') {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            });
            $childs['childs'] = $arrs;
        }
        return $childs;
    }

    public function diffTree()
    {
        $result = $this->iterateToDiff($this->file01, true);
        return $result;
    }

    public function iterateToDiff($node, bool $isNeedToCheckPreviousNode)
    {
        //Выводим дифф, всё, что имеется + данные из 2 файла

        if (!is_array($node)) {
            return $node;
        }

        $childs = array_map(function ($item) use ($isNeedToCheckPreviousNode) {

            $isNeedToCheck = $isNeedToCheckPreviousNode;

            //false - не проверяем
            if ($isNeedToCheckPreviousNode == true) {
                //Проверяем Директории ( имя )
                $accDir = [];
                if ($this->treeService->isDirectory($item)) {
                    $result = $this->findDirectory($item, $accDir);
                    //Если директория не найдена, acc удет пустой
                    if (!$accDir) {
                        $item['comparison'] = "deleted";
                    } else {
                        $item['comparison'] = $accDir['comparison'];
                        if (array_key_exists('updated', $accDir)) {
                            $item['updated'] = $accDir['updated'];
                        }
                    }

                    if ($item['comparison'] === "deleted") {
                        $isNeedToCheck = false;
                    } else {
                        $isNeedToCheck = true;
                    }
                }
            }
            return $this->iterateToDiff($item, $isNeedToCheck);
        }, $node);

        //Проверяем со вторым файлом.

        //Сюда заносим новые/имзененные узлы, которые нужно добавить в корень
        $addedNodes = [];
        foreach ($childs as &$item) {
            $acc = [];
            //Проверяем только файлы
            if ($this->treeService->isFile($item)) {
                $result = $this->findFile($item, $acc);
               // $item['comparison'] = $acc;
                if (array_key_exists('comparison', $acc)) {
                    $item['comparison'] = $acc['comparison'];
                    //Для имзененных. added добавлено ниже, в корневую директорию
                    if ($acc['comparison'] === 'changed') {
                        $item['comparison'] = 'deleted';
                        $addedNodes [] = $acc['newItem'];
                    }
                } else {
                    $item['comparison'] = "deleted";
                }

                //Если предыдущая директорию изменять не нужно
                if ($isNeedToCheckPreviousNode === false) {
                        $item['comparison'] = 'matched';
                }
            }
        }

        //определяем все имена папок и файлов, которые сейчас в текущей директории
        //Чтобы получить те узлы из нового списка, которых нет в текущем файле
        $nodeNames = $this->getAllNamesOfNode($node);

        $newNodes = $this->newNodesFrom2File($node, $nodeNames);

        foreach ($addedNodes as $addedNode) {
             $childs[] = $addedNode;
        }

        foreach ($newNodes as $newNode) {
            $childs['childs'][] = $newNode;
        }
        return $childs;
    }

    public function newNodesFrom2File(array $node, array $nodeNames)
    {
        $file2 = $this->file02;

        $acc = [];
        $result = $this->iterateToCheckNewFiles($node, $nodeNames, $file2, $acc);

        return $acc;
    }

    public function iterateToCheckNewFiles(
        array $node,
        array $nodeNames,
        $file2,
        array &$acc
    ) {

        if (!is_array($file2)) {
            return $file2;
        }

        $childs = array_map(function ($item) use ($node, $nodeNames, &$acc) {
            if ($this->treeService->isFile($item) || $this->treeService->isDirectory($item)) {
                if ($this->isNodesInTheSameFolder($node, $item)) {
                    $isFounded = false;
                    foreach ($nodeNames as $nodeName) {
                        if (
                            $item['name'] === $nodeName['name'] &&
                            $item['type'] === $nodeName['type']
                        ) {
                            $isFounded = true;
                        }
                    }
                    if (!$isFounded) {
                        $item['comparison'] = 'added';
                        $acc[] = $item;
                    }
                }
            }
            $res = $this->iterateToCheckNewFiles($node, $nodeNames, $item, $acc);
        }, $file2);
        return $childs;
    }

    public function isNodesInTheSameFolder($item1, $item2)
    {
        //item1 - корневая директория
        if (
            !( $this->treeService->isFile($item1) || $this->treeService->isDirectory($item1)) &&
            ( $this->treeService->isFile($item2) || $this->treeService->isDirectory($item2))
        ) {
            return false;
        }

        $path1 = $item1['path'];
        $path2 = $item2['path'];

        unset($path2[count($path2) - 1]);


        if ($path1 === $path2) {
            return true;
        }

        return false;
    }

    public function getAllNamesOfNode($node)
    {
        $result = [];
        //Чтобы проверить одинаковые имена у папки и файла - добавить тип
        if ($this->treeService->isDirectory($node)) {
            foreach ($node['childs'] as $item) {
                if ($this->treeService->isFile($item) || $this->treeService->isDirectory($item)) {
                    $result[] = [
                        'name' => $item['name'],
                        'type' => $item['type']
                        ];
                }
            }
        }
        return $result;
    }

    public function findDirectory($node, array &$acc, $file2 = null)
    {
        //ищем node директорию во втором файле

        if ($file2 === null) {
            $file2 = $this->file02;
        }

        //echo "Проверяем директорию\n";

        if (!is_array($file2)) {
            return $file2;
        }

        $childs = array_map(function ($item) use ($node, &$acc) {

            if ($this->treeService->isDirectory($item) || $this->treeService->isFile($item)) {
                $result = $this->treeService->isNodeFound($node, $item);

                if ($result === true) {
                    if ($node['type'] !== $item['type']) {
                        $acc['comparison'] = 'deleted';
                        $acc['updated'] = $item;
                    } else {
                        $acc['comparison'] = 'matched';
                    }
                }
            }
            return $this->findDirectory($node, $acc, $item);
        }, $file2);

        if ($acc) {
            return [];
        } else {
            return $childs;
        }
    }

    public function findFile($node, array &$acc, $file2 = null)
    {
        if ($file2 === null) {
            $file2 = $this->file02;
        }

        if (!is_array($file2)) {
            return $file2;
        }

        $childs = array_map(function ($item) use ($node, &$acc) {
        //**ПРОВЕРКА ДЛЯ ФАЙЛА-----------------------------------------------
            if ($this->treeService->isFile($item)) {
                  $result = $this->treeService->isNodeFound($node, $item);
                  //Если ключ и path совпадает

                if ($result === true) {
                    if ($item['value'] === $node['value']) {
                        $acc['comparison'] = 'matched';
                        //return $acc;
                    } else {
                        $acc['comparison'] = "changed";
                        $acc['newItem'] = $item;
                        //добавляем новому элементу статус
                        $acc['newItem']['comparison'] = 'added';
                    }
                } else {
                      //Если файлы не совпадают по ключу
                    return [];
                }
            } elseif ($acc) {
                //Если аккумулятор не пустой, то смысла в проверке далее нет.
                return [];
            }
                    //Если директория - просто обходим
            return $this->findFile($node, $acc, $item);
        }, $file2);

        return $childs;
    }


    public function makeNormalTree(array $file01)
    {
        $file = $this->sortDiff($file01);

        $fileTree = $this->iterateToMakeTree($file, ['root']);

        $result = [];
        //описание корневой директории
        $result ['name'] = 'root';
        $result ['type'] = 'directory';
        $result ['path'] = ['root'];
        $result ['childs'] = $fileTree;
        return $result;
    }

    public function iterateToMakeTree($file, $accPath)
    {
        if (!is_array($file)) {
            return $file;
        }

        $childs = array_map(function ($item, $key) use ($accPath) {
            $accPath[] = $key;
            $result = $this->iterateToMakeTree($item, $accPath);

            if (!is_array($result)) {
                $result = [
                    'type' => FileType::File->value,
                    'name' => $key,
                    'value' => $this->getNormalizeValue($item),
                    'path' => $accPath,
                    'comparison' => 'matched'
                    ];
            } elseif (is_array($result)) {
                $result = [
                    'type' => FileType::Directory->value,
                    'name' => $key,
                    'path' => $accPath,
                    'childs' => $result,
                    'comparison' => 'matched'
                ];
            }
            return $result;
        }, $file, array_keys($file));

        return $childs ;
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

    public function normalizeArray($node)
    {
        if (!is_array($node)) {
            return $this->getNormalizeValue($node);
        }

        $childs = array_map(function ($item) {
            return $this->normalizeArray($item);
        }, $node);

        return $childs;
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
