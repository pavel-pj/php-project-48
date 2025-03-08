<?php

namespace Hexlet\Code\Formaters;

use Hexlet\Code\FileType;
use Hexlet\Code\TreeService;
use Error;

class JsonFormat
{
    public TreeService $treeService;

    public function __construct()
    {
        $this->treeService = new TreeService();
    }

    public function formatJson(array $data)
    {
        $data = $data['childs'] ;
        $preparedData = $this->createList($data);

        $result = json_encode($preparedData, 100);
       //Удалить []
        $result = substr($result, 1, strlen($result) - 1);
        $result = substr($result, 0, strlen($result) - 1);

        return $result;
    }

    public function createList(array $data)
    {
        $result = $this->createListIterate($data);
        return  $result  ;
    }

    public function createListIterate($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $childs = array_map(function ($item) {

            $result = $this->createListIterate($item);

            if ($this->treeService->isFile($result)) {
                $prefix = $this->getPrefixByComparison($result['comparison']);

                return [$prefix . $result['name'] => $result['value']];
            }
            if ($this->treeService->isDirectory($result)) {
                $prefix = $this->getPrefixByComparison($result['comparison']);

                return [$prefix . $result['name'] => array_merge(...$result['childs'])];
            } else {
                return $result;
            }
        }, $data);
        return $childs;
    }

    public function getPrefixByComparison($comparison)
    {
        return match ($comparison) {
            'added' => '+ ',
            'deleted' => '- ',
            'matched' => '',
            default => '*ОШИБКА*',
        };
    }
}
