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
}
