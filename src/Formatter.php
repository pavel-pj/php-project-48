<?php

namespace Differ\Formatter;

use Differ\Formatters\Stylish;
use Exception;

/**
 * @throws Exception
 */
function formatResult(array $diff, string $format): string
{
    return match ($format) {
        'stylish' => Stylish\formatResult($diff),
      //  'plain' => Plain\formatResult($diff),
       // 'json' => Json\formatResult($diff),
        default => throw new Exception("Unsupportable format: '{$format}'\n")
    };
}
