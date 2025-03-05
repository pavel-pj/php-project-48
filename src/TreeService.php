<?php

namespace Hexlet\Code;

use Hexlet\Code\FileType;

class TreeService
{
    public function isFile($node): bool
    {
        if (is_array($node) && array_key_exists('type', $node)) {
            if ($node['type'] === FileType::File->value) {
                return true;
            }
        }
        return false;
    }
    public function isDirectory($node): bool
    {
        if (is_array($node) && array_key_exists('type', $node)) {
            if ($node['type'] === FileType::Directory->value) {
                return true;
            }
        }
        return false;
    }

    //*****************************************************************
    //Сделать type = обязательным значением
    public function isNodeInOtherFile(array $path, $fileType, array $result  )
    {
        if (!$result) {
            return false;
        }
        //$result = файл, в котором проверять вхождение текущего узла/директории

        //path  - массив со списками ключей
        //Для корневой директории $path = root
        $normPath = array_filter($path, function ($item) {
            return $item !== 'root';
        });

        if (!empty($normPath)) {
            foreach ($normPath as $key) {
                //Нашли ключ

                if (!is_array($result)) {
                    return false;
                }
                if (array_key_exists($key, $result)) {

                    $result = $result[$key];
                } else {
                    return false;
                }

            }
        }

        //Типы должны соппадать
        if ((is_array($result) && $fileType == FileType::Directory->value) ||
            (!is_array($result) && $fileType == FileType::File->value))
        {
            return true;
        }  else {
            return false;
        }

    }
}
