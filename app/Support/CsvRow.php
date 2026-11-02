<?php

namespace App\Support;

class CsvRow
{
    /** Keep user-controlled strings from executing as spreadsheet formulas. */
    public static function safe(array $cells): array
    {
        return array_map(function ($cell) {
            if (is_string($cell) && ! is_numeric($cell) && preg_match('/^[\s]*[=+@\-]/u', $cell)) {
                return "'".$cell;
            }

            return $cell;
        }, $cells);
    }
}
