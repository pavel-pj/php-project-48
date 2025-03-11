<?php

namespace Differ\Differ;

use function Differ\Parsers\getData;
use function Differ\Compare\compareTrees;
use function Differ\Formatter\formatResult;

const DEFAULT_FORMAT = "main";

function genDiff(string $filePath1, string $filePath2, string $format = DEFAULT_FORMAT)
{
    $file1 = getData($filePath1);
    $file2 = getData($filePath2);

    $result = compareTrees($file1,$file2 );
    return formatResult($result, $format);



}
