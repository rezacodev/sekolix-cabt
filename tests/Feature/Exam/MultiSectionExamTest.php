<?php

namespace Tests\Feature\Exam;

use App\Models\AttemptSectionStart;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\ExamSectionQuestion;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\ExamService;
use App\Services\ScoringService;
use App\Services\ShuffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests — Multi-Section Exam (F09, Fase 14.5)
 *
 * Covers:
 *   1. Peralihan seksi: selesaikanSeksi meng-aktifkan AttemptSectionStart berikutnya
 *   2. Seksi terakhir: selesaikanSeksi finalize attempt (status = SELESAI)
 *   3. Nilai akhir mencakup soal dari seluruh seksi
 */
class MultiSectionExamTest extends TestCase
{
  use RefreshDatabase;

  protected User $guru;
  protected User $peserta;
  protected ExamPackage $package;
  protected ExamSession $session;
  protected ExamSection $seksi1;
  protected ExamSection $seksi2;
  protected array $soalSeksi1 = [];
  protected array $soalSeksi2 = [];

  public function setUp(): void
  {
    parent::setUp();

    $this->guru    = User::factory()->guru()->create();
    $this->peserta = User::factory()->peserta()->create();

    // ── Paket multi-seksi ──────────────────────────────────────────────
    $this->package = ExamPackage::factory()->create([
      'created_by'      => $this->guru->id,
      'has_sections'    => true,
      'navigasi_seksi'  => ExamPackage::NAV_SEKSI_URUT,
      'durasi_menit'    => 60,
      'acak_soal'       => false,
      'acak_opsi'       => false,
      'max_pengulangan' => 0,
      'tampilkan_hasil' => true,
      'grading_mode'    => ExamPackage::GRADING_REALTIME,
    ]);

    $this->session = ExamSession::factory()->create([
      'exam_package_id' => $this->package->id,
      'created_by'      => $this->guru->id,
      'status'          => ExamSession::STATUS_AKTIF,
      'token_akses'     => null,
    ]);

    $this->session->participants()->create([
      'user_id' => $this->peserta->id,
      'status'  => ExamSessionParticipant::STATUS_BELUM,
    ]);

    // ── Seksi 1: 2 soal PG (bobot=10 each) ──────────────────────────────
    $this->seksi1 = ExamSection::create([
      'exam_package_id'     => $this->package->id,
      'nama'                => 'Bagian 1',
      'urutan'              => 1,
      'durasi_menit'        => 10,
      'waktu_minimal_menit' => 0,
      'acak_soal'           => false,
    ]);

    $soalSeksi1 = Question::factory()->pg()->count(2)->create(['bobot' => 10]);
    $soalSeksi1->each(function ($q, $i) {
      ExamSectionQuestion::create([
        'section_id'  => $this->seksi1->id,
        'question_id' => $q->id,
        'urutan'      => $i + 1,
      ]);
    });
    $this->soalSeksi1 = $soalSeksi1->all();

    // ── Seksi 2: 2 soal PG (bobot=10 each) ──────────────────────────────
    $this->seksi2 = ExamSection::create([
      'exam_package_id'     => $this->package->id,
      'nama'                => 'Bagian 2',
      'urutan'              => 2,
      'durasi_menit'        => 10,
      'waktu_minimal_menit' => 0,
      'acak_soal'           => false,
    ]);

    $soalSeksi2 = Question::factory()->pg()->count(2)->create(['bobot' => 10]);
    $soalSeksi2->each(function ($q, $i) {
      ExamSectionQuestion::create([
        'section_id'  => $this->seksi2->id,
        'question_id' => $q->id,
        'urutan'      => $i + 1,
      ]);
    });
    $this->soalSeksi2 = $soalSeksi2->all();
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Peralihan seksi
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * POST selesai bagian 1 → AttemptSectionStart baru untuk bagian 2 dibuat.
   */
  public function test_selesaikan_seksi_pertama_mengaktifkan_seksi_berikutnya(): void
  {
    // Mulai ujian
    $this->actingAs($this->peserta)
      ->post(route('ujian.mulai', $this->session->id));

    $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
      ->where('user_id', $this->peserta->id)
      ->firstOrFail();

    // Pastikan hanya seksi 1 yang aktif setelah mulai
    $this->assertDatabaseCount('attempt_section_starts', 1);
    $this->assertDatabaseHas('attempt_section_starts', [
      'attempt_id' => $attempt->id,
      'section_id' => $this->seksi1->id,
    ]);

    // Selesaikan seksi 1
    $this->travel(1)->seconds(); // ensure distinct waktu_mulai for section 2
    $response = $this->actingAs($this->peserta)
      ->postJson(route('ujian.seksi.selesai', [
        'attemptId' => $attempt->id,
        'sectionId' => $this->seksi1->id,
      ]));

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'is_last' => false,
      ]);

    // Seksi 2 sekarang harus ikut tercatat
    $this->assertDatabaseCount('attempt_section_starts', 2);
    $this->assertDatabaseHas('attempt_section_starts', [
      'attempt_id' => $attempt->id,
      'section_id' => $this->seksi2->id,
    ]);

    // Attempt masih berlangsung
    $attempt->refresh();
    $this->assertEquals(ExamAttempt::STATUS_BERLANGSUNG, $attempt->status);
  }

  /**
   * POST selesai bagian terakhir → attempt finalisasi dengan status SELESAI.
   */
  public function test_selesaikan_seksi_terakhir_menyelesaikan_attempt(): void
  {
    // Mulai ujian
    $this->actingAs($this->peserta)
      ->post(route('ujian.mulai', $this->session->id));

    $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
      ->where('user_id', $this->peserta->id)
      ->firstOrFail();

    // Selesaikan seksi 1
    $this->travel(1)->seconds(); // distinct waktu_mulai for section 2
    $this->actingAs($this->peserta)
      ->postJson(route('ujian.seksi.selesai', [
        'attemptId' => $attempt->id,
        'sectionId' => $this->seksi1->id,
      ]));

    // Selesaikan seksi 2 (terakhir)
    $this->travel(1)->seconds(); // distinct waktu_mulai for finalize lookup
    $response = $this->actingAs($this->peserta)
      ->postJson(route('ujian.seksi.selesai', [
        'attemptId' => $attempt->id,
        'sectionId' => $this->seksi2->id,
      ]));

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'is_last' => true,
      ]);

    $attempt->refresh();
    $this->assertEquals(ExamAttempt::STATUS_SELESAI, $attempt->status);
    $this->assertNotNull($attempt->waktu_selesai);
  }

    // ─────────────────────────────────────────────────────────────────────────
    // Nilai akhir multi-section
    // ─────────────────────────────────────────────────────────────────────────

  /**
   * Nilai akhir dihitung dari soal seluruh seksi (bukan hanya seksi aktif).
   * Setup: 4 soal (2 per seksi), bobot=10 tiap soal.
   * Semua dijawab benar → nilai_akhir = 100.
   */
  public function test_nilai_akhir_mencakup_soal_dari_semua_seksi(): void
  {
    $service = new ExamService(new ShuffleService(), new ScoringService());

    // Mulai ujian via service (lebih reliable untuk setup)
    $attempt = $service->mulai($this->session->id, $this->peserta->id);
    $attempt->load('questions.question.options');

    // Jawab semua soal dengan jawaban benar langsung via service
    // Scoring PG mengharapkan ID opsi (integer), bukan kode_opsi string
    foreach ($attempt->questions as $aq) {
      $correctOption = $aq->question->options->firstWhere('is_correct', true);
      $service->simpanJawaban($attempt->id, $aq->question_id, $correctOption->id, false);
    }

    // Selesaikan seksi 1 → seksi 2 aktif (via HTTP — ini yang diuji)
    $this->travel(1)->seconds();
    $this->actingAs($this->peserta)
      ->postJson(route('ujian.seksi.selesai', [
        'attemptId' => $attempt->id,
        'sectionId' => $this->seksi1->id,
      ]))->assertOk()->assertJson(['success' => true, 'is_last' => false]);

    // Selesaikan seksi 2 (terakhir → submit+scoring otomatis)
    $this->travel(1)->seconds();
    $this->actingAs($this->peserta)
      ->postJson(route('ujian.seksi.selesai', [
        'attemptId' => $attempt->id,
        'sectionId' => $this->seksi2->id,
      ]))->assertOk()->assertJson(['success' => true, 'is_last' => true]);

    $attempt->refresh();

    // nilai_akhir harus 100 — semua soal (4 soal × 10 bobot) dijawab benar
    $this->assertEquals(
      100.0,
      (float) $attempt->nilai_akhir,
      "nilai_akhir seharusnya 100 jika semua soal dari seluruh seksi dijawab benar"
    );

    // Semua attempt_questions harus sudah dinilai (nilai_perolehan tidak null)
    $attempt->load('questions');
    $attempt->questions->each(function ($aq) {
      $this->assertNotNull(
        $aq->nilai_perolehan,
        "nilai_perolehan untuk soal {$aq->question_id} seharusnya tidak null setelah submit"
      );
    });
  }

  /**
   * Nilai akhir mencerminkan jawaban parsial: hanya soal seksi 1 yang dijawab benar.
   * 2 benar dari 4 soal (bobot=10 masing-masing) → nilai_akhir = 50.
   */
  public function test_nilai_akhir_parsial_hanya_seksi_pertama_dijawab(): void
  {
    $service = new ExamService(new ShuffleService(), new ScoringService());

    // Mulai ujian via service
    $attempt = $service->mulai($this->session->id, $this->peserta->id);
    $attempt->load('questions.question.options');

    // Jawab hanya soal milik seksi 1 dengan jawaban benar
    // Scoring PG mengharapkan ID opsi (integer), bukan kode_opsi string
    $soalSeksi1Ids = collect($this->soalSeksi1)->pluck('id');
    foreach ($attempt->questions->whereIn('question_id', $soalSeksi1Ids->all()) as $aq) {
      $correctOption = $aq->question->options->firstWhere('is_correct', true);
      $service->simpanJawaban($attempt->id, $aq->question_id, $correctOption->id, false);
    }
    // Soal seksi 2 dibiarkan kosong (jawaban_peserta = null)

    // Selesaikan seksi 1
    $this->travel(1)->seconds();
    $this->actingAs($this->peserta)
      ->postJson(route('ujian.seksi.selesai', [
        'attemptId' => $attempt->id,
        'sectionId' => $this->seksi1->id,
      ]))->assertOk()->assertJson(['success' => true]);

    // Selesaikan seksi 2 (terakhir → submit+scoring)
    $this->travel(1)->seconds();
    $this->actingAs($this->peserta)
      ->postJson(route('ujian.seksi.selesai', [
        'attemptId' => $attempt->id,
        'sectionId' => $this->seksi2->id,
      ]))->assertOk()->assertJson(['success' => true, 'is_last' => true]);

    $attempt->refresh();

    // 2 soal benar dari total 4 soal, bobot sama → nilai = 50
    $this->assertEquals(
      50.0,
      (float) $attempt->nilai_akhir,
      "nilai_akhir seharusnya 50 jika hanya soal seksi 1 (2 dari 4) yang dijawab benar"
    );
  }
}
