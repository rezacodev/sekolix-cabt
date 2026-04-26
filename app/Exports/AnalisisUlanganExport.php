<?php

namespace App\Exports;

use App\Exports\Sheets\AnalisisUlanganSheet;
use App\Exports\Sheets\HasilAnalisisSheet;
use App\Exports\Sheets\ProgramPerbaikanSheet;
use App\Exports\Sheets\ProgramPengayaanSheet;
use App\Models\ExamSession;
use App\Services\AnalisisService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AnalisisUlanganExport implements WithMultipleSheets
{
    private array $analisisData;
    private array $hasilData;
    private array $pengayaanData;

    public function __construct(ExamSession $session, AnalisisService $service)
    {
        $this->analisisData  = $service->analisisUlangan($session->id);
        $this->hasilData     = $service->hasilAnalisis($this->analisisData);
        $this->pengayaanData = $service->programPengayaan($this->analisisData);
    }

    public function sheets(): array
    {
        return [
            new AnalisisUlanganSheet($this->analisisData),
            new HasilAnalisisSheet($this->hasilData),
            new ProgramPerbaikanSheet($this->hasilData),
            new ProgramPengayaanSheet($this->pengayaanData),
        ];
    }
}
