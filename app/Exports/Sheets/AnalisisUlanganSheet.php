<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalisisUlanganSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Analisis Ulangan';
    }

    public function headings(): array
    {
        $soalHeaders = $this->data['soal_list']->map(fn($q, $i) => 'Soal ' . ($i + 1))->toArray();

        return array_merge(
            ['No', 'Nama Peserta', 'No. Peserta'],
            $soalHeaders,
            ['Jml Skor', '% Skor', 'Tuntas']
        );
    }

    public function collection(): Collection
    {
        return $this->data['peserta_data']->values()->map(function ($p, $idx) {
            $row = [$idx + 1, $p->nama, $p->nomor_peserta];
            foreach ($p->skor_per_soal as $skor) {
                $row[] = $skor;
            }
            $row[] = $p->total;
            $row[] = $p->persen . '%';
            $row[] = $p->tuntas ? 'Ya' : 'Tidak';
            return $row;
        });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
