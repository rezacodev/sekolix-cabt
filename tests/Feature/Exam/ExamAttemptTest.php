<?php

namespace Tests\Feature\Exam;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamAttemptTest extends TestCase
{
    use RefreshDatabase;

    protected ExamSession $session;
    protected User $peserta;
    protected ExamPackage $package;
    protected Question $question;

    public function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->peserta = User::factory()->create(['level' => 1]);
        $guru = User::factory()->create(['level' => 2]);

        $this->package = ExamPackage::factory()
            ->has(Question::factory()->count(5))
            ->create(['created_by' => $guru->id]);

        $this->session = ExamSession::factory()
            ->create([
                'exam_package_id' => $this->package->id,
                'created_by' => $guru->id,
                'status' => 'aktif',
            ]);

        // Assign peserta to session
        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status' => 'belum',
        ]);

        $this->question = $this->package->questions->first();
    }

    /**
     * Test peserta can start exam if all validations pass
     */
    public function test_peserta_can_start_exam(): void
    {
        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => $this->session->token_akses,
            ]);

        $this->assertDatabaseHas('exam_attempts', [
            'exam_session_id' => $this->session->id,
            'user_id' => $this->peserta->id,
            'status' => 'berlangsung',
        ]);
    }

    /**
     * Test cannot start exam with wrong token
     */
    public function test_cannot_start_exam_with_wrong_token(): void
    {
        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => 'wrongtoken',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('token');
    }

    /**
     * Test cannot start exam on inactive session
     */
    public function test_cannot_start_exam_on_inactive_session(): void
    {
        $this->session->update(['status' => 'draft']);

        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => $this->session->token_akses,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('sesi');
    }

    /**
     * Test cannot start exam if not assigned as participant
     */
    public function test_cannot_start_exam_if_not_participant(): void
    {
        $otherPeserta = User::factory()->create(['level' => 1]);

        $response = $this->actingAs($otherPeserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => $this->session->token_akses,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('peserta');
    }

    /**
     * Test peserta cannot exceed max attempts
     */
    public function test_cannot_exceed_max_attempts(): void
    {
        $this->package->update(['max_pengulangan' => 2]);

        // Create 2 existing completed attempts
        ExamAttempt::factory()->selesai()->count(2)->create([
            'exam_session_id' => $this->session->id,
            'user_id' => $this->peserta->id,
        ]);

        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => $this->session->token_akses,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('attempt');
    }

    /**
     * Test can start new attempt if under limit
     */
    public function test_can_start_new_attempt_if_under_limit(): void
    {
        $this->package->update(['max_pengulangan' => 3]);

        // Create 1 existing attempt
        ExamAttempt::factory()->create([
            'exam_session_id' => $this->session->id,
            'user_id' => $this->peserta->id,
        ]);

        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => $this->session->token_akses,
            ]);

        $this->assertDatabaseHas('exam_attempts', [
            'exam_session_id' => $this->session->id,
            'user_id' => $this->peserta->id,
        ]);
    }

    /**
     * Test case-insensitive token access
     */
    public function test_token_access_is_case_insensitive(): void
    {
        $this->session->update(['token_akses' => 'TestToken123']);

        $response = $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => 'testtoken123',
            ]);

        // Should succeed with case-insensitive token
        $this->assertDatabaseHas('exam_attempts', [
            'exam_session_id' => $this->session->id,
            'user_id' => $this->peserta->id,
        ]);
    }

    /**
     * Test attempt_ke increments on new attempt
     */
    public function test_attempt_ke_increments_on_new_attempt(): void
    {
        $this->package->update(['max_pengulangan' => 3]);

        // First attempt
        $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => $this->session->token_akses,
            ]);

        $firstAttempt = ExamAttempt::where([
            'exam_session_id' => $this->session->id,
            'user_id' => $this->peserta->id,
        ])->first();

        $this->assertEquals(1, $firstAttempt->attempt_ke);

        // Complete first attempt
        $firstAttempt->update(['status' => 'selesai']);

        // Second attempt
        $this->actingAs($this->peserta)
            ->post("/ujian/{$this->session->id}/mulai", [
                'token' => $this->session->token_akses,
            ]);

        $secondAttempt = ExamAttempt::where([
            'exam_session_id' => $this->session->id,
            'user_id' => $this->peserta->id,
        ])->where('id', '!=', $firstAttempt->id)->first();

        $this->assertEquals(2, $secondAttempt->attempt_ke);
    }

    /**
     * Test IDOR - peserta cannot access other's attempt
     */
    public function test_peserta_cannot_access_other_attempt(): void
    {
        $otherPeserta = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->create([
            'exam_session_id' => $this->session->id,
            'user_id' => $otherPeserta->id,
        ]);

        // Try to access other peserta's attempt
        $response = $this->actingAs($this->peserta)
            ->get("/ujian/{$this->session->id}/kerjakan", [
                'attemptId' => $attempt->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('attempt');
    }
}
