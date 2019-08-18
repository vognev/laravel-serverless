<?php

namespace Laravel\Serverless;

class VarMerger
{
    public static function merge(array &$array, string $name, $value) : void
    {
        $nameParts = preg_split('/\\]\\[|\\[/s', $name);
        $current = &$array;
        foreach ($nameParts as $namePart) {
            $namePart = trim($namePart, ']');
            if ($namePart === '') {
                $current[] = [];
                $keys = array_keys($current);
                $lastKey = array_pop($keys);
                $current = &$current[$lastKey];
            } else {
                if (! isset($current[$namePart])) {
                    $current[$namePart] = [];
                }
                $current = &$current[$namePart];
            }
        }
        $current = $value;
    }
}
