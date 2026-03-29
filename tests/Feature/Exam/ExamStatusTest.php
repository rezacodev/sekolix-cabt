<?php

namespace Tests\Feature\Exam;

use App\Models\AttemptLog;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected User $peserta;
    protected ExamSession $session;
    protected ExamPackage $package;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);

        $this->package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create([
                'created_by'      => $this->guru->id,
                'durasi_menit'    => 60,
                'grading_mode'    => ExamPackage::GRADING_REALTIME,
                'tampilkan_hasil' => true,
            ]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // /ujian/{attemptId}/status — sisa waktu AJAX
    // ─────────────────────────────────────────────────────────────────────────

    public function test_status_returns_sisa_waktu_for_active_attempt(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now()->subMinutes(5),
        ]);

        $this->actingAs($this->peserta)
            ->getJson(route('ujian.status', $attempt->id))
            ->assertOk()
            ->assertJsonStructure(['success', 'sisa_detik', 'status'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', ExamAttempt::STATUS_BERLANGSUNG);
    }

    public function test_status_requires_authentication(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->getJson(route('ujian.status', $attempt->id))->assertUnauthorized();
    }

    public function test_status_returns_403_for_other_peserta_attempt(): void
    {
        $lain    = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $lain->id,
            'waktu_mulai'     => now(),
        ]);

        $this->actingAs($this->peserta)
            ->getJson(route('ujian.status', $attempt->id))
            ->assertForbidden();
    }

    public function test_status_returns_redirect_url_when_time_expired(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->peserta)
            ->getJson(route('ujian.status', $attempt->id));

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals(0, $data['sisa_detik']);
        $this->assertNotNull($data['redirect_url']);
    }

    public function test_status_redirect_url_points_to_hasil_when_tampilkan_hasil_true(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now()->subHours(2),
        ]);

        $data = $this->actingAs($this->peserta)
            ->getJson(route('ujian.status', $attempt->id))
            ->json();

        $this->assertStringContainsString('hasil', $data['redirect_url']);
    }
}
