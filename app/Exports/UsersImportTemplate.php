<?php

namespace App\Exports;

use App\Models\Rombel;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class UsersImportTemplate implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new UsersImportTemplateDataSheet(),
            new UsersImportTemplateKeteranganSheet(),
        ];
    }
}

/**
 * Sheet 1 — Template data dengan contoh baris.
 */
class UsersImportTemplateDataSheet implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Template Import';
    }

    public function headings(): array
    {
        return ['nama_lengkap', 'username', 'email', 'password', 'level', 'nomor_peserta', 'kode_rombel'];
    }

    public function array(): array
    {
        return [
            ['Budi Santoso',  'budi.santoso',  'budi@sekolah.id',  'password123', 'peserta', 'PST-001', 'X-IPA-1'],
            ['Siti Aminah',   'siti.aminah',   'siti@sekolah.id',  'password123', 'peserta', 'PST-002', 'X-IPA-1;X-IPA-2'],
            ['Ahmad Guru',    'ahmad.guru',    'ahmad@sekolah.id', 'password123', 'guru',    '',        ''],
            ['Admin Sekolah', 'admin.sekolah', 'admin@sekolah.id', 'password123', 'admin',   '',        ''],
        ];
    }
}

/**
 * Sheet 2 — Keterangan kode level dan daftar rombel tersedia.
 */
class UsersImportTemplateKeteranganSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Keterangan';
    }

    public function array(): array
    {
        $rows = [
            ['KETERANGAN KOLOM'],
            [''],
            ['Kolom', 'Keterangan', 'Wajib?'],
            ['nama_lengkap',  'Nama lengkap user',                                       'Ya'],
            ['username',      'Username unik (huruf, angka, titik, strip, garis bawah)', 'Tidak'],
            ['email',         'Alamat email unik',                                        'Ya'],
            ['password',      'Password minimal 6 karakter',                              'Ya'],
            ['level',         'Kode level akun (lihat tabel di bawah)',                   'Ya'],
            ['nomor_peserta', 'Nomor peserta unik (hanya untuk level peserta)',            'Tidak'],
            ['kode_rombel',   'Kode rombel; pisahkan dengan titik koma (;) jika lebih dari satu', 'Tidak'],
            [''],
            ['KODE LEVEL'],
            ['Kode',         'Keterangan'],
        ];

        foreach (User::LEVEL_CODES as $code => $int) {
            $rows[] = [$code, User::levelLabels()[$int]];
        }

        $rows[] = [''];
        $rows[] = ['DAFTAR ROMBEL TERSEDIA'];
        $rows[] = ['Kode Rombel', 'Nama Rombel', 'Angkatan', 'Tahun Ajaran', 'Aktif'];

        $rombels = Rombel::orderBy('kode')->get();
        foreach ($rombels as $r) {
            $rows[] = [$r->kode, $r->nama, $r->angkatan ?? '-', $r->tahun_ajaran ?? '-', $r->aktif ? 'Ya' : 'Tidak'];
        }

        return $rows;
    }
}
