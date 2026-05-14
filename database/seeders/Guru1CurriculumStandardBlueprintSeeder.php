<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CurriculumStandard;
use App\Models\ExamBlueprint;
use App\Models\ExamBlueprintItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class Guru1CurriculumStandardBlueprintSeeder extends Seeder
{
  public function run(): void
  {
    $guru = User::where('email', 'guru1@cabt.local')->first();

    if (! $guru) {
      $this->command->warn('User guru1@cabt.local tidak ditemukan. Seeder KD/CP dan blueprint dilewati.');
      return;
    }

    $guruId = $guru->id;

    $standards = [
      [
        'kode' => 'MTK.10.1',
        'mata_pelajaran' => 'Matematika',
        'jenjang' => 'SMA',
        'kurikulum' => 'K13',
        'kelas' => 'X',
        'tingkat_kognitif' => 'C2',
        'nama' => 'Mendeskripsikan fungsi eksponen dan logaritma serta menerapkannya dalam konteks masalah nyata.',
      ],
      [
        'kode' => 'MTK.10.2',
        'mata_pelajaran' => 'Matematika',
        'jenjang' => 'SMA',
        'kurikulum' => 'K13',
        'kelas' => 'X',
        'tingkat_kognitif' => 'C3',
        'nama' => 'Menganalisis operasi vektor, sudut antarvektor, dan hubungan antarvektor dalam ruang dua dimensi.',
      ],
      [
        'kode' => 'BIND.10.1',
        'mata_pelajaran' => 'Bahasa Indonesia',
        'jenjang' => 'SMA',
        'kurikulum' => 'Merdeka',
        'kelas' => 'X',
        'tingkat_kognitif' => 'C4',
        'nama' => 'Mengevaluasi struktur teks argumentatif dan menyusun tanggapan kritis yang tepat.',
      ],
    ];

    foreach ($standards as $data) {
      CurriculumStandard::firstOrCreate(
        array_merge(
          [
            'kode' => $data['kode'],
            'mata_pelajaran' => $data['mata_pelajaran'],
            'jenjang' => $data['jenjang'],
            'kurikulum' => $data['kurikulum'],
            'kelas' => $data['kelas'],
            'created_by' => $guruId,
          ],
          ['nama' => $data['nama']]
        ),
        array_merge($data, ['created_by' => $guruId])
      );
    }

    $this->command->info('Guru1 KD/CP seeder: ' . count($standards) . ' record inserted/skipped.');

    $this->createMathBlueprint($guruId);
    $this->createBindBlueprint($guruId);
  }

  private function createMathBlueprint(int $guruId): void
  {
    $aljabar    = Category::where('nama', 'Aljabar')->value('id');
    $geometri   = Category::where('nama', 'Geometri')->value('id');
    $statistika = Category::where('nama', 'Statistika')->value('id');
    $trigono    = Category::where('nama', 'Trigonometri')->value('id');

    if (! $aljabar || ! $geometri || ! $statistika || ! $trigono) {
      $this->command->warn('Kategori Matematika tidak lengkap. Jalankan QuestionSeeder terlebih dahulu.');
      return;
    }

    $stdMtk1 = CurriculumStandard::where('kode', 'MTK.10.1')->where('created_by', $guruId)->value('id');
    $stdMtk2 = CurriculumStandard::where('kode', 'MTK.10.2')->where('created_by', $guruId)->value('id');

    $bp = ExamBlueprint::firstOrCreate(
      ['nama' => 'Blueprint Harian Matematika — Guru Budi', 'created_by' => $guruId],
      [
        'mata_pelajaran' => 'Matematika',
        'deskripsi' => 'Blueprint harian matematika kelas X oleh guru Budi. Fokus pada Aljabar, Geometri, Statistika, dan Trigonometri.',
        'total_soal' => 10,
        'created_by' => $guruId,
      ]
    );

    if ($bp->items()->count() > 0) {
      $this->command->line("  <comment>–</comment> Blueprint [{$bp->nama}] sudah ada, dilewati.");
      return;
    }

    $items = [
      ['blueprint_id' => $bp->id, 'category_id' => $aljabar,    'standard_id' => $stdMtk1, 'tipe_soal' => 'PG',    'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 2, 'bobot_per_soal' => 1, 'urutan' => 1],
      ['blueprint_id' => $bp->id, 'category_id' => $geometri,   'standard_id' => $stdMtk2, 'tipe_soal' => 'PG',    'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C3', 'jumlah_soal' => 2, 'bobot_per_soal' => 1, 'urutan' => 2],
      ['blueprint_id' => $bp->id, 'category_id' => $statistika, 'tipe_soal' => 'PGJ',   'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C4', 'jumlah_soal' => 2, 'bobot_per_soal' => 2, 'urutan' => 3],
      ['blueprint_id' => $bp->id, 'category_id' => $trigono,    'standard_id' => $stdMtk2, 'tipe_soal' => 'ISIAN', 'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 2, 'bobot_per_soal' => 1, 'urutan' => 4],
      ['blueprint_id' => $bp->id, 'category_id' => $aljabar,    'standard_id' => $stdMtk1, 'tipe_soal' => 'URAIAN', 'tingkat_kesulitan' => 'sulit',  'bloom_level' => 'C5', 'jumlah_soal' => 2, 'bobot_per_soal' => 5, 'urutan' => 5],
    ];

    foreach ($items as $item) {
      ExamBlueprintItem::create($item);
    }

    $this->command->line("  <info>✓</info> Blueprint [{$bp->nama}] — " . count($items) . " item kisi-kisi.");
  }

  private function createBindBlueprint(int $guruId): void
  {
    $bind = Category::where('nama', 'Bahasa Indonesia')->value('id');

    if (! $bind) {
      $this->command->warn('Kategori Bahasa Indonesia tidak ditemukan. Jalankan QuestionSeeder terlebih dahulu.');
      return;
    }

    $stdBind = CurriculumStandard::where('kode', 'BIND.10.1')->where('created_by', $guruId)->value('id');

    $bp = ExamBlueprint::firstOrCreate(
      ['nama' => 'Blueprint Bahasa Indonesia — Guru Budi', 'created_by' => $guruId],
      [
        'mata_pelajaran' => 'Bahasa Indonesia',
        'deskripsi' => 'Blueprint Bahasa Indonesia kelas X oleh guru Budi. Fokus pada penilaian teks argumentatif dan respons kritis siswa.',
        'total_soal' => 8,
        'created_by' => $guruId,
      ]
    );

    if ($bp->items()->count() > 0) {
      $this->command->line("  <comment>–</comment> Blueprint [{$bp->nama}] sudah ada, dilewati.");
      return;
    }

    $items = [
      ['blueprint_id' => $bp->id, 'category_id' => $bind, 'standard_id' => $stdBind, 'tipe_soal' => 'PG',    'tingkat_kesulitan' => 'mudah',  'bloom_level' => 'C2', 'jumlah_soal' => 3, 'bobot_per_soal' => 1, 'urutan' => 1],
      ['blueprint_id' => $bp->id, 'category_id' => $bind, 'standard_id' => $stdBind, 'tipe_soal' => 'PGJ',   'tingkat_kesulitan' => 'sedang', 'bloom_level' => 'C4', 'jumlah_soal' => 2, 'bobot_per_soal' => 2, 'urutan' => 2],
      ['blueprint_id' => $bp->id, 'category_id' => $bind, 'standard_id' => $stdBind, 'tipe_soal' => 'URAIAN', 'tingkat_kesulitan' => 'sulit',  'bloom_level' => 'C5', 'jumlah_soal' => 3, 'bobot_per_soal' => 5, 'urutan' => 3],
    ];

    foreach ($items as $item) {
      ExamBlueprintItem::create($item);
    }

    $this->command->line("  <info>✓</info> Blueprint [{$bp->nama}] — " . count($items) . " item kisi-kisi.");
  }
}
