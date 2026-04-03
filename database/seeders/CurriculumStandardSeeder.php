<?php

namespace Database\Seeders;

use App\Models\CurriculumStandard;
use Illuminate\Database\Seeder;

class CurriculumStandardSeeder extends Seeder
{
    public function run(): void
    {
        $standards = [
            // Bahasa Indonesia — SMA Kelas X — Kurikulum Merdeka
            ['kode' => 'CP.BIN.10.1', 'mata_pelajaran' => 'Bahasa Indonesia', 'jenjang' => 'SMA', 'kurikulum' => 'Merdeka', 'kelas' => 'X', 'tingkat_kognitif' => 'C2', 'nama' => 'Peserta didik mampu memahami dan menganalisis informasi berupa gagasan, pikiran, perasaan, arahan, atau pesan dari berbagai tipe teks.'],
            ['kode' => 'CP.BIN.10.2', 'mata_pelajaran' => 'Bahasa Indonesia', 'jenjang' => 'SMA', 'kurikulum' => 'Merdeka', 'kelas' => 'X', 'tingkat_kognitif' => 'C4', 'nama' => 'Peserta didik mampu mengevaluasi informasi dari berbagai sumber dan mengidentifikasi unsur kebahasaan dalam teks.'],
            ['kode' => 'CP.BIN.10.3', 'mata_pelajaran' => 'Bahasa Indonesia', 'jenjang' => 'SMA', 'kurikulum' => 'Merdeka', 'kelas' => 'X', 'tingkat_kognitif' => 'C6', 'nama' => 'Peserta didik mampu menulis gagasan, pikiran, pandangan dalam berbagai jenis teks yang sesuai konteks.'],

            // Matematika — SMA Kelas X — K13
            ['kode' => '3.1', 'mata_pelajaran' => 'Matematika', 'jenjang' => 'SMA', 'kurikulum' => 'K13', 'kelas' => 'X', 'tingkat_kognitif' => 'C2', 'nama' => 'Mendeskripsikan dan menentukan penyelesaian fungsi eksponen dan logaritma menggunakan masalah kontekstual.'],
            ['kode' => '3.2', 'mata_pelajaran' => 'Matematika', 'jenjang' => 'SMA', 'kurikulum' => 'K13', 'kelas' => 'X', 'tingkat_kognitif' => 'C3', 'nama' => 'Menjelaskan vektor, operasi vektor, panjang vektor, sudut antarvektor dalam ruang berdimensi dua dan berdimensi tiga.'],
            ['kode' => '3.3', 'mata_pelajaran' => 'Matematika', 'jenjang' => 'SMA', 'kurikulum' => 'K13', 'kelas' => 'X', 'tingkat_kognitif' => 'C4', 'nama' => 'Menganalisis barisan dan deret aritmetika serta barisan dan deret geometri.'],

            // IPA Biologi — SMA Kelas X — Merdeka
            ['kode' => 'CP.BIO.10.1', 'mata_pelajaran' => 'Biologi', 'jenjang' => 'SMA', 'kurikulum' => 'Merdeka', 'kelas' => 'X', 'tingkat_kognitif' => 'C1', 'nama' => 'Peserta didik dapat mengidentifikasi dan memahami ruang lingkup biologi sebagai ilmu pengetahuan.'],
            ['kode' => 'CP.BIO.10.2', 'mata_pelajaran' => 'Biologi', 'jenjang' => 'SMA', 'kurikulum' => 'Merdeka', 'kelas' => 'X', 'tingkat_kognitif' => 'C3', 'nama' => 'Peserta didik dapat menjelaskan dan menerapkan konsep sel sebagai unit struktural dan fungsional makhluk hidup.'],

            // Bahasa Indonesia — SMP Kelas VII — K13
            ['kode' => '3.1', 'mata_pelajaran' => 'Bahasa Indonesia', 'jenjang' => 'SMP', 'kurikulum' => 'K13', 'kelas' => 'VII', 'tingkat_kognitif' => 'C2', 'nama' => 'Mengidentifikasi informasi dalam teks deskripsi tentang objek (sekolah, tempat wisata, tempat bersejarah, dan atau suasana pentas seni daerah).'],
            ['kode' => '3.2', 'mata_pelajaran' => 'Bahasa Indonesia', 'jenjang' => 'SMP', 'kurikulum' => 'K13', 'kelas' => 'VII', 'tingkat_kognitif' => 'C4', 'nama' => 'Menelaah struktur dan kebahasaan dari teks deskripsi tentang objek yang didengar dan dibaca.'],
        ];

        foreach ($standards as $data) {
            CurriculumStandard::firstOrCreate(
                ['kode' => $data['kode'], 'mata_pelajaran' => $data['mata_pelajaran'], 'jenjang' => $data['jenjang'], 'kurikulum' => $data['kurikulum'], 'kelas' => $data['kelas']],
                array_merge($data, ['created_by' => 1])
            );
        }

        $this->command->info('CurriculumStandard seeder: ' . count($standards) . ' records inserted/skipped.');
    }
}
