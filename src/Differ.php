<?php

namespace Differ\Differ;

use function Differ\Parsers\getData;

const DEFAULT_FORMAT = "main";

function genDiff(string $filePath1, string $filePath2, $format = self::DEFAULT_FORMAT)
{
    $file1 = getData($filePath1);
    print_r($file1);
}
