<?php

namespace Tests\Feature\Exam;

use App\Models\AppSetting;
use App\Models\AttemptLog;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamLogTest extends TestCase
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
                'created_by'   => $this->guru->id,
                'durasi_menit' => 60,
                'grading_mode' => ExamPackage::GRADING_REALTIME,
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
    // /ujian/{attemptId}/log — log tab switch
    // ─────────────────────────────────────────────────────────────────────────

    public function test_log_records_tab_switch(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.log', $attempt->id), ['detail' => 'tab focus lost'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['tab_switch_count', 'submitted']);

        $this->assertDatabaseHas('attempt_logs', [
            'attempt_id' => $attempt->id,
            'event_type' => AttemptLog::EVENT_TAB_SWITCH,
        ]);
    }

    public function test_log_requires_authentication(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->postJson(route('ujian.log', $attempt->id))->assertUnauthorized();
    }

    public function test_log_returns_404_for_other_peserta_attempt(): void
    {
        $lain    = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $lain->id,
            'waktu_mulai'     => now(),
        ]);

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.log', $attempt->id))
            ->assertNotFound();
    }

    public function test_log_auto_submits_when_max_tab_switch_exceeded(): void
    {
        // Atur max_tab_switch = 3, auto_submit = true
        AppSetting::set('max_tab_switch', '2');
        AppSetting::set('auto_submit_on_max_tab', 'true');

        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        // POST 2 kali untuk melebihi batas
        $this->actingAs($this->peserta)
            ->postJson(route('ujian.log', $attempt->id));
        $response = $this->actingAs($this->peserta)
            ->postJson(route('ujian.log', $attempt->id));

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['submitted']);

        $attempt->refresh();
        $this->assertEquals(ExamAttempt::STATUS_DISKUALIFIKASI, $attempt->status);
    }

    public function test_log_does_not_auto_submit_when_under_max_tab_switch(): void
    {
        AppSetting::set('max_tab_switch', '5');
        AppSetting::set('auto_submit_on_max_tab', 'true');

        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $response = $this->actingAs($this->peserta)
            ->postJson(route('ujian.log', $attempt->id));

        $response->assertOk();
        $this->assertFalse($response->json('submitted'));
    }

    public function test_log_tracks_count_from_db_not_js(): void
    {
        // Nonaktifkan auto-submit agar count tidak bertambah dari autoSubmit log
        AppSetting::set('auto_submit_on_max_tab', '0');
        AppSetting::set('max_tab_switch', '10');

        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        // Log 3 tab switches
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->peserta)
                ->postJson(route('ujian.log', $attempt->id));
        }

        // DB count harus akurat: tepat 3 sesuai jumlah request yang dikirim
        $this->assertEquals(3, $attempt->fresh()->tabSwitchCount());
    }

    public function test_log_returns_404_for_finished_attempt(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.log', $attempt->id))
            ->assertNotFound();
    }
}
