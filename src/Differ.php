<?php

namespace Differ\Differ;

use function Differ\Parsers\getData;

const DEFAULT_FORMAT = "main";

function genDiff(string $filePath1, string $filePath2, string $format = DEFAULT_FORMAT)
{
    $file1 = getData($filePath1);
    return $file1;
}
