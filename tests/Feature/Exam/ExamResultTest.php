<?php

namespace Tests\Feature\Exam;

use App\Models\AppSetting;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamResultTest extends TestCase
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
                'tampilkan_hasil' => true,
                'grading_mode'    => ExamPackage::GRADING_REALTIME,
            ]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_SELESAI,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Hasil ujian
    // ─────────────────────────────────────────────────────────────────────────

    public function test_peserta_can_view_hasil_when_allowed(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'nilai_akhir'     => 85.0,
        ]);

        $this->actingAs($this->peserta)
            ->get(route('ujian.hasil', $attempt->id))
            ->assertOk()
            ->assertViewIs('peserta.hasil');
    }

    public function test_hasil_requires_authentication(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $this->get(route('ujian.hasil', $attempt->id))->assertRedirect(route('login'));
    }

    public function test_peserta_cannot_view_hasil_of_other_peserta(): void
    {
        $lain    = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $lain->id,
        ]);

        $this->actingAs($this->peserta)
            ->get(route('ujian.hasil', $attempt->id))
            ->assertNotFound();
    }

    public function test_hasil_returns_403_if_attempt_still_berlangsung(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->actingAs($this->peserta)
            ->get(route('ujian.hasil', $attempt->id))
            ->assertForbidden();
    }

    public function test_hasil_returns_403_when_paket_disables_show_hasil(): void
    {
        $package2 = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create([
                'created_by'      => $this->guru->id,
                'tampilkan_hasil' => false,
                'grading_mode'    => ExamPackage::GRADING_REALTIME,
            ]);

        $session2 = ExamSession::factory()->create([
            'exam_package_id' => $package2->id,
            'created_by'      => $this->guru->id,
        ]);

        $session2->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_SELESAI,
        ]);

        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $session2->id,
            'user_id'         => $this->peserta->id,
        ]);

        $this->actingAs($this->peserta)
            ->get(route('ujian.hasil', $attempt->id))
            ->assertForbidden();
    }

    public function test_hasil_shows_ranking_when_setting_enabled(): void
    {
        AppSetting::set('show_ranking_hasil', 'true');

        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'nilai_akhir'     => 80.0,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('ujian.hasil', $attempt->id));

        $response->assertOk();
        $response->assertViewHas('ranking');
        $this->assertNotNull($response->viewData('ranking'));
    }

    public function test_hasil_does_not_show_ranking_when_setting_disabled(): void
    {
        AppSetting::set('show_ranking_hasil', '0');

        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'nilai_akhir'     => 80.0,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('ujian.hasil', $attempt->id));

        $response->assertOk();
        $this->assertNull($response->viewData('ranking'));
    }
}
