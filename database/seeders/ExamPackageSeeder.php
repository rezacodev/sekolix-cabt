<?php

namespace Database\Seeders;

use App\Models\ExamPackage;
use App\Models\ExamPackageQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ExamPackageSeeder
 *
 * Membuat 6 paket ujian komprehensif yang mencakup berbagai mapel,
 * pengaturan berbeda, dan kombinasi tipe soal yang bervariasi.
 *
 * Paket yang dibuat:
 *  1. Matematika Dasar            — 20 soal, realtime, acak, durasi 45 mnt
 *  2. IPA Terpadu Kelas X         — 20 soal, realtime, acak soal+opsi, 60 mnt
 *  3. Bahasa Indonesia            — 10 soal, manual grading (ada URAIAN), 30 mnt
 *  4. Sejarah Indonesia           — 10 soal, manual grading, 30 mnt
 *  5. TIK — Jaringan & Algoritma  — 14 soal, realtime, review terbuka, 45 mnt
 *  6. Tryout Akhir Tahun          — 30 soal mix semua mapel, acak, 90 mnt, max 3x
 *
 * Idempotent: paket dikenali dari nama uniknya.
 * Soal di-sync hanya jika paket baru dibuat (belum ada soal sebelumnya).
 */
class ExamPackageSeeder extends Seeder
{
    public function run(): void
    {
        // Gunakan user admin pertama sebagai creator, atau null jika belum ada
        $adminId = User::where('level', '>=', 3)->value('id');

        $packages = $this->packageDefinitions($adminId);

        $created  = 0;
        $skipped  = 0;

        foreach ($packages as $def) {
            $soalIds = $def['soal_ids'];
            unset($def['soal_ids']);

            [$paket, $isNew] = $this->firstOrCreatePackage($def);

            if ($isNew) {
                $this->attachSoal($paket, $soalIds);
                $created++;
                $this->command->line("  <info>✓</info> [{$paket->nama}] — {$paket->questions()->count()} soal");
            } else {
                $skipped++;
                $this->command->line("  <comment>–</comment> [{$paket->nama}] sudah ada, dilewati");
            }
        }

        $this->command->info("ExamPackageSeeder: {$created} paket dibuat, {$skipped} dilewati.");
    }

    // ────────────────────────────────────────────────────────────────────────────
    //  Package Definitions
    // ────────────────────────────────────────────────────────────────────────────

    private function packageDefinitions(?int $adminId): array
    {
        return [

            // ── 1. Matematika Dasar ─────────────────────────────────────────────
            [
                'nama'                => 'Matematika Dasar — Aljabar, Geometri, Statistika',
                'deskripsi'           => 'Paket ujian matematika dasar mencakup topik Aljabar, Geometri, Statistika, dan Trigonometri. Terdiri dari soal PG, PG Berbobot, PG Jawaban Jamak, Menjodohkan, Isian Singkat, dan Uraian.',
                'durasi_menit'        => 45,
                'waktu_minimal_menit' => 10,
                'acak_soal'           => true,
                'acak_opsi'           => false,
                'max_pengulangan'     => 0,
                'tampilkan_hasil'     => true,
                'tampilkan_review'    => true,
                'grading_mode'        => 'manual', // ada soal URAIAN
                'created_by'          => $adminId,
                // Aljabar (kat6): 1,2,4,5,6,18,20,23 | Geometri (kat7): 7,8,9,10,21
                // Statistika (kat8): 11,12,13,14,22 | Trigonometri (kat9): 15,16,17
                'soal_ids'            => [1, 2, 4, 5, 6, 18, 20, 23, 7, 8, 9, 10, 21, 11, 12, 13, 14, 22, 15, 16, 17],
            ],

            // ── 2. IPA Terpadu Kelas X ──────────────────────────────────────────
            [
                'nama'                => 'IPA Terpadu Kelas X — Fisika, Biologi, Kimia',
                'deskripsi'           => 'Ujian IPA terpadu mencakup seluruh sub-materi Fisika, Biologi, dan Kimia kelas X. Soal diacak urutan dan opsinya. Cocok digunakan untuk penilaian tengah semester.',
                'durasi_menit'        => 60,
                'waktu_minimal_menit' => 15,
                'acak_soal'           => true,
                'acak_opsi'           => true,
                'max_pengulangan'     => 0,
                'tampilkan_hasil'     => true,
                'tampilkan_review'    => false,
                'grading_mode'        => 'manual', // ada URAIAN di biologi & kimia
                'created_by'          => $adminId,
                // Fisika (kat10): 37,38,39,40,41 | Biologi (kat11): 42,43,44,45,46,47,48,49
                // Kimia (kat12): 50,51,52,53,54,55,56
                'soal_ids'            => [37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56],
            ],

            // ── 3. Bahasa Indonesia ─────────────────────────────────────────────
            [
                'nama'                => 'Bahasa Indonesia — Penilaian Harian',
                'deskripsi'           => 'Penilaian harian Bahasa Indonesia, mencakup semua tipe soal: PG, PG Jawaban Jamak, Menjodohkan, Isian Singkat, dan Uraian. Dinilai secara manual untuk soal uraian.',
                'durasi_menit'        => 30,
                'waktu_minimal_menit' => 5,
                'acak_soal'           => false,
                'acak_opsi'           => false,
                'max_pengulangan'     => 2,
                'tampilkan_hasil'     => true,
                'tampilkan_review'    => true,
                'grading_mode'        => 'manual',
                'created_by'          => $adminId,
                // Bahasa Indonesia (kat2): 27,28,29,30,31,32,33,34,35,36
                'soal_ids'            => [27, 28, 29, 30, 31, 32, 33, 34, 35, 36],
            ],

            // ── 4. Sejarah Indonesia ────────────────────────────────────────────
            [
                'nama'                => 'Sejarah Indonesia — Ujian Tengah Semester',
                'deskripsi'           => 'Ujian Tengah Semester mata pelajaran Sejarah Indonesia. Mencakup soal PG, PG Jawaban Jamak, Menjodohkan, Isian Singkat, dan Uraian. Penilaian manual untuk jawaban uraian.',
                'durasi_menit'        => 30,
                'waktu_minimal_menit' => 5,
                'acak_soal'           => false,
                'acak_opsi'           => false,
                'max_pengulangan'     => 1,
                'tampilkan_hasil'     => true,
                'tampilkan_review'    => false,
                'grading_mode'        => 'manual',
                'created_by'          => $adminId,
                // Sejarah (kat4): 57,58,59,60,61,62,63,64,65,66
                'soal_ids'            => [57, 58, 59, 60, 61, 62, 63, 64, 65, 66],
            ],

            // ── 5. TIK — Jaringan & Algoritma ──────────────────────────────────
            [
                'nama'                => 'TIK — Jaringan Komputer & Algoritma',
                'deskripsi'           => 'Paket ujian Teknologi Informasi dan Komunikasi mencakup materi Jaringan Komputer dan Dasar Algoritma. Peserta dapat mereview jawaban beserta pembahasan setelah ujian selesai. Grading otomatis realtime.',
                'durasi_menit'        => 45,
                'waktu_minimal_menit' => 10,
                'acak_soal'           => true,
                'acak_opsi'           => true,
                'max_pengulangan'     => 0,
                'tampilkan_hasil'     => true,
                'tampilkan_review'    => true,
                'grading_mode'        => 'manual', // ada URAIAN
                'created_by'          => $adminId,
                // Jaringan (kat13): 67,68,69,70,71,72,77,79 | Algoritma (kat14): 73,74,75,76,78,80
                'soal_ids'            => [67, 68, 69, 70, 71, 72, 77, 79, 73, 74, 75, 76, 78, 80],
            ],

            // ── 6. Tryout Akhir Tahun (semua mapel) ────────────────────────────
            [
                'nama'                => 'Tryout Akhir Tahun — Semua Mapel',
                'deskripsi'           => 'Paket tryout komprehensif akhir tahun yang mencakup semua mata pelajaran: Matematika, Bahasa Indonesia, IPA, Sejarah, dan TIK. Soal dan opsi diacak. Maksimal 3 kali pengulangan. Nilai ditampilkan setelah submit, namun review jawaban tidak dibuka (paket dirahasiakan).',
                'durasi_menit'        => 90,
                'waktu_minimal_menit' => 30,
                'acak_soal'           => true,
                'acak_opsi'           => true,
                'max_pengulangan'     => 3,
                'tampilkan_hasil'     => true,
                'tampilkan_review'    => false,
                'grading_mode'        => 'manual', // ada URAIAN di beberapa mapel
                'created_by'          => $adminId,
                // Campuran: Matematika (7) + B.Indo (6) + IPA (6) + Sejarah (5) + TIK (6) = 30
                'soal_ids'            => [
                    // Matematika mix: Aljabar PG + PGJ + ISIAN + URAIAN; Geometri JODOH; Trigonometri PG
                    1, 3, 6, 18, 21, 23, 25,
                    // Bahasa Indonesia: PG + PGJ + JODOH + ISIAN + URAIAN
                    27, 31, 32, 33, 35, 36,
                    // IPA: Fisika PG + Biologi PG + URAIAN + Kimia PG + URAIAN
                    37, 42, 47, 49, 50, 56,
                    // Sejarah: PG + PGJ + ISIAN + URAIAN
                    57, 62, 63, 64, 66,
                    // TIK: Jaringan PG + PGJ + JODOH + Algoritma PGJ + ISIAN + URAIAN
                    67, 71, 72, 76, 78, 80,
                ],
            ],

        ];
    }

    // ────────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * @return array{0: ExamPackage, 1: bool}  [$paket, $isNew]
     */
    private function firstOrCreatePackage(array $attributes): array
    {
        $existing = ExamPackage::where('nama', $attributes['nama'])->first();

        if ($existing) {
            return [$existing, false];
        }

        return [ExamPackage::create($attributes), true];
    }

    /**
     * Attach soal ke paket dengan urutan sesuai array index.
     * Tidak akan duplikat karena firstOrCreate di pivot.
     */
    private function attachSoal(ExamPackage $paket, array $soalIds): void
    {
        foreach ($soalIds as $urutan => $questionId) {
            ExamPackageQuestion::firstOrCreate(
                [
                    'exam_package_id' => $paket->id,
                    'question_id'     => $questionId,
                ],
                [
                    'urutan' => $urutan + 1,
                ],
            );
        }
    }
}
