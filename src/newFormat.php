<?php

namespace Hexlet\Code;

use Hexlet\Code\newCli;
use Hexlet\Code\FileType;
use Hexlet\Code\TreeService;
use Error;

class newFormat
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
        echo "***********FORMAT*************\n";
        //$result = $this->iterateFormat($data);
        $data = $data['childs'];
        $result= $this->flat($data);
        print_r( $result);

        //$res = [];
        //$result = $this->createListFlat($data,$res);
        //print_r($res );


    }
    public function flat(array $node)
    {

        $list= [];
        $result = $this->flatIterate($node, $list);
        return $list ;
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


/*
    public function createList($data,&$acc)
    {

        if (!is_array($data)) {
            return $data;
        }

        $childs = array_map(function ($item ) use (&$acc){

            return $this->createList($item, $acc);
        }, $data);


        if ($this->treeService->isDirectory($data)) {
            echo "Мы в директории\n";
            $acc[] = "HOOO";
            foreach ($childs as $child) {

                if(is_array($child[0])){

                    $acc [] = $child[0]['name'] ;
                }


                echo "В цикле\n";
                if ( $this->treeService->isFile($child)) {
                    echo "мы в файле\n";
                    $acc [] = $child ;
               // $format[] = $child['name'];
                } else if ( $this->treeService->isDirectory($child)) {
                    $acc [] = $child['name'];
                    // $format[] = $child['name'];
                }
            }
        }

        return $childs;


    }
    /*

    public function getPrefixByComparison($comparison)
    {

        return match ($comparison) {
            'added' => '+ ',
            'deleted' => '- ',
            'matched' => '  ',
            default => '*ОШИБКА*',

        };


    }




/*
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
        $rows = explode('|', $item);
        $newItem = implode("\n{$indent}", $rows);

        //Пустые значения, появившиеся после слияния второго файла

        if (!$item) {
            return ['root' => []];
        }

        $value = $indent . $newItem;
        for ($i = count($path) - 1; $i >= 0; $i--) {
            $value = array($path[$i] => $value);
        }

        return $value;
    }

    public function normalize2($data)
    {

        //Одинаковые ключи из разных файлов будут дважды добавлены, удаляем эти дубликаты
        $uniqueData = array_unique($data,SORT_REGULAR );
        echo "ПОсле удаления дубликатов\n";
        print_r($uniqueData);


        $result = [];

        foreach ( $uniqueData as $item) {
            $result = array_merge_recursive($result,$this->createFold2($item));
        }

        print_r($result);

        $prefixFolderData = $this->makePrefixToFolder($result);
         echo "С ПРЕФИКОМ\n";
         print_r($prefixFolderData);



        return $prefixFolderData;
     }

    public function makePrefixToFolder(array $data){

        //cписок папок
        $folders = array_filter($data, function ($item) {
            return $item['type'] === FileType::Directory->value;
        });

        $result = $this->iteraterFolderToMakePrefix($data,['root'],$folders);
        return $result;

    }

    public function iteraterFolderToMakePrefix( $node,array $accPath, array $list ) {



        if (!is_array($node)){
            return $node;
        }

        $childs = array_map(function ($item,$key) use($list, $accPath) {
            //echo "childs\n";
            $accPath[] = $key;
            return $this->iteraterFolderToMakePrefix($item, $accPath, $list);

        }, $node, array_keys($node));

        //ВСЕ папки должны быть в списке априори.

        echo "ПАПКА key:". $accPath[count($accPath)-1];
        print_r($childs);
        echo "PATH :\n";
        print_r($accPath);
        return $childs;
      //  return [$accPath[count($accPath)-1]=> $childs];


    }

     /*
     public function  makePrefixToFolder (array $data) {

        $folders = array_filter($data, function ($item) {

            return $item['type'] === FileType::Directory->value;
        });

        echo "ПАПКИ :\n";
        print_r($folders);


        $newData = $data;


        foreach ($folders as $folder) {
            foreach ( $newData as $item) {

                $folderPath = $folder['path'];
                $itemPath = $item['path'];

                echo "проверяем FolderPath:\n";
                print_r($folderPath);
                echo "ItemPath:\n";
                print_r($itemPath);

                //Проверка
                $iter = 0;
                $folderPathCount = count($folderPath);
                $toCheck = true;
                while ($iter < $folderPathCount ) {
                    echo "iter = {$iter}\n";
                    if ($iter === count($itemPath)) {
                        echo "iter = count(itemPath) BREAK\n";
                        $toCheck = false;
                        break;
                    }
                    if ($folderPath[$iter] !== $itemPath[$iter]) {
                        echo "folderPath[iter] = ".$folderPath[$iter]." !== itemPath[iter]".$itemPath[$iter] ."\n";
                        echo "BREAK\n";
                        $toCheck = false;
                        break;
                    }
                    $iter += 1;
                 }

                 if ($toCheck === true) {
                     echo "YES\n";
                 }
                 else {
                     echo "NO\n";
                 }
                //обновляем путь с учетом comparison
                 if($toCheck) {
                     echo "count(folderPath) = ".count($folderPath) ."\n";
                     $key = $this->getPrefixByComparison($item['comparison']) . $item['path'][count($item['path']) - 1];
                     $item['path'][count($item['path']) - 1] =   $key;

                     echo "item :\n";
                     print_r($item);

                 }



            }
        }
        echo "\n=============================================НОВАЯ ДАТААААА\n";
        print_r($newData);

        return $newData;


     }



    public function createFold2($item)
    {
        //Стандартный отступ - 2

        $value = $item['value'];
        $type = $item['type'];
        $comparison = $item['comparison'];
        $path = $item['path'];
        $key = $path[count($path) - 1];

        // echo "\nkey : {$key} , value : {$value} \n";
        // echo "comparison : {$comparison} \n";
        //echo "path ДО:\n";
        //print_r($path);


        //Префикс
        if ($type === FileType::File->value) {
            $keyPrefix = $this->getPrefixByComparison($comparison) . $key;
            $path[count($path) - 1] = $keyPrefix;
            // echo "Prefix: {$keyPrefix}\n\n";
        }



        //Пустые значения, появившиеся после слияния второго файла
        //ВАЖНО !
        if (!$value) {
            return ['root' => []];
        }

        //Стандартный отступ - 2
        $symbol = str_repeat(self::SYMBOL, self::FILE_INDENT);
        $indent = str_repeat($symbol, count($path));
        if ($type === FileType::Directory->value) {
            $symbol = str_repeat(self::SYMBOL, self::FOLDER_INDENT);
            //4 * вложенность - 2
            $indent = str_repeat($symbol, count($path) - 1);
            $indent = substr($indent, 0, strlen($indent) - 2);

        }


        $value = $indent . $value;
        for ($i = count($path) - 1; $i >= 0; $i--) {
            $value = array($path[$i] => $value);
        }

       // echo "РЕЗУЛЬТАТ CreateFold2\n";
        //p/rint_r($value);

        return $value;
    }


    public function getPrefixByComparison($comparison)
    {

        return match ($comparison) {
            'added' => '+ ',
            'deleted' => '- ',
            'matched' => '  ',
            default => '*ОШИБКА*',

        };


    }



     public function addPrefixToFolders(array $result, array $data){

        //$result - массив после merge, но папки без префикса
        //$data - плоский массив всех узлов и директорий , где path - массив вложенности, и comparison - тип prefix

        $newResult = $result;

        foreach ($data as $item) {
            //Выбираем только папки
            if($item['type'] === FileType::Directory->value){

                //Без проверки на наличие ключа. Массивы 100% должны совпадать.
                //заменить имя папки

                echo "В ЦИКЛЕ +++++++++++++++++++\n";
                echo "Входящий массив :\n";
                print_r($newResult);

                $newResult = $this->changeFolderName ($newResult,$item );

                echo "В ЦИКЛЕ +++++++++++++++++++\n";
                echo "ПОЛУЧЕННЫЙ массив :\n";
                print_r($newResult);


            }
        }

        return $newResult;


     }

     public function changeFolderName (array $mainNode, $item ) {

        //перейти к нужной директории

        $path = $item['path'];
        $comparison = $item['comparison'];

        // echo "\n\n++++++++++++++++++++++++++++++\nНачало программы\n";
        // echo "ЭЛЕМЕНТ :\n";
        // print_r($itemData);

        // echo "Начальный mainNode\n";
        // print_r($mainNode);
        // echo "*****Начало цикла\n\n";

         //
        // echo "Получаем ссылку на папку :\n";
        // $path = $itemData;

         echo "PATH: \n";
         print_r($path);
         [$node, $copy] = $this->deleteNode($mainNode, $path  );
         echo("ПОЛУЧЕННЫЙ МАССИВ = \n");
         print_r($node );

         echo "ПОЛУЧЕННАЯ КОПИЯ\n";
         print_r($copy);
           $key =  $path[count($path)-1];
           echo "key = {$key}\n";
          $node[ $key] = $copy;
          $node['ВАся'] = 'oofofof';

         echo "НОВАЯ NODE = \n";
         print_r($node);


         return $node;

         //echo "Ключ, по которому делается копия :\n";
         //echo $itemData[count($itemData)-1] . "\n";
         //$copy = $link[$itemData[count($itemData) -1 ]];

         ///echo "Сделанная копия :\n";
         // print_r($copy);
        // echo "ОСНОВНОЙ МАССИВ ДО ОПЕРАЦИИ УДАЛЕНИЯ-ОБНОВЛЕНИЯ: \n";
        // print_r($mainNode);

       //  $key = $itemData[count($itemData)-1];
       //  echo "ключ : {$key}\n";

         //unset($link[$itemData[count($itemData)-1]]);
         // $link = "NNN";
        // echo "УДАЛИЛИ\n";
       //  echo "Массив после удаления\n";
       //  print_r($mainNode);
       //  echo "ссылка посое удаления : \n";
       //  print_r($link);

        // echo "\n";
       //  $newKey = "новый КЛюч : ". $itemData[count($itemData)-1];
       //  echo "Новый ключ : {$newKey}";

        // $link[$newKey] = $copy;
        // $link['HELLO'] = 2400;

        // echo "++++++++++++++Массив после обновлнеия \n";
      //   print_r($mainNode);


     }

     public function deleteNode (array $result, array $path){

        echo "вошли в GET NODE LINK\n";
        echo "result :\n";
       print_r($result);
         echo "path:\n";
        print_r($path);


        if (count($path) > 1) {
            $node = $result[$path[0]];
            array_shift($path);


           // echo "ПРОВЕРЯЕМ ССЫЛКУ ВНУТРИ :\n";
            //$link = "ЗЗЗ";

           // echo "Новый PATH:\n";
           // print_r($result);

            if (count($path) === 1) {

                ECho "УЖЕ ТУТ :\n";
                echo " node :\n";
                print_r($node);
                echo "path :\n";
                print_r($path);

               // $link = "PPP";
                //echo "Значение Итогогова массива :\n";
                //print_r($result);
               // echo "\n\n";
                $copy = $node[$path[0]];

                echo "КОПИЯ :\n";
                print_r( $copy);

                unset($node[$path[0]]);

                echo "node : \n";
                print_r($node);

                $arr =  [$node , $copy];
                return $arr;
            }
            $newLink = $this->getNodeLink($node, $path);

           // echo " RESULT NEW ЛИНК(если ненулевой path после вызова самой себя\n";
           // print_r($newLink);

           // $res = $newLink;
            return $newLink;
        }

        //echo "ЕСЛИ PATH = 0 :\n";
        //print_r($result);
        return ['ТАКОГО НЕ БЫВАЕТ'];

     }

    public function getNewResult (array $result, array $path){

        //echo "вошли в GET NODE LINK\n";
        // echo "result :\n";
        // print_r($result);
        // echo "path:\n";
        // print_r($path);


        if ($path>1) {
            $link = &$result[$path[0]];
            array_shift($path);
            //  echo "Получили ссылку :\n";
            //  print_r($link);

            // echo "ПРОВЕРЯЕМ ССЫЛКУ ВНУТРИ :\n";
            //$link = "ЗЗЗ";

            // echo "Новый PATH:\n";
            // print_r($result);



            if (count($path) === 1) {

                //ECho "ВОЗВРАЩАЕМ ССЫЛКУ :\n";
                // $link = "PPP";
                //echo "Значение Итогогова массива :\n";
                //print_r($result);
                // echo "\n\n";

                return $link;
            }
            $newLink = &$this->getNodeLink($link, $path);

            // echo " RESULT NEW ЛИНК(если ненулевой path после вызова самой себя\n";
            // print_r($newLink);

            // $res = $newLink;
            return $newLink;
        }

        echo "ЕСЛИ PATH = 0 :\n";
        print_r($result);
        return $result;

    }




/*
public function createFold3($item, &$result)
{
    //Стандартный отступ - 2

    $value = $item['value'];
    $type = $item['type'];
    $comparison = $item['comparison'];
    $path = $item['path'];
    $key = $path[count($path) - 1];

    //Префикс
   // $keyPrefix = $this->getPrefixByComparison($comparison) . $key;
   // $path[count($path) - 1] = $keyPrefix;
    // echo "Prefix: {$keyPrefix}\n\n";



    //$value = $indent . $value;
    //Проверяем, если уже есть такое узел или папка, то не создаём
    $mainArray = $result;
    echo "\n***********************************\nНачинаем проверку path :\n";
    //print_r($path);
    //$this->cli->isNodeInOtherFile($path,$result);

    $isExits = $this->treeService->isNodeInOtherFile( $path,$type, $result );

    if ($isExits) {
        echo "Есть key: {$key} ";
     } else {
        echo "Нет ключа key: {$key}" ."\n";
        $link = &$result;
         for ($i = 0; $i <= count($path) -1 ; $i++) {

             //Если это не последний элемент, то создаём массив
             if($i < count($path)-1 ){
                 echo "i={$i}\n";
                 echo "result :\n";
                 print_r($result);
                 $link[$path[$i]] =[];
                 echo "Создали новый array. Result = \n";
                 print_r($result);
                 echo "LInk до =\n";
                 print_r($link);

                 $link = &$link[$path[$i]];
                 echo "LInk ПОСЛЕ =\n";
                 print_r($link);

             } else {
                 echo "Присваиваем значение. Link =\n";
                 print_r($link);
                 $link[$path[$i]] = $value;
                 echo "Присваиваем значение. Result=\n";
                 print_r($result);
             }

         }

    }


    echo "Перед самым выходом\n";
    print_r($result);


}
*/




    /*
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


            //Проверка на правильный результат
            if ($iterator % self::FOLDER_INDENT !== 0) {
                throw new Error("Ошибка. Неправильный расчет закрывающей скобки.\n
                 folderName =|{$folderName}|=\n
                 отступ = {$iterator}\n");
            }

            return str_repeat(self::SYMBOL, $iterator);
        }
    */



}

