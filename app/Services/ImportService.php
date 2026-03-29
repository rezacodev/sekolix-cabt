<?php

namespace App\Services;

use App\Imports\QuestionsImport;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportService
{
    /**
     * Import user massal dari file Excel.
     * Mengembalikan array ['imported' => int, 'errors' => string[]]
     */
    public static function importUser(string $filePath): array
    {
        $import = new UsersImport();
        Excel::import($import, $filePath);

        return [
            'imported' => $import->imported,
            'errors'   => $import->errors,
        ];
    }

    /**
     * Import soal massal dari file Excel.
     * Mengembalikan array ['imported' => int, 'errors' => string[]]
     */
    public static function importSoal(string $filePath): array
    {
        $import = new QuestionsImport();
        Excel::import($import, $filePath);

        return [
            'imported' => $import->imported,
            'errors'   => $import->errors,
        ];
    }
}
