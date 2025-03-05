<?php

namespace Hexlet\Code;

use App\Http\Controllers\Auth\PasswordController;
use Docopt;
use Mockery\Exception;
use PHPUnit\Framework\Error;
use Symfony\Component\Yaml\Yaml;
use Hexlet\Code\newFormat;
use Illuminate\Support\Collection;
use Hexlet\Code\FileType;
use Hexlet\Code\TreeService2;

class newCli3
{
    public $params;
    public const NOT_EXIST = 'not_exists';
    public const FIRST_FILE = 'first_file';
    public const SECOND_FILE = 'second_file';

    public array $file01;
    public array $file02;

    public $checkedFile;

    public newFormat $formater;
    public TreeService2 $treeService;

    public function __construct(array|null $params = [])
    {
        $this->params = array_values($params);
        $this->treeService = new TreeService2();
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

      //  $result = $this->greatDiff($filesData[0], $filesData[1]);
        $this->file01 = $this->makeNormalTree($filesData[0]);
        $this->file02 = $this->makeNormalTree($filesData[1]);

      //  echo "ПЕРВЫЙ ФАЙЛ НАЧАЛО\n";
       // print_r($this->file01);

        $result = $this->diffTree();
        echo "ОКОНЧАТЕЛЬНЫЙ РЕЗУЛЬТАТ: \n";
          print_r($result);

       // $resultFlatten =  $this->flat($result);

        // print_r($resultFlatten);

        $this->formater->formatData($result);
    }


    public function diffTree( ){



       $result = $this->iterateToDiff($this->file01, true);
       $result = $this->normalizeArray($result);
       return $result;


    }

    public function iterateToDiff ($node , bool $isNeedToCheckPreviousNode) {

        //Выводим дифф, всё, что имеется + данные из 2 файла

        if(!is_array($node)){
            return $node;
        }

        $childs = array_map(function ($item) use ($isNeedToCheckPreviousNode) {


            $isNeedToCheck = $isNeedToCheckPreviousNode;

            //false - не проверяем
            if ($isNeedToCheckPreviousNode == true ) {
                //Проверяем Директории ( имя )
                $accDir = [];
                if ($this->treeService->isDirectory($item)) {


                    $result = $this->findDirectory($item, $accDir);
                    //Если директория не найдена, acc удет пустой
                    if (!$accDir) {
                        $item['comparison'] = "deleted";
                    } else {
                        $item['comparison'] = $accDir['comparison'];
                    }

                    if ($item['comparison'] === "deleted") {
                        $isNeedToCheck = false;
                    } else {
                        $isNeedToCheck = true;
                    }


                }
            }


            return $this->iterateToDiff($item, $isNeedToCheck);


        },$node);




        //Проверяем со вторым файлом.

        //Сюда заносим новые/имзененные узлы, которые нужно добавить в корень
        $addedNodes = [];
        foreach($childs as &$item) {

            $acc = [];
            //Проверяем только файлы
            if($this->treeService->isFile($item)) {

                $result = $this->findFile($item, $acc);
               // $item['comparison'] = $acc;
                if (array_key_exists('comparison',$acc)){

                    $item['comparison'] = $acc['comparison'];
                    //Для имзененных. added добавлено ниже, в корневую директорию
                    if ($acc['comparison'] === 'changed' ) {
                        $item['comparison'] = 'deleted';
                        $addedNodes [] = $acc['newItem'];
                    }

                }
                else {
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
       $nodeNames = $this->getAllNamesOfNode ($node);



       $newNodes= $this->newNodesFrom2File($node,$nodeNames);
        //$node['childs'][] = "fasdfasdf";
        foreach($addedNodes as $addedNode) {
             $childs[] = $addedNode;
        }

        foreach($newNodes as $newNode) {
          $childs['childs'][] = $newNode;
        }
  

        return $childs;

    }

    public function newNodesFrom2File(array $node, array $nodeNames){



       //echo "ПРИШЛО NODE\n";
       // print_r($node);

        $file2 =$this->file02;
        $acc = [];
        $result = $this->iterateToCheckNewFiles($node,$nodeNames,$file2, $acc );
        return $acc;

    }

    public function iterateToCheckNewFiles(
        array $node,
        array $nodeNames,
        $file2,
        array &$acc)
    {


        if (!is_array($file2)){
            return $file2;
        }

        //echo "МЫ В ИереатCHECK\n";

        $childs = array_map (function ($item) use($node,$nodeNames,&$acc){

            if ($this->treeService->isFile($item) || $this->treeService->isDirectory($item)    ) {

                if ($this->isNodesInTheSameFolder($node,$item)) {

                    if (!in_array($item['name'], $nodeNames)) {

                        $item['comparison'] = 'added';
                        $acc[] = $item;

                    }
                }


            }


            $res = $this->iterateToCheckNewFiles($node,$nodeNames,$item,$acc);



        },$file2);

        return $childs;

    }

    public function isNodesInTheSameFolder($item1,$item2) {

        //item1 - корневая директория

        if (!
            ( $this->treeService->isFile($item1) || $this->treeService->isDirectory($item1)) &&
            ( $this->treeService->isFile($item2) || $this->treeService->isDirectory($item2))
        ) return false;


        $path1 = $item1['path'];
        $path2 = $item2['path'];

        unset($path2[count($path2) - 1]);

        if ($path1 === $path2) {
            return true;
        }

        return false;
    }

    public function getAllNamesOfNode ($node){

        $result = [];

        if ($this->treeService->isDirectory($node)) {
            foreach ($node['childs'] as $item) {
                if ($this->treeService->isFile($item) || $this->treeService->isDirectory($item)) {
                    $result[] = $item['name'];
                }
            }
        }
       //echo "Все name папки:\n";
      //   print_r($result);
        return $result;

    }

    public function findDirectory($node,array &$acc, $file2 = null )  {

        if ($file2 === null) {
            $file2 =$this->file02;
        }

        echo "Проверяем директорию\n";

        if (!is_array($file2)) {
            return $file2;
        }

        $childs = array_map(function ($item) use ($node, &$acc) {


            if ($this->treeService->isDirectory($item)) {

                $result = $this->is_node_found($node,$item);
/*
                echo "результат проверки ДИРЕКТОРИИ \n";
                echo "имя проверяемой папки : ". $node['name'] ."\n";
                echo "результат :\n";
                echo $result;
                if ($result) {
                    echo "TRUE\n";
                } else {
                    echo "FALSE\n";
                }
*/
                if ($result === true) {
                    $acc['comparison'] = 'matched';
                } else {
                    $acc['comparison'] = 'deleted';
                }

            }

        }, $file2);

        if ($acc) {
            return [];

        } else {
            return $childs;
        }


    }

    public function findFile($node,array &$acc, $file2 = null )  {

       // echo "В ФУНКЦИИ FINDFILE\n";


        if ($file2 === null) {
            $file2 =$this->file02;
        }


        if (!is_array($file2)) {
            return $file2;
        }

        $childs = array_map(function ($item) use ($node,&$acc ){

          //   echo "************ В arrayMAP FindFile\n";
          //  echo "ITEM = \n";
          //  print_r($item);
          // echo "\n node = \n";
         //  print_r($node);

        //**ПРОВЕРКА ДЛЯ ФАЙЛА-----------------------------------------------
                if ($this->treeService->isFile($item)) {
                      $result = $this->is_node_found($node,$item);
                      //Если ключ и path совпадает

              //      echo "ПРОВЕРКА ФАЙЛОВ\n";
                //    echo "node :\n";
                 //   print_r($node);
                 //   echo "сейчас проверяем :\n";
                  //  print_r($item);



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
                      }
                      else {
                          //Если файлы не совпадают по ключу
                          return [];
                      }

                } elseif ($acc) {
                    //Если аккумулятор не пустой, то смысла в проверке далее нет.
                    return [];
                } //elseif ($this->treeService->isDirectory($item)) {
                    //Если директория - просто обходим
                   return $this->findFile($node, $acc, $item);

                //}
                //return [];

        },$file2);

        return $childs;



    }

    //Проверяем 2 узла по type,name,path.
    public function is_node_found(array $item1, array $item2): bool
    {

      //  echo "ФУНКЦИЯ IS_NODE_FOUND\n";
     //  echo "item1 :\n";
     // print_r($item1);
     //  echo "item2 :\n";
     //  print_r($item2);

      //  Echo "Начало проверки узла\n";
        $result = false;

        if ($item1['name'] === $item2['name'] &&
            $item1['path'] === $item2['path'] &&
            $item1['type'] === $item2['type']) {
            $result = true;
        }
     //    if ($item1['name'] === $item2['name'] ){echo "ИМЕНА СОВПАДАЮТ\n";}

       // if ($result ) {
       ///     echo "РЕЗУЛЬТАТ : TRUE\n";
       // } else {echo "РЕЗУЛЬТАТ : false\n";}

       // Echo "Выполнена проверка файлов:\n";
      //  echo "Результат:\n";
       // if ($result) {echo "TRUE\n";} else {echo "FALSE\n";}
        return $result;

    }




    public function makeNormalTree(array $file01)
    {
        $file = $this->sortDiff($file01);
       // echo "Оригиналльный файл json\n";
        //print_r($file01);



        $fileTree = $this->iterateToMakeTree($file,['root']) ;

        $result = [];
        //описание корневой директории
        $result ['name'] = 'root';
        $result ['type'] = 'directory';
        $result ['path'] = ['root'];
        $result ['childs'] = $fileTree;



        return $result;

    }

    public function iterateToMakeTree($file, $accPath ) {

        if(!is_array($file)){
           // echo "попали в файл\n";
           // echo $file ."\n";
          //  return ['name'=>'file','meta'=>'INFO'];
            return $file;
        }

        $childs = array_map(function ($item, $key) use ($accPath) {
                $accPath[] = $key;
                $result =  $this->iterateToMakeTree($item, $accPath);

                if (!is_array($result)) {
                   // echo "это файл:\n";
                   //echo "ключ: {$key}, значение : {$result}\n\n";
                    $result = [
                        'type'=>FileType::File->value,
                        'name' => $key,
                        'value' => $item,
                        'path' => $accPath,
                        'comparison' => 'matched'

                        ];

                } elseif(is_array($result)){

                    $result = [
                        'type'=> FileType::Directory->value,
                        'name' => $key,
                        'path' => $accPath,
                        'childs' => $result,
                        'comparison' => 'matched'
                    ];
                }



                return $result;

        },$file , array_keys($file));

        return  $childs ;
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

    public function normalizeArray($node){

        if(!is_array($node)){
            return $this->getNormalizeValue($node);
        }

        $childs = array_map(function ($item){
            return $this->normalizeArray($item);
        },$node);

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

    /*
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
*/


    /*

        public function getTypeOfComparingFileName(
            string $fileName01,
            bool $isNodeInOtherFile,
            bool $isPreviousFolderExists,
            array $accPath = [])
        {



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

    */

    /*
    public function iterate2File (array $item, $node) {
        //$item = из первого файла Array, неизменяемая часть
        //$node - изменяется


        if (!is_array($node)){
            return $node;

        }


            $childs = array_map (function ($elem) use ($item) {

                if ($this->treeService->isFile($elem)) {

                    $result = $this->is_node_found($item,$elem);


                    //Если ключ найден, проверяем значение :
                    if ($result) {
                        if ($elem['value'] === $item['value']){
                            return "matched";
                        }
                        else {
                            return "deleted";
                        }
                    }



                }

                $res = $this->iterate2File( $item ,$elem);
                return $res;


            }, $node );

            $matched = array_filter($childs, function ($element) {
                return $element === 'matched';
            });
            if ($matched) return "matched";

            $deleted = array_filter($childs, function ($element) {
                return $element === 'deleted';
            });
            if ($deleted) return "deleted";

            return $childs;


    }*/


}
