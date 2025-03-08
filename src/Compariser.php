<?php

namespace Hexlet\Code;

class Compariser
{
    public function copmareArrays(): void
    {
        $ar = ['abba' => 1,'plot' => 2];
        $val = key_exists('abba', $ar);
        if ($val) {
            echo "ЕСТЬ\n";
        } else {
            echo "NO\n";
        }
    }
}
