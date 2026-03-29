<?php

namespace Tests\Feature\Admin;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $guru2;
    protected User $peserta;
    protected ExamSession $session;
    protected ExamPackage $package;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['level' => 3]);
        $this->guru    = User::factory()->create(['level' => 2]);
        $this->guru2   = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);

        $this->package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create([
                'created_by'   => $this->guru->id,
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
    // Monitor data (GET JSON)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guru_can_access_monitor_data_for_own_session(): void
    {
        $this->actingAs($this->guru)
            ->getJson(route('cabt.monitor.data', $this->session))
            ->assertOk()
            ->assertJsonStructure(['list', 'total', 'session_status']);
    }

    public function test_admin_can_access_any_session_monitor_data(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('cabt.monitor.data', $this->session))
            ->assertOk();
    }

    public function test_guru_cannot_access_other_guru_session_monitor(): void
    {
        $this->actingAs($this->guru2)
            ->getJson(route('cabt.monitor.data', $this->session))
            ->assertForbidden();
    }

    public function test_monitor_data_requires_authentication(): void
    {
        $this->getJson(route('cabt.monitor.data', $this->session))
            ->assertUnauthorized();
    }

    public function test_peserta_cannot_access_monitor_data(): void
    {
        $this->actingAs($this->peserta)
            ->getJson(route('cabt.monitor.data', $this->session))
            ->assertForbidden();
    }

    public function test_monitor_data_shows_participants(): void
    {
        $response = $this->actingAs($this->guru)
            ->getJson(route('cabt.monitor.data', $this->session));

        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('list', $data);
        $this->assertCount(1, $data['list']);
        $this->assertEquals($this->peserta->id, $data['list'][0]['user_id']);
    }

    public function test_monitor_data_shows_attempt_status(): void
    {
        ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $response = $this->actingAs($this->guru)
            ->getJson(route('cabt.monitor.data', $this->session));

        $data = $response->json();
        $participant = $data['list'][0];

        $this->assertEquals(ExamAttempt::STATUS_BERLANGSUNG, $participant['attempt_status']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Paksa keluar (POST)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guru_can_paksa_keluar_peserta(): void
    {
        ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->actingAs($this->guru)
            ->postJson(route('cabt.monitor.paksa-keluar', [
                'session' => $this->session->id,
                'userId'  => $this->peserta->id,
            ]))
            ->assertOk();

        $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
            ->where('user_id', $this->peserta->id)
            ->first();

        $this->assertEquals(ExamAttempt::STATUS_DISKUALIFIKASI, $attempt->status);
    }

    public function test_guru2_cannot_paksa_keluar_in_other_guru_session(): void
    {
        $this->actingAs($this->guru2)
            ->postJson(route('cabt.monitor.paksa-keluar', [
                'session' => $this->session->id,
                'userId'  => $this->peserta->id,
            ]))
            ->assertForbidden();
    }

    public function test_paksa_keluar_requires_authentication(): void
    {
        $this->postJson(route('cabt.monitor.paksa-keluar', [
            'session' => $this->session->id,
            'userId'  => $this->peserta->id,
        ]))->assertUnauthorized();
    }
}
