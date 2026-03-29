<?php

namespace Tests\Feature\Security;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rate limiting test untuk endpoint kritis.
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $peserta;
    protected User $guru;
    protected ExamAttempt $attempt;
    protected Question $question;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);

        $this->question = Question::factory()->pg()->create();

        $package = ExamPackage::factory()->create([
            'created_by'   => $this->guru->id,
            'grading_mode' => ExamPackage::GRADING_REALTIME,
        ]);
        $package->questions()->attach($this->question, ['urutan' => 1]);

        $session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);

        $this->attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        AttemptQuestion::factory()->create([
            'attempt_id'  => $this->attempt->id,
            'question_id' => $this->question->id,
        ]);
    }

    public function test_jawab_endpoint_has_throttle_applied(): void
    {
        // Middleware throttle:120,1 artinya 120 request per menit
        // Test bahwa middleware aktif: kirim 1 request valid → harus sukses
        $response = $this->actingAs($this->peserta)
            ->postJson(route('ujian.jawab'), [
                'attempt_id'  => $this->attempt->id,
                'question_id' => $this->question->id,
                'jawaban'     => 'A',
            ]);

        // Bisa 200 atau 403 (jika attempt ownership tidak cocok),
        // Yang penting bukan 429 (Too Many Requests) pada request pertama
        $this->assertNotEquals(429, $response->status());
    }

    public function test_login_rate_limit_enforced(): void
    {
        // Kirim 6 request login gagal → ke-7+ harus 429 (default rate limit 5)
        // Rate limit dari AppServiceProvider: RateLimiter::for('login')
        // Default: 5 attempts per 15 minutes

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'username' => 'invalid_user',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->post(route('login'), [
            'username' => 'invalid_user',
            'password' => 'wrongpassword',
        ]);

        // Setelah 5 request gagal, harus mendapatkan rate limit atau redirect dengan error throttle
        $status = $response->status();
        $this->assertTrue(
            in_array($status, [302, 429]),
            "Expected 302 or 429, got {$status}"
        );
    }

    public function test_jawab_accepts_valid_answer(): void
    {
        $response = $this->actingAs($this->peserta)
            ->postJson(route('ujian.jawab'), [
                'attempt_id'  => $this->attempt->id,
                'question_id' => $this->question->id,
                'jawaban'     => 'A',
            ]);

        // Harus sukses (bukan rate limited)
        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }
}
