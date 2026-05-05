<?php

namespace App\Exports;

use App\Exports\Sheets\QuestionsDataSheet;
use App\Exports\Sheets\QuestionsInstructionSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuestionsImportTemplate implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new QuestionsDataSheet(),
            new QuestionsInstructionSheet(),
        ];
    }
}
