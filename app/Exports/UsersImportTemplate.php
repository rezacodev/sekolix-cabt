<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class UsersImportTemplate implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Template Import User';
    }

    public function headings(): array
    {
        return ['nama_lengkap', 'username', 'email', 'password', 'level', 'nomor_peserta', 'kode_rombel'];
    }

    public function array(): array
    {
        return [
            ['Budi Santoso', 'budi.santoso', 'budi@sekolah.id', 'password123', 1, 'PST-001', 'X-IPA-1'],
            ['Siti Aminah', 'siti.aminah', 'siti@sekolah.id', 'password123', 1, 'PST-002', 'X-IPA-2'],
        ];
    }
}
