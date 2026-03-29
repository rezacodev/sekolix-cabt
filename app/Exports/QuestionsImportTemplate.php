<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class QuestionsImportTemplate implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Template Import Soal';
    }

    public function headings(): array
    {
        return [
            'tipe_soal',
            'teks_soal',
            'opsi_a',
            'opsi_b',
            'opsi_c',
            'opsi_d',
            'opsi_e',
            'kunci',
            'kategori',
            'kesulitan',
            'bobot',
        ];
    }

    public function array(): array
    {
        return [
            [
                'PG',
                'Ibu kota Indonesia adalah ...',
                'Bandung',
                'Jakarta',
                'Surabaya',
                'Medan',
                '',
                'B',
                'Pengetahuan Umum',
                'mudah',
                1,
            ],
            [
                'ISIAN',
                'Ibukota provinsi Jawa Barat adalah ...',
                '',
                '',
                '',
                '',
                '',
                'Bandung',
                'Pengetahuan Umum',
                'mudah',
                1,
            ],
            [
                'URAIAN',
                'Jelaskan pengertian dari fotosintesis!',
                '',
                '',
                '',
                '',
                '',
                '',
                'Biologi',
                'sedang',
                5,
            ],
        ];
    }
}
