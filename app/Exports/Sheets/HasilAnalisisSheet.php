<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HasilAnalisisSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Hasil Analisis';
    }

    public function headings(): array
    {
        return ['Keterangan', 'Nilai'];
    }

    public function collection(): Collection
    {
        $soalTidakTuntas = $this->data['soal_tidak_tuntas']->pluck('urutan')->join(', ') ?: 'Semua tuntas';

        return collect([
            ['Jumlah Peserta', $this->data['total_peserta']],
            ['Peserta Tuntas', $this->data['total_tuntas']],
            ['Peserta Belum Tuntas', $this->data['total_tidak_tuntas']],
            ['', ''],
            ['Jumlah Soal', $this->data['total_soal']],
            ['Soal Tuntas Klasikal', $this->data['soal_tuntas_count']],
            ['Soal Belum Tuntas Klasikal', $this->data['soal_tidak_tuntas_count']],
            ['Nomor Soal Belum Tuntas', $soalTidakTuntas],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
