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


        if (array_key_exists('type', $node)) {
            if ($node['type'] === FileType::File->value) {
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

        if (
            array_key_exists('type', $node) &&
            $node['type'] === FileType::Directory->value
        ) {
            return true;
        }
        return false;
    }


    //Проверяем 2 узла по type,name,path.
    public function isNodeFound(array $item1, array $item2): bool
    {
        $result = false;

        if (
            $item1['name'] === $item2['name'] &&
            $item1['path'] === $item2['path']
        ) {
            $result = true;
        }
        return $result;
    }
}
