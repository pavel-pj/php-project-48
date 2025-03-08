<?php

namespace Hexlet\Code\Formaters;

use Hexlet\Code\FileType;
use Hexlet\Code\Enums\States;
use Hexlet\Code\TreeService;
use Error;

class PlainFormat
{
    public TreeService $treeService;

    public function __construct()
    {
        $this->treeService = new TreeService();
    }

    public function formatPlain(array $data)
    {
        $flatArray = $this->flat($data['childs']);
        $result = $this->plainDiff($flatArray);
        $resultPrint = implode("\n", $result);

        echo "WE ARE HERE\n";
        print_r($result);

        return $resultPrint;
    }

    public function plainDiff(array $data)
    {
        $result = [];

        for ($i = 0; $i < count($data); $i++) {
            $state = $data[$i]['comparison'];
            $name = $data[$i]['name'];
            $path = $this->getPath($data[$i]['path']);
            $diff = '';

            if ($state === 'deleted') {
                //Если файл обновлен
                if (array_key_exists('updated', $data[$i])) {
                    if ($data[$i]['updated'] === 'complex value') {
                    } else {
                        $value = $data[$i]['value'];
                        $val1 = $this->getValue($value);
                        $value2 = $data[$i + 1]['value'];
                        $val2 = $this->getValue($value2);

                        if ($this->treeService->isDirectory($data[$i])) {
                            $val1 = '[complex value]';
                        }

                        $diff = "Property '" . $path . "' was updated. From " . $val1 . " to " . $val2;
                        $i += 1;
                    }
                } else {
                    if ($this->treeService->isNodeFound($data[$i], $data[$i + 1])) {
                        $value1 = $data[$i]['value'];
                        $val1 = $this->getValue($value1);
                        $value2 = $data[$i + 1]['value'];
                        $val2 = $this->getValue($value2);

                        $diff = "Property '" . $path . "' was updated. From: " . $val1 . " to " . $val2 ;
                        $i += 1;
                    } else {
                        //Простое удаление
                        $diff = "Property '" . $path . "' was removed";
                    }
                }
                //Если следующий файл - это замена текущего
            } elseif ($state === 'added') {
                if ($this->treeService->isFile($data[$i])) {
                    $value = $data[$i]['value'];
                    $val = $this->getValue($value);

                    $diff = "Property '" . $path . "' was added with value: " . $val;
                } elseif ($this->treeService->isDirectory($data[$i])) {
                    $diff = "Property '" . $path . "' was added with value: [complex value]";
                }
            }

            if ($diff) {
                $result[] = $diff;
            }
        }
        return $result;
    }

    public function getValue($str)
    {
        if (
            $str !== 'false' and
            $str !== 'true' and
            $str !== 'null'
        ) {
            return "'{$str}'";
        }
        return $str;
    }

    public function getPath(array $data)
    {
        unset($data[0]);
        return implode('.', $data);
    }

    public function flat(array $node)
    {
        //  выравнивание в одну плоскость
        $format = [];
        $res = $this->flatIterate($node, $format);

        $noMatched = array_filter($format, function ($item) {
                return $item['comparison'] !== 'matched';
        });

        $result = array_values($noMatched);

        return  $result;
    }

    public function flatIterate($node, &$acc)
    {
        if (!is_array($node)) {
            return $node;
        }

        $childs = array_map(function ($item) use (&$acc) {
            return $this->flatIterate($item, $acc);
        }, $node);

        if (
            $this->treeService->isFile($node)
            or $this->treeService->isDirectory($node)
        ) {
            $acc[] = [
                'name' => $node['name'],
                'path' => $node['path'],
                'type' => $node['type'],
                'comparison' => $node['comparison']
            ];

            if (array_key_exists('updated', $node)) {
                //Если не масисв, то возвращаем значение

                if ($this->treeService->isFile($node['updated'])) {
                    $acc[count($acc) - 1]['updated'] = $node['updated']['value'];
                } else {
                    $acc[count($acc) - 1]['updated'] = 'complex value';
                }
            }
        }
        if ($this->treeService->isFile($node)) {
            $acc[count($acc) - 1]['value'] = $node['value'];
        }
        return $childs;
    }
}
