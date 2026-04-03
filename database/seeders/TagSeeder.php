<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TagSeeder
 *
 * Membuat tag-tag yang representatif mencakup semua kasus:
 *
 *   Taksonomi Bloom:
 *     LOTS (C1-C3) — Lower Order Thinking Skills
 *     HOTS (C4-C6) — Higher Order Thinking Skills
 *     Ingatan, Pemahaman, Aplikasi, Analisis, Evaluasi, Kreasi
 *       (satu tag per level C1–C6 untuk filter granular)
 *
 *   Literasi & Numerasi:
 *     Numerasi  — soal mapel Matematika dan sub-kategorinya
 *     Literasi  — soal mapel Bahasa Indonesia
 *
 *   Konteks ujian:
 *     Ujian Nasional — soal tipikal UN
 *     Olimpiade      — soal tantangan tinggi
 *     Remidi         — soal mudah untuk remedial
 *
 * Idempotent: Tag dikenali dari nama unik (firstOrCreate).
 * Lampiran ke soal: syncWithoutDetaching agar data lampiran yang ada tidak hilang.
 */
class TagSeeder extends Seeder
{
  /** Daftar tag yang akan dibuat */
  private array $tagDefs = [
    // Taksonomi — umum
    'HOTS',
    'LOTS',
    // Taksonomi — per level
    'C1 Ingatan',
    'C2 Pemahaman',
    'C3 Aplikasi',
    'C4 Analisis',
    'C5 Evaluasi',
    'C6 Kreasi',
    // Literasi & Numerasi
    'Numerasi',
    'Literasi',
    // Konteks ujian
    'Ujian Nasional',
    'Olimpiade',
    'Remidi',
  ];

  public function run(): void
  {
    // ─── 1. Buat semua tag (idempotent) ──────────────────────────────────
    $tags = [];
    foreach ($this->tagDefs as $nama) {
      $tags[$nama] = Tag::firstOrCreate(['nama' => $nama]);
    }
    $this->command->info('TagSeeder: ' . count($tags) . ' tag dibuat/ditemukan.');

    // ─── 2. Lampirkan tag ke soal berdasarkan bloom_level ────────────────
    $this->attachByBloom($tags);

    // ─── 3. Lampirkan tag Numerasi ke soal Matematika (dan sub-kategori) ─
    $this->attachByMapel($tags['Numerasi'], [
      'Matematika',
      'Aljabar',
      'Geometri',
      'Statistika',
      'Trigonometri',
    ]);

    // ─── 4. Lampirkan tag Literasi ke soal Bahasa Indonesia ──────────────
    $this->attachByMapel($tags['Literasi'], ['Bahasa Indonesia']);

    // ─── 5. Lampirkan Olimpiade ke soal sulit dengan bloom C5/C6 ─────────
    Question::where('tingkat_kesulitan', 'sulit')
      ->whereIn('bloom_level', ['C5', 'C6'])
      ->each(function (Question $q) use ($tags) {
        $q->tags()->syncWithoutDetaching([$tags['Olimpiade']->id]);
      });

    // ─── 6. Lampirkan Remidi ke soal mudah ───────────────────────────────
    Question::where('tingkat_kesulitan', 'mudah')
      ->each(function (Question $q) use ($tags) {
        $q->tags()->syncWithoutDetaching([$tags['Remidi']->id]);
      });

    // ─── 7. Lampirkan Ujian Nasional ke beberapa PG mudah/sedang ─────────
    // Ambil 10 soal PG berbeda untuk mensimulasikan soal-soal tipikal UN
    Question::where('tipe', 'PG')
      ->whereIn('tingkat_kesulitan', ['mudah', 'sedang'])
      ->take(10)
      ->each(function (Question $q) use ($tags) {
        $q->tags()->syncWithoutDetaching([$tags['Ujian Nasional']->id]);
      });

    $total = DB::table('question_tag')->count();
    $this->command->info("TagSeeder: {$total} relasi soal-tag tersedia.");
  }

  /**
   * Lampirkan tag HOTS/LOTS dan C1–C6 berdasarkan bloom_level soal.
   */
  private function attachByBloom(array $tags): void
  {
    $bloomMap = [
      'C1' => ['LOTS', 'C1 Ingatan'],
      'C2' => ['LOTS', 'C2 Pemahaman'],
      'C3' => ['LOTS', 'C3 Aplikasi'],
      'C4' => ['HOTS', 'C4 Analisis'],
      'C5' => ['HOTS', 'C5 Evaluasi'],
      'C6' => ['HOTS', 'C6 Kreasi'],
    ];

    foreach ($bloomMap as $bloom => $tagNames) {
      $tagIds = array_map(fn($name) => $tags[$name]->id, $tagNames);

      Question::where('bloom_level', $bloom)
        ->each(function (Question $q) use ($tagIds) {
          $q->tags()->syncWithoutDetaching($tagIds);
        });
    }
  }

  /**
   * Lampirkan $tag ke semua soal yang berada di kategori dengan nama tertentu.
   */
  private function attachByMapel(Tag $tag, array $categoryNames): void
  {
    $categoryIds = Category::whereIn('nama', $categoryNames)->pluck('id')->toArray();

    if (empty($categoryIds)) {
      return;
    }

    Question::whereIn('kategori_id', $categoryIds)
      ->each(function (Question $q) use ($tag) {
        $q->tags()->syncWithoutDetaching([$tag->id]);
      });
  }
}
