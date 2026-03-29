<?php

namespace Tests\Feature\Exam;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamAnswerTest extends TestCase
{
    use RefreshDatabase;

    protected ExamSession $session;
    protected User $peserta;
    protected ExamPackage $package;
    protected ExamAttempt $attempt;
    protected Question $pgQuestion;
    protected Question $pgjQuestion;

    public function setUp(): void
    {
        parent::setUp();

        $this->peserta = User::factory()->create(['level' => 1]);
        $guru = User::factory()->create(['level' => 2]);

        $this->pgQuestion = Question::factory()->create([
            'tipe' => 'PG',
            'created_by' => $guru->id,
        ]);
        // Create options for PG question
        QuestionOption::factory()->count(3)->create([
            'question_id' => $this->pgQuestion->id,
        ]);

        $this->pgjQuestion = Question::factory()->create([
            'tipe' => 'PGJ',
            'created_by' => $guru->id,
        ]);
        // Create options for PGJ question
        QuestionOption::factory()->count(4)->create([
            'question_id' => $this->pgjQuestion->id,
        ]);

        $this->package = ExamPackage::factory()
            ->create(['created_by' => $guru->id]);

        $this->package->questions()->attach([
            $this->pgQuestion->id => ['urutan' => 1],
            $this->pgjQuestion->id => ['urutan' => 2],
        ]);

        $this->session = ExamSession::factory()
            ->create([
                'exam_package_id' => $this->package->id,
                'created_by' => $guru->id,
                'status' => 'aktif',
            ]);

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status' => 'belum',
        ]);

        $this->attempt = ExamAttempt::factory()
            ->berlangsung()
            ->create([
                'exam_session_id' => $this->session->id,
                'user_id' => $this->peserta->id,
                'waktu_mulai' => now(),
            ]);

        // Create attempt_questions
        AttemptQuestion::factory()->create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->pgQuestion->id,
        ]);

        AttemptQuestion::factory()->create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->pgjQuestion->id,
        ]);
    }

    /**
     * Test peserta can save PG answer
     */
    public function test_peserta_can_save_pg_answer(): void
    {
        $option = $this->pgQuestion->options->first();

        $response = $this->actingAs($this->peserta)
            ->postJson('/ujian/jawab', [
                'attempt_id' => $this->attempt->id,
                'question_id' => $this->pgQuestion->id,
                'jawaban' => (string) $option->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attempt_questions', [
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->pgQuestion->id,
            'jawaban_peserta' => (string) $option->id,
        ]);
    }

    /**
     * Test peserta can save PGJ answer (multiple options)
     */
    public function test_peserta_can_save_pgj_answer(): void
    {
        // Ensure we have options
        if ($this->pgjQuestion->options->isEmpty()) {
            $this->markTestSkipped('Question has no options');
        }
        $options = $this->pgjQuestion->options->take(2)->pluck('id')->toArray();

        $response = $this->actingAs($this->peserta)
            ->postJson('/ujian/jawab', [
                'attempt_id' => $this->attempt->id,
                'question_id' => $this->pgjQuestion->id,
                'jawaban' => json_encode($options),
            ]);

        $response->assertStatus(200);

        $attemptQuestion = AttemptQuestion::where([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->pgjQuestion->id,
        ])->first();

        $saved = json_decode($attemptQuestion->jawaban_peserta);
        $this->assertEquals(count($options), count($saved));
    }

    /**
     * Test cannot save answer for other peserta's attempt (IDOR)
     */
    public function test_cannot_save_answer_for_other_attempt(): void
    {
        $otherPeserta = User::factory()->create(['level' => 1]);
        $otherAttempt = ExamAttempt::factory()
            ->create([
                'exam_session_id' => $this->session->id,
                'user_id' => $otherPeserta->id,
            ]);

        $option = $this->pgQuestion->options->first();

        $response = $this->actingAs($this->peserta)
            ->postJson('/ujian/jawab', [
                'attempt_id' => $otherAttempt->id,
                'question_id' => $this->pgQuestion->id,
                'jawaban' => (string) $option->id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test cannot save answer for non-berlangsung attempt
     */
    public function test_cannot_save_answer_for_finished_attempt(): void
    {
        $this->attempt->update(['status' => 'selesai']);

        $option = $this->pgQuestion->options->first();

        $response = $this->actingAs($this->peserta)
            ->postJson('/ujian/jawab', [
                'attempt_id' => $this->attempt->id,
                'question_id' => $this->pgQuestion->id,
                'jawaban' => (string) $option->id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test ragu flag can be toggled
     */
    public function test_peserta_can_toggle_ragu(): void
    {
        $response = $this->actingAs($this->peserta)
            ->postJson('/ujian/jawab', [
                'attempt_id' => $this->attempt->id,
                'question_id' => $this->pgQuestion->id,
                'jawaban' => null,
                'is_ragu' => true,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attempt_questions', [
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->pgQuestion->id,
            'is_ragu' => true,
        ]);
    }

    /**
     * Test answer updates previous answer
     */
    public function test_save_answer_updates_previous(): void
    {
        $option1 = $this->pgQuestion->options->skip(0)->first();
        $option2 = $this->pgQuestion->options->skip(1)->first();

        // Save first answer
        $this->actingAs($this->peserta)
            ->postJson('/ujian/jawab', [
                'attempt_id' => $this->attempt->id,
                'question_id' => $this->pgQuestion->id,
                'jawaban' => (string) $option1->id,
            ]);

        // Save second answer
        $this->actingAs($this->peserta)
            ->postJson('/ujian/jawab', [
                'attempt_id' => $this->attempt->id,
                'question_id' => $this->pgQuestion->id,
                'jawaban' => (string) $option2->id,
            ]);

        $this->assertDatabaseHas('attempt_questions', [
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->pgQuestion->id,
            'jawaban_peserta' => (string) $option2->id,
        ]);
    }
}
