<?php

namespace Differ\Parsers;

use Exception;
use Symfony\Component\Yaml\Yaml;

function getData(string $filePath): array
{
    $data = getFileData($filePath);
    return $data;
}

function getFileData(string $filePath): array
{
    if (!file_exists($filePath)) {
        throw new  Exception("File not found: $filePath");
    }

    $result = file_get_contents($filePath);
    if ($result === false) {
         throw new  Exception("File could not be read: $filePath");
    }

    return parceFile($result, $filePath);
}

function parceFile(string $file, string $filePath): array
{
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    return match ($extension) {
        'json' => jsonParser($file, $filePath),
        'yaml' => yamlParser($file, $filePath),
        default => throw new Exception("Program could not work with file type :{$extension}"),
    };
}

function jsonParser(string $file, string $path): array
{
    return json_decode($file, true, JSON_PRETTY_PRINT);
}

function yamlParser(string $file, string $filePath): array
{
    try {
        return Yaml::parseFile($filePath);
    } catch (Exception $e) {
        throw new Exception("Cannot parse file {$filePath} : {$e->getMessage() }");
    }
}
