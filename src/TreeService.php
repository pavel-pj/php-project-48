<?php

namespace Hexlet\Code;

use Hexlet\Code\FileType;

class TreeService
{
    public function isFile($node): bool
    {
       if (!is_array($node)) {
           return false;
       }
        /*
       echo "\n*********ПРОВЕРКА ФАЙЛА\n";
       echo "Входящий узел:\n";
       if (is_array($node)) {
           echo "узел - массив\n";
           print_r($node);
       } else {
           echo "Узел НЕ МАССИВ\n";
           echo $node;
       }
        */

       if (array_key_exists('type',$node)) {
           /*
            echo "Это ВСЕ ТАКИ УЗЕЛ, ЕСТЬ type\n";
            echo "сам узел\n";
            print_r($node);
           echo "значение node Type = \n";
            echo $node['type']."\n";
          // echo "сравниваеммое :\n";
          // echo FileType::File->value ."\n";*/
           if ($node['type'] === FileType::File->value) {
              // echo "ДА, ЭТО ФАЙЛ!\n";
               return true;
           }
       }

       return false;
    }
    public function isDirectory($node): bool
    {
        if (!is_array($node)) {
            return false;
        }

        if (array_key_exists('type',$node) &&
            $node['type'] === FileType::Directory->value) {
                return true;
         }

        return false;
    }


}
