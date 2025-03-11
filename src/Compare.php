<?php

namespace Differ\Compare;


function putDiffMark(mixed $key, mixed $value, int $mark, bool $isUpdated = null, mixed $newValue = null) {

    return ['key'=>$key, 'value'=>$value, 'mark' => $mark, 'isUpdated'=>$isUpdated, 'newValue'=>$newValue];

}

function compareTrees (array $file1, array $file2 ) {

    $keys = array_unique(array_merge(array_keys($file1),array_keys($file2)));
    $keysSorted = sort($keys);


    $result = array_reduce($keys, function($carry, $key) use ($file1, $file2) {

        $keyExist1 = key_exists($key, $file1);
        $keyExist2 = key_exists($key, $file2);

        if ($keyExist1 && !$keyExist2) {
            return [...$carry , putDiffMark($key, $file1[$key], -1)];

        }
        if (!$keyExist1 && !$keyExist2) {
            return [...$carry , putDiffMark($key, $file2[$key], 1)];

        }

        //both exist
        $value1 = $file1[$key];
        $value2 = $file2[$key];
        //if keys equals go deeper
        if (is_array($value1) && !array_is_list($value1) && is_array($value2) && !array_is_list($value2)) {
            return [...$carry, putDiffMark($key, compareTrees($value1, $value2 ), 0)];
        }


        //if list - compare values, but maybe need to put mark to children
        //if scalar - compare values
        if ($value1 === $value2) {
            return [...$carry, putDiffMark($key, $value1, 0)];
        }


        $deleted = putDiffMark($key, $value1, -1, true, $value2);
        $added = putDiffMark($key, $value2, 1, false);
        return [...$carry, $deleted, $added];



    }, []);

    return $result;

}