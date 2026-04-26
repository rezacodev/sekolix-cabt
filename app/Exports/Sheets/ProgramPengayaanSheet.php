<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProgramPengayaanSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Program Pengayaan';
    }

    public function headings(): array
    {
        return ['No', 'Rentang Nilai', 'Nama Peserta', 'Jumlah Siswa', 'Kegiatan'];
    }

    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['kelompok'] as $no => $kelompok) {
            $names = $kelompok->peserta->pluck('nama')->join(', ');
            $rows->push([
                $no + 1,
                $kelompok->label,
                $names ?: '—',
                $kelompok->peserta->count(),
                'Menyelesaikan tugas berkaitan dengan materi yang telah dipelajari',
            ]);
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
