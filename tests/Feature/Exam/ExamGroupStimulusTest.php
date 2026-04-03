<?php

namespace Tests\Feature\Exam;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamGroupStimulusTest extends TestCase
{
  use RefreshDatabase;

  protected User $peserta;
  protected User $guru;
  protected ExamPackage $package;
  protected ExamSession $session;

  public function setUp(): void
  {
    parent::setUp();

    $this->peserta = User::factory()->create(['level' => User::LEVEL_PESERTA]);
    $this->guru    = User::factory()->create(['level' => User::LEVEL_GURU]);

    $this->package = ExamPackage::factory()->create([
      'created_by'   => $this->guru->id,
      'durasi_menit' => 120,
    ]);

    $this->session = ExamSession::factory()->create([
      'exam_package_id' => $this->package->id,
      'created_by'      => $this->guru->id,
      'status'          => ExamSession::STATUS_AKTIF,
    ]);

    $this->session->participants()->create([
      'user_id' => $this->peserta->id,
      'status'  => 'belum',
    ]);
  }

  /**
   * Test that exam page shows split-panel with stimulus content for grouped questions.
   */
  public function test_ujian_with_grouped_question_shows_stimulus_panel(): void
  {
    $group = QuestionGroup::create([
      'judul'         => 'Bacaan: Teknologi Hijau',
      'tipe_stimulus' => 'teks',
      'konten'        => '<p>Teknologi hijau adalah inovasi ramah lingkungan.</p>',
      'deskripsi'     => 'Bacalah teks berikut dengan saksama.',
      'created_by'    => $this->guru->id,
    ]);

    $q1 = Question::factory()->create([
      'question_group_id' => $group->id,
      'group_urutan'      => 1,
      'created_by'        => $this->guru->id,
    ]);
    $q2 = Question::factory()->create([
      'question_group_id' => $group->id,
      'group_urutan'      => 2,
      'created_by'        => $this->guru->id,
    ]);

    $attempt = ExamAttempt::factory()->berlangsung()->create([
      'exam_session_id' => $this->session->id,
      'user_id'         => $this->peserta->id,
      'waktu_mulai'     => now(),
    ]);

    AttemptQuestion::create(['attempt_id' => $attempt->id, 'question_id' => $q1->id, 'urutan' => 1]);
    AttemptQuestion::create(['attempt_id' => $attempt->id, 'question_id' => $q2->id, 'urutan' => 2]);

    $response = $this->actingAs($this->peserta)
      ->get("/ujian/{$this->session->id}/kerjakan");

    $response->assertStatus(200);
    $response->assertSee('Bacaan: Teknologi Hijau');
    $response->assertSee('Teknologi hijau adalah inovasi ramah lingkungan');
    $response->assertSee('Bacalah teks berikut dengan saksama');
    // Split-panel landmark should be present
    $response->assertSee('stimTab', false);
  }

  /**
   * Test that exam page shows full-width layout for standalone questions (no group).
   */
  public function test_ujian_with_standalone_question_shows_fullwidth_layout(): void
  {
    $q1 = Question::factory()->create([
      'question_group_id' => null,
      'created_by'        => $this->guru->id,
    ]);
    $q2 = Question::factory()->create([
      'question_group_id' => null,
      'created_by'        => $this->guru->id,
    ]);

    $attempt = ExamAttempt::factory()->berlangsung()->create([
      'exam_session_id' => $this->session->id,
      'user_id'         => $this->peserta->id,
      'waktu_mulai'     => now(),
    ]);

    AttemptQuestion::create(['attempt_id' => $attempt->id, 'question_id' => $q1->id, 'urutan' => 1]);
    AttemptQuestion::create(['attempt_id' => $attempt->id, 'question_id' => $q2->id, 'urutan' => 2]);

    $response = $this->actingAs($this->peserta)
      ->get("/ujian/{$this->session->id}/kerjakan");

    $response->assertStatus(200);
    // No stimulus panel — 'stimTab' should not appear
    $response->assertDontSee('stimTab', false);
    // Should show full-width standalone section
    $response->assertSee('Standalone: layout full-width', false);
  }

  /**
   * Test that mixed attempt (some grouped, some standalone) renders both layouts.
   */
  public function test_ujian_mixed_grouped_and_standalone_renders_correctly(): void
  {
    $group = QuestionGroup::create([
      'judul'         => 'Bacaan: Sejarah Nusantara',
      'tipe_stimulus' => 'teks',
      'konten'        => '<p>Kerajaan Majapahit berdiri pada abad ke-13.</p>',
      'created_by'    => $this->guru->id,
    ]);

    $qGrouped     = Question::factory()->create(['question_group_id' => $group->id, 'group_urutan' => 1, 'created_by' => $this->guru->id]);
    $qStandalone  = Question::factory()->create(['question_group_id' => null, 'created_by' => $this->guru->id]);

    $attempt = ExamAttempt::factory()->berlangsung()->create([
      'exam_session_id' => $this->session->id,
      'user_id'         => $this->peserta->id,
      'waktu_mulai'     => now(),
    ]);

    AttemptQuestion::create(['attempt_id' => $attempt->id, 'question_id' => $qGrouped->id,    'urutan' => 1]);
    AttemptQuestion::create(['attempt_id' => $attempt->id, 'question_id' => $qStandalone->id, 'urutan' => 2]);

    $response = $this->actingAs($this->peserta)
      ->get("/ujian/{$this->session->id}/kerjakan");

    $response->assertStatus(200);
    $response->assertSee('Bacaan: Sejarah Nusantara');
    $response->assertSee('Kerajaan Majapahit berdiri pada abad ke-13');
    $response->assertSee('stimTab', false);
  }
}
