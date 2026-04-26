<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProgramPerbaikanSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Program Perbaikan';
    }

    public function headings(): array
    {
        return ['No', 'Nama Peserta', 'Nilai', 'Soal yang Belum Tuntas', 'Kegiatan'];
    }

    public function collection(): Collection
    {
        return $this->data['peserta_tidak_tuntas']->values()->map(function ($p, $idx) {
            return [
                $idx + 1,
                $p->nama,
                number_format($p->total, 0),
                $p->soal_salah->join(', ') ?: '—',
                'Ulangan Perbaikan',
            ];
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
