<?php

namespace Hexlet\Code\Formaters;

use Hexlet\Code\FileType;
use Hexlet\Code\TreeService;
use Error;

class DiffFormat
{
    public TreeService $treeService;

    public function __construct()
    {
        $this->treeService = new TreeService();
    }

    public function formatData(array $data)
    {
        $data = $data['childs'];
        $result = $this->createList($data);
        return $result;
    }

    public function createList(array $data): string
    {
        $info = $this->createListIterate($data, 0);
        $result = implode("\n", $info);

        return "{\n" . $result . "\n}";
    }

    public function createListIterate($data, int $level)
    {
        $symbol = " ";
        $leftIndent = str_repeat($symbol, 2);
        $indent = str_repeat($symbol, 2);

        if (!is_array($data)) {
            return $data;
        }

        $childs = array_map(function ($item) use ($level, $indent, $leftIndent) {
            $level += 1;
            $indent = str_repeat($indent, $level);
            $result = $this->createListIterate($item, $level);

            if ($this->treeService->isFile($result)) {
                $prefix = $this->getPrefixByComparison($result['comparison']);
                return $indent . $prefix . $result['name'] . ": " . $result['value'];
            }
            if ($this->treeService->isDirectory($result)) {
                $prefix = $this->getPrefixByComparison($result['comparison']);
                $files = implode("\n", $result['childs']);

                return $indent . $prefix . $result['name'] . ": {\n" . $files . "\n" . $indent . $leftIndent . "}";
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
            'matched' => '  ',
            default => '*ОШИБКА*',
        };
    }
}
