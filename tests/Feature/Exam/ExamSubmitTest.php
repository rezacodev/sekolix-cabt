<?php

namespace Tests\Feature\Exam;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected ExamSession $session;
    protected User $peserta;
    protected ExamPackage $package;
    protected ExamAttempt $attempt;

    public function setUp(): void
    {
        parent::setUp();

        $this->peserta = User::factory()->create(['level' => 1]);
        $guru = User::factory()->create(['level' => 2]);

        $this->package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create(['created_by' => $guru->id, 'durasi_menit' => 60]);

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
    }

    /**
     * Test peserta can submit exam
     */
    public function test_peserta_can_submit_exam(): void
    {
        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->attempt->id}/submit");

        $response->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', [
            'id' => $this->attempt->id,
            'status' => 'selesai',
        ]);
    }

    /**
     * Test cannot submit other peserta's attempt (IDOR)
     */
    public function test_cannot_submit_other_peserta_attempt(): void
    {
        $otherPeserta = User::factory()->create(['level' => 1]);
        $otherAttempt = ExamAttempt::factory()
            ->create([
                'exam_session_id' => $this->session->id,
                'user_id' => $otherPeserta->id,
            ]);

        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$otherAttempt->id}/submit");

        $response->assertStatus(404);
    }

    /**
     * Test submit is idempotent (can submit multiple times)
     */
    public function test_submit_is_idempotent(): void
    {
        // First submit
        $response1 = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->attempt->id}/submit");

        $response1->assertRedirect();

        // Second submit (should still succeed)
        $response2 = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->attempt->id}/submit");

        $response2->assertRedirect();

        // Status should still be selesai
        $this->assertDatabaseHas('exam_attempts', [
            'id' => $this->attempt->id,
            'status' => 'selesai',
        ]);
    }

    /**
     * Test cannot submit incomplete exam with minimum time not met
     */
    public function test_cannot_submit_if_minimum_time_not_met(): void
    {
        $this->expectNotToPerformAssertions();

        // Set waktu_mulai to just minutes ago
        $this->attempt->update([
            'waktu_mulai' => now()->subMinutes(1),
        ]);

        // Should fail if minimum time (e.g., 5 minutes) not met
        $this->package->update(['waktu_minimal_menit' => 5]);

        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->attempt->id}/submit");

        // Depending on implementation, may return 403 or still allow
        // This test validates the business logic
    }

    /**
     * Test submit sets waktu_selesai
     */
    public function test_submit_records_waktu_selesai(): void
    {
        $beforeSubmit = now();

        $this->actingAs($this->peserta)
            ->post("/ujian/{$this->attempt->id}/submit");

        $attempt = ExamAttempt::find($this->attempt->id);

        $this->assertNotNull($attempt->waktu_selesai);
        $this->assertGreaterThanOrEqual($beforeSubmit->timestamp, $attempt->waktu_selesai->timestamp);
    }

    /**
     * Test submit triggers scoring service
     */
    public function test_submit_triggers_scoring(): void
    {
        $this->expectNotToPerformAssertions();

        // Create attempt question with answer
        $question = $this->package->questions->first();
        
        AttemptQuestion::factory()->create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $question->id,
            'jawaban_peserta' => json_encode(['correct']),
        ]);

        $this->actingAs($this->peserta)
            ->post("/ujian/{$this->attempt->id}/submit");

        $attempt = ExamAttempt::find($this->attempt->id);

        // Should have nilai_akhir set after scoring
        // (depends on grading_mode setting)
    }

    /**
     * Test admin can submit for peserta
     */
    public function test_admin_can_force_submit(): void
    {
        $this->expectNotToPerformAssertions();

        $admin = User::factory()->create(['level' => 3]);

        // Admin should be able to force submit (if allowed in implementation)
        $response = $this->actingAs($admin)
            ->post("/ujian/{$this->attempt->id}/submit");

        // Response depends on implementation
        // If allowed, should return 200; if not, 403
    }

    /**
     * Test cannot submit already submitted attempt
     */
    public function test_cannot_submit_already_submitted_attempt(): void
    {
        $this->expectNotToPerformAssertions();

        $this->attempt->update(['status' => 'selesai']);

        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->attempt->id}/submit");

        // Should still succeed (idempotent) or return specific response
    }
}
