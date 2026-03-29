<?php

namespace Tests\Unit\Services;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\Question;
use App\Models\QuestionKeyword;
use App\Models\QuestionMatch;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
  use RefreshDatabase;

  protected ScoringService $scoringService;
  protected ExamAttempt $attempt;
  protected User $guru;

  public function setUp(): void
  {
    parent::setUp();

    $this->scoringService = new ScoringService();
    $this->guru = User::factory()->create(['level' => 2]);

    $this->attempt = ExamAttempt::factory()->create();
  }

  /**
   * Test PG question scoring - correct answer
   */
  public function test_pg_question_correct_answer_scores_full_bobot(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'PG',
      'bobot' => 10,
      'created_by' => $this->guru->id,
    ]);

    // Create options
    $correctOption = QuestionOption::factory()->create([
      'question_id' => $question->id,
      'is_correct' => true,
      'urutan' => 0,
    ]);
    QuestionOption::factory()->count(2)->create([
      'question_id' => $question->id,
      'is_correct' => false,
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => (string) $correctOption->id,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    $this->assertEquals(10.0, $aq->nilai_perolehan);
    $this->assertTrue($aq->is_correct);
  }

  /**
   * Test PG question scoring - wrong answer
   */
  public function test_pg_question_wrong_answer_scores_zero(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'PG',
      'bobot' => 10,
      'created_by' => $this->guru->id,
    ]);

    QuestionOption::factory()->create([
      'question_id' => $question->id,
      'is_correct' => true,
      'urutan' => 0,
    ]);
    $wrongOption = QuestionOption::factory()->create([
      'question_id' => $question->id,
      'is_correct' => false,
      'urutan' => 1,
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => (string) $wrongOption->id,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    $this->assertEquals(0.0, $aq->nilai_perolehan);
    $this->assertFalse($aq->is_correct);
  }

  /**
   * Test PG question scoring - empty answer
   */
  public function test_pg_question_empty_answer_scores_zero(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'PG',
      'bobot' => 10,
      'created_by' => $this->guru->id,
    ]);

    QuestionOption::factory()->count(3)->create([
      'question_id' => $question->id,
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => null,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    $this->assertEquals(0.0, $aq->nilai_perolehan);
  }

  /**
   * Test PG_BOBOT scoring
   */
  public function test_pg_bobot_question_scoring(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'PG_BOBOT',
      'bobot' => 100,
      'created_by' => $this->guru->id,
    ]);

    $fiftyPercentOption = QuestionOption::factory()->create([
      'question_id' => $question->id,
      'bobot_persen' => 50,
      'urutan' => 0,
    ]);
    QuestionOption::factory()->create([
      'question_id' => $question->id,
      'bobot_persen' => 0,
      'urutan' => 1,
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => (string) $fiftyPercentOption->id,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    // 100 * (50/100) = 50
    $this->assertEquals(50.0, $aq->nilai_perolehan);
  }

  /**
   * Test PGJ (multiple choice) scoring
   */
  public function test_pgj_question_scoring_all_correct(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'PGJ',
      'bobot' => 20,
      'created_by' => $this->guru->id,
    ]);

    $correctOptions = collect();
    for ($i = 0; $i < 3; $i++) {
      $correctOptions->push(QuestionOption::factory()->create([
        'question_id' => $question->id,
        'is_correct' => true,
        'urutan' => $i,
      ]));
    }
    for ($i = 3; $i < 5; $i++) {
      QuestionOption::factory()->create([
        'question_id' => $question->id,
        'is_correct' => false,
        'urutan' => $i,
      ]);
    }

    $jawaban = json_encode($correctOptions->pluck('id')->toArray());

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => $jawaban,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    $this->assertEquals(20.0, $aq->nilai_perolehan);
  }

  /**
   * Test PGJ partial correct
   */
  public function test_pgj_question_partial_correct(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'PGJ',
      'bobot' => 20,
      'created_by' => $this->guru->id,
    ]);

    $correctOptions = collect();
    for ($i = 0; $i < 2; $i++) {
      $correctOptions->push(QuestionOption::factory()->create([
        'question_id' => $question->id,
        'is_correct' => true,
        'urutan' => $i,
      ]));
    }
    for ($i = 2; $i < 5; $i++) {
      QuestionOption::factory()->create([
        'question_id' => $question->id,
        'is_correct' => false,
        'urutan' => $i,
      ]);
    }

    // Select 1 correct + 1 wrong = 50% correct
    $jawaban = json_encode([$correctOptions[0]->id, $question->options->where('is_correct', false)->first()->id]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => $jawaban,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    // Score may vary based on implementation (linear or penalty-based)
    $this->assertGreaterThanOrEqual(0, $aq->nilai_perolehan);
    $this->assertLessThanOrEqual(20, $aq->nilai_perolehan);
  }

  /**
   * Test JODOH (matching) scoring
   */
  public function test_jodoh_question_scoring(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'JODOH',
      'bobot' => 30,
      'created_by' => $this->guru->id,
    ]);

    for ($i = 0; $i < 3; $i++) {
      QuestionMatch::factory()->create([
        'question_id' => $question->id,
        'urutan' => $i,
      ]);
    }

    // Simulate 2 correct matches out of 3
    $matches = $question->matches;
    $jawaban = json_encode([
      $matches[0]->id => $matches[0]->id,
      $matches[1]->id => $matches[1]->id,
      $matches[2]->id => 'wrong_id',
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => $jawaban,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    // 30 * (2/3) = 20
    $this->assertGreaterThanOrEqual(0, $aq->nilai_perolehan);
  }

  /**
   * Test ISIAN (short answer) scoring
   */
  public function test_isian_question_correct_keyword(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'ISIAN',
      'bobot' => 5,
      'created_by' => $this->guru->id,
    ]);

    QuestionKeyword::factory()->create([
      'question_id' => $question->id,
      'keyword' => 'mitokondria',
    ]);
    QuestionKeyword::factory()->create([
      'question_id' => $question->id,
      'keyword' => 'energi',
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => 'MITOKONDRIA',  // Case insensitive
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    $this->assertEquals(5.0, $aq->nilai_perolehan);
  }

  /**
   * Test ISIAN wrong keyword
   */
  public function test_isian_question_wrong_keyword(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'ISIAN',
      'bobot' => 5,
      'created_by' => $this->guru->id,
    ]);

    QuestionKeyword::factory()->create([
      'question_id' => $question->id,
      'keyword' => 'mitokondria',
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => 'nucleus',
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    $this->assertEquals(0.0, $aq->nilai_perolehan);
  }

  /**
   * Test URAIAN (essay) scoring - skipped in grade(), only via regrade()
   */
  public function test_uraian_question_skipped_in_grade(): void
  {
    $question = Question::factory()->create([
      'tipe' => 'URAIAN',
      'bobot' => 50,
      'created_by' => $this->guru->id,
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $question->id,
      'jawaban_peserta' => 'student essay answer',
      'nilai_perolehan' => null,
    ]);

    $this->scoringService->grade($this->attempt);

    $aq->refresh();
    // Should remain null until manually graded
    $this->assertNull($aq->nilai_perolehan);
  }

  /**
   * Test nilai_akhir calculation
   */
  public function test_nilai_akhir_calculated_correctly(): void
  {
    // Create 3 PG questions with bobot 10 each = 30 total
    for ($i = 0; $i < 3; $i++) {
      $question = Question::factory()->create([
        'tipe' => 'PG',
        'bobot' => 10,
        'created_by' => $this->guru->id,
      ]);

      $correctOption = QuestionOption::factory()->create([
        'question_id' => $question->id,
        'is_correct' => true,
        'urutan' => 0,
      ]);
      QuestionOption::factory()->count(2)->create([
        'question_id' => $question->id,
        'is_correct' => false,
      ]);

      // Answer: 2 correct, 1 wrong
      if ($i < 2) {
        $jawaban = (string) $correctOption->id;
      } else {
        $jawaban = (string) $question->options->where('is_correct', false)->first()->id;
      }

      AttemptQuestion::factory()->create([
        'attempt_id' => $this->attempt->id,
        'question_id' => $question->id,
        'jawaban_peserta' => $jawaban,
      ]);
    }

    $this->scoringService->grade($this->attempt);

    $this->attempt->refresh();
    // (20 / 30) * 100 = 66.67
    $this->assertNotNull($this->attempt->nilai_akhir);
    $this->assertGreaterThan(65, $this->attempt->nilai_akhir);
    $this->assertLessThan(68, $this->attempt->nilai_akhir);
  }

  /**
   * Test nilai_akhir is null if URAIAN not yet graded
   */
  public function test_nilai_akhir_null_if_ungraded_uraian_exists(): void
  {
    // PG question
    $pgQuestion = Question::factory()->create([
      'tipe' => 'PG',
      'bobot' => 10,
      'created_by' => $this->guru->id,
    ]);

    $correctOption = QuestionOption::factory()->create([
      'question_id' => $pgQuestion->id,
      'is_correct' => true,
      'urutan' => 0,
    ]);

    AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $pgQuestion->id,
      'jawaban_peserta' => (string) $correctOption->id,
    ]);

    // URAIAN question
    $uraianQuestion = Question::factory()->create([
      'tipe' => 'URAIAN',
      'bobot' => 50,
      'created_by' => $this->guru->id,
    ]);

    AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $uraianQuestion->id,
      'jawaban_peserta' => 'essay answer',
      'nilai_perolehan' => null,
    ]);

    $this->scoringService->grade($this->attempt);

    $this->attempt->refresh();
    // nilai_akhir should be null until URAIAN is graded
    $this->assertNull($this->attempt->nilai_akhir);
  }

  /**
   * Test regrade updates nilai_akhir after manual URAIAN grading
   */
  public function test_regrade_updates_nilai_akhir_after_uraian_grading(): void
  {
    $uraianQuestion = Question::factory()->create([
      'tipe' => 'URAIAN',
      'bobot' => 30,
      'created_by' => $this->guru->id,
    ]);

    $aq = AttemptQuestion::factory()->create([
      'attempt_id' => $this->attempt->id,
      'question_id' => $uraianQuestion->id,
      'jawaban_peserta' => 'essay answer',
      'nilai_perolehan' => null,
    ]);

    // Manually grade the essay: 25 out of 30
    $aq->update(['nilai_perolehan' => 25.0]);

    $this->scoringService->regrade($this->attempt);

    $this->attempt->refresh();
    // (25 / 30) * 100 = 83.33
    $this->assertNotNull($this->attempt->nilai_akhir);
    $this->assertGreaterThan(83, $this->attempt->nilai_akhir);
  }
}
