<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CurriculumStandard;
use App\Models\ExamBlueprint;
use App\Models\ExamBlueprintItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * ExamBlueprintSeeder
 *
 * Membuat 3 kisi-kisi (blueprint) ujian yang komprehensif:
 *
 *  1. Kisi-kisi Matematika Harian (10 soal)
 *     — Aljabar, Geometri, Statistika, Trigonometri
 *     — Mix tipe: PG, PGJ, ISIAN, URAIAN
 *     — Mix kesulitan: mudah/sedang/sulit
 *
 *  2. Kisi-kisi IPA Terpadu — PTS Kelas X (20 soal)
 *     — Fisika, Biologi (dengan standard KD/CP), Kimia
 *     — Mix tipe: PG, PGJ, ISIAN, URAIAN, BS
 *     — Referensi ke CurriculumStandard (jika ada)
 *
 *  3. Kisi-kisi Ujian Akhir Semester — Semua Tipe (30 soal)
 *     — Semua mapel: Matematika, BI, IPA, Sejarah, TIK
 *     — Semua tipe: PG, PG_BOBOT, PGJ, BS, JODOH, ISIAN, CLOZE, URAIAN
 *     — Mix bloom level C1–C6
 *
 * Idempotent — blueprint dikenali dari nama unik.
 */
class ExamBlueprintSeeder extends Seeder
{
  public function run(): void
  {
    $adminId = User::where('level', '>=', 3)->value('id') ?? 1;

    $this->blueprintMatematika($adminId);
    $this->blueprintIPATerpadu($adminId);
    $this->blueprintUAS($adminId);

    $total = ExamBlueprint::count();
    $this->command->info("ExamBlueprintSeeder: {$total} blueprint tersedia.");
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 1. Matematika Harian
  // ─────────────────────────────────────────────────────────────────────────

  private function blueprintMatematika(int $adminId): void
  {
    $bp = ExamBlueprint::firstOrCreate(
      ['nama' => 'Kisi-kisi Matematika Harian — Kelas X'],
      [
        'mata_pelajaran' => 'Matematika',
        'deskripsi'      => 'Blueprint ujian harian matematika kelas X. Mencakup Aljabar, Geometri, Statistika, dan Trigonometri. Distribusi: 4 soal mudah, 4 sedang, 2 sulit.',
        'total_soal'     => 10,
        'created_by'     => $adminId,
      ]
    );

    if ($bp->items()->count() > 0) {
      $this->command->line("  <comment>–</comment> Blueprint [{$bp->nama}] sudah ada, dilewati.");
      return;
    }

    $aljabar     = Category::where('nama', 'Aljabar')->value('id');
    $geometri    = Category::where('nama', 'Geometri')->value('id');
    $statistika  = Category::where('nama', 'Statistika')->value('id');
    $trigono     = Category::where('nama', 'Trigonometri')->value('id');

    if (! $aljabar) {
      $this->command->warn('  Kategori Matematika tidak ditemukan. Jalankan QuestionSeeder terlebih dahulu.');
      return;
    }

    $items = [
      // Aljabar — PG C2 mudah (2 soal)
      [
        'blueprint_id'     => $bp->id,
        'category_id'      => $aljabar,
        'tipe_soal'        => 'PG',
        'tingkat_kesulitan' => 'mudah',
        'bloom_level'      => 'C2',
        'jumlah_soal'      => 2,
        'bobot_per_soal'   => 1,
        'urutan'           => 1,
      ],
      // Aljabar — PG C3 sedang (2 soal)
      [
        'blueprint_id'     => $bp->id,
        'category_id'      => $aljabar,
        'tipe_soal'        => 'PG',
        'tingkat_kesulitan' => 'sedang',
        'bloom_level'      => 'C3',
        'jumlah_soal'      => 2,
        'bobot_per_soal'   => 1,
        'urutan'           => 2,
      ],
      // Geometri — PG C1 mudah (2 soal)
      [
        'blueprint_id'     => $bp->id,
        'category_id'      => $geometri,
        'tipe_soal'        => 'PG',
        'tingkat_kesulitan' => 'mudah',
        'bloom_level'      => 'C1',
        'jumlah_soal'      => 2,
        'bobot_per_soal'   => 1,
        'urutan'           => 3,
      ],
      // Statistika — PGJ C4 sedang (2 soal)
      [
        'blueprint_id'     => $bp->id,
        'category_id'      => $statistika,
        'tipe_soal'        => 'PGJ',
        'tingkat_kesulitan' => 'sedang',
        'bloom_level'      => 'C4',
        'jumlah_soal'      => 2,
        'bobot_per_soal'   => 2,
        'urutan'           => 4,
      ],
      // Trigonometri — ISIAN C2 mudah (1 soal)
      [
        'blueprint_id'     => $bp->id,
        'category_id'      => $trigono,
        'tipe_soal'        => 'ISIAN',
        'tingkat_kesulitan' => 'mudah',
        'bloom_level'      => 'C2',
        'jumlah_soal'      => 1,
        'bobot_per_soal'   => 1,
        'urutan'           => 5,
      ],
      // Aljabar — URAIAN C5 sulit (1 soal)
      [
        'blueprint_id'     => $bp->id,
        'category_id'      => $aljabar,
        'tipe_soal'        => 'URAIAN',
        'tingkat_kesulitan' => 'sulit',
        'bloom_level'      => 'C5',
        'jumlah_soal'      => 1,
        'bobot_per_soal'   => 5,
        'urutan'           => 6,
      ],
    ];

    foreach ($items as $item) {
      ExamBlueprintItem::create($item);
    }

    $this->command->line("  <info>✓</info> Blueprint [{$bp->nama}] — " . count($items) . " item kisi-kisi.");
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 2. IPA Terpadu PTS
  // ─────────────────────────────────────────────────────────────────────────

  private function blueprintIPATerpadu(int $adminId): void
  {
    $bp = ExamBlueprint::firstOrCreate(
      ['nama' => 'Kisi-kisi IPA Terpadu — PTS Kelas X'],
      [
        'mata_pelajaran' => 'IPA',
        'deskripsi'      => 'Blueprint Penilaian Tengah Semester IPA Terpadu kelas X. Distribusi merata antara Fisika (7 soal), Biologi (7 soal), Kimia (6 soal). Mencakup referensi KD/CP Biologi.',
        'total_soal'     => 20,
        'created_by'     => $adminId,
      ]
    );

    if ($bp->items()->count() > 0) {
      $this->command->line("  <comment>–</comment> Blueprint [{$bp->nama}] sudah ada, dilewati.");
      return;
    }

    $fisika  = Category::where('nama', 'Fisika')->value('id');
    $biologi = Category::where('nama', 'Biologi')->value('id');
    $kimia   = Category::where('nama', 'Kimia')->value('id');

    if (! $fisika) {
      $this->command->warn('  Kategori IPA tidak ditemukan. Jalankan QuestionSeeder terlebih dahulu.');
      return;
    }

    $stdBio1 = CurriculumStandard::where('kode', 'CP.BIO.10.1')->value('id');
    $stdBio2 = CurriculumStandard::where('kode', 'CP.BIO.10.2')->value('id');

    $items = [
      // Fisika — PG C1 mudah
      ['blueprint_id' => $bp->id, 'category_id' => $fisika,  'tipe_soal' => 'PG',     'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C1', 'jumlah_soal' => 3, 'bobot_per_soal' => 1, 'urutan' => 1],
      // Fisika — PG C3 sedang
      ['blueprint_id' => $bp->id, 'category_id' => $fisika,  'tipe_soal' => 'PG',     'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C3', 'jumlah_soal' => 2, 'bobot_per_soal' => 1, 'urutan' => 2],
      // Fisika — BS C2 mudah
      ['blueprint_id' => $bp->id, 'category_id' => $fisika,  'tipe_soal' => 'BS',     'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 2, 'bobot_per_soal' => 1, 'urutan' => 3],
      // Biologi — PG C1 mudah (referensi KD/CP)
      ['blueprint_id' => $bp->id, 'category_id' => $biologi, 'tipe_soal' => 'PG',     'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C1', 'standard_id' => $stdBio1, 'jumlah_soal' => 3, 'bobot_per_soal' => 1, 'urutan' => 4],
      // Biologi — PGJ C4 sedang (referensi KD/CP)
      ['blueprint_id' => $bp->id, 'category_id' => $biologi, 'tipe_soal' => 'PGJ',    'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C4', 'standard_id' => $stdBio2, 'jumlah_soal' => 2, 'bobot_per_soal' => 2, 'urutan' => 5],
      // Biologi — URAIAN C5 sulit
      ['blueprint_id' => $bp->id, 'category_id' => $biologi, 'tipe_soal' => 'URAIAN', 'tingkat_kesulitan' => 'sulit',  'bloom_level' => 'C5', 'jumlah_soal' => 2, 'bobot_per_soal' => 5, 'urutan' => 6],
      // Kimia — PG C2 mudah
      ['blueprint_id' => $bp->id, 'category_id' => $kimia,   'tipe_soal' => 'PG',     'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 3, 'bobot_per_soal' => 1, 'urutan' => 7],
      // Kimia — PG C3 sedang
      ['blueprint_id' => $bp->id, 'category_id' => $kimia,   'tipe_soal' => 'PG',     'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C3', 'jumlah_soal' => 2, 'bobot_per_soal' => 1, 'urutan' => 8],
      // Kimia — ISIAN C2 mudah
      ['blueprint_id' => $bp->id, 'category_id' => $kimia,   'tipe_soal' => 'ISIAN',  'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 1, 'bobot_per_soal' => 1, 'urutan' => 9],
    ];

    foreach ($items as $item) {
      ExamBlueprintItem::create($item);
    }

    $this->command->line("  <info>✓</info> Blueprint [{$bp->nama}] — " . count($items) . " item kisi-kisi.");
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 3. Ujian Akhir Semester — Semua Tipe Soal
  // ─────────────────────────────────────────────────────────────────────────

  private function blueprintUAS(int $adminId): void
  {
    $bp = ExamBlueprint::firstOrCreate(
      ['nama' => 'Kisi-kisi Ujian Akhir Semester — Semua Tipe Soal'],
      [
        'mata_pelajaran' => 'Umum',
        'deskripsi'      => 'Blueprint UAS komprehensif yang mencakup semua mapel dan semua tipe soal (PG, PG_BOBOT, PGJ, BS, JODOH, ISIAN, CLOZE, URAIAN). Total 30 soal dengan variasi bloom C1–C6. Cocok untuk demonstrasi seluruh fitur sistem.',
        'total_soal'     => 30,
        'created_by'     => $adminId,
      ]
    );

    if ($bp->items()->count() > 0) {
      $this->command->line("  <comment>–</comment> Blueprint [{$bp->nama}] sudah ada, dilewati.");
      return;
    }

    $mtk  = Category::where('nama', 'Matematika')->value('id');
    $bind = Category::where('nama', 'Bahasa Indonesia')->value('id');
    $ipa  = Category::where('nama', 'IPA')->value('id');
    $sej  = Category::where('nama', 'Sejarah')->value('id');
    $tik  = Category::where('nama', 'TIK')->value('id');

    if (! $mtk) {
      $this->command->warn('  Kategori tidak ditemukan. Jalankan QuestionSeeder terlebih dahulu.');
      return;
    }

    $items = [
      // PG — mudah — C1/C2
      ['blueprint_id' => $bp->id, 'category_id' => $mtk,  'tipe_soal' => 'PG',       'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 3, 'bobot_per_soal' => 1.00, 'urutan' => 1],
      ['blueprint_id' => $bp->id, 'category_id' => $bind, 'tipe_soal' => 'PG',       'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 3, 'bobot_per_soal' => 1.00, 'urutan' => 2],
      ['blueprint_id' => $bp->id, 'category_id' => $ipa,  'tipe_soal' => 'PG',       'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C3', 'jumlah_soal' => 3, 'bobot_per_soal' => 1.00, 'urutan' => 3],
      // PG_BOBOT — sedang — C4
      ['blueprint_id' => $bp->id, 'category_id' => $sej,  'tipe_soal' => 'PG_BOBOT', 'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C4', 'jumlah_soal' => 2, 'bobot_per_soal' => 2.00, 'urutan' => 4],
      // PGJ — sedang — C4
      ['blueprint_id' => $bp->id, 'category_id' => $tik,  'tipe_soal' => 'PGJ',      'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C4', 'jumlah_soal' => 2, 'bobot_per_soal' => 2.00, 'urutan' => 5],
      // BS — mudah — C1
      ['blueprint_id' => $bp->id, 'category_id' => $mtk,  'tipe_soal' => 'BS',       'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C1', 'jumlah_soal' => 3, 'bobot_per_soal' => 1.00, 'urutan' => 6],
      ['blueprint_id' => $bp->id, 'category_id' => $ipa,  'tipe_soal' => 'BS',       'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C1', 'jumlah_soal' => 2, 'bobot_per_soal' => 1.00, 'urutan' => 7],
      // JODOH — mudah — C2
      ['blueprint_id' => $bp->id, 'category_id' => $bind, 'tipe_soal' => 'JODOH',    'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 2, 'bobot_per_soal' => 2.00, 'urutan' => 8],
      // CLOZE — sedang — C3
      ['blueprint_id' => $bp->id, 'category_id' => $tik,  'tipe_soal' => 'CLOZE',    'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C3', 'jumlah_soal' => 2, 'bobot_per_soal' => 2.00, 'urutan' => 9],
      ['blueprint_id' => $bp->id, 'category_id' => $bind, 'tipe_soal' => 'CLOZE',    'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C3', 'jumlah_soal' => 2, 'bobot_per_soal' => 2.00, 'urutan' => 10],
      // ISIAN — mudah — C2
      ['blueprint_id' => $bp->id, 'category_id' => $ipa,  'tipe_soal' => 'ISIAN',    'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 2, 'bobot_per_soal' => 1.00, 'urutan' => 11],
      ['blueprint_id' => $bp->id, 'category_id' => $sej,  'tipe_soal' => 'ISIAN',    'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 1, 'bobot_per_soal' => 1.00, 'urutan' => 12],
      // URAIAN — sulit — C5/C6
      ['blueprint_id' => $bp->id, 'category_id' => $mtk,  'tipe_soal' => 'URAIAN',   'tingkat_kesulitan' => 'sulit',  'bloom_level' => 'C5', 'jumlah_soal' => 1, 'bobot_per_soal' => 5.00, 'urutan' => 13],
      ['blueprint_id' => $bp->id, 'category_id' => $bind, 'tipe_soal' => 'URAIAN',   'tingkat_kesulitan' => 'sulit',  'bloom_level' => 'C6', 'jumlah_soal' => 1, 'bobot_per_soal' => 5.00, 'urutan' => 14],
      ['blueprint_id' => $bp->id, 'category_id' => $ipa,  'tipe_soal' => 'URAIAN',   'tingkat_kesulitan' => 'sulit',  'bloom_level' => 'C5', 'jumlah_soal' => 1, 'bobot_per_soal' => 5.00, 'urutan' => 15],
    ];

    foreach ($items as $item) {
      ExamBlueprintItem::create($item);
    }

    $this->command->line("  <info>✓</info> Blueprint [{$bp->nama}] — " . count($items) . " item kisi-kisi.");
  }
}
