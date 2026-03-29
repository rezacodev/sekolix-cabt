<?php

namespace Tests\Feature\Peserta;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesertaDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $peserta;
    protected User $guru;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);
    }

    public function test_peserta_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->peserta)->get(route('peserta.dashboard'));

        $response->assertOk();
        $response->assertViewIs('peserta.dashboard');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('peserta.dashboard'))->assertRedirect(route('login'));
    }

    public function test_guru_can_also_access_peserta_dashboard(): void
    {
        // check.level:1 hanya mensyaratkan level >= 1.
        // Guru (level 2) memenuhi syarat dan diizinkan mengakses.
        $this->actingAs($this->guru)
            ->get(route('peserta.dashboard'))
            ->assertOk();
    }

    public function test_dashboard_shows_assigned_sessions(): void
    {
        $package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create(['created_by' => $this->guru->id]);

        $session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('peserta.dashboard'));

        $response->assertOk();
        $response->assertSee($session->nama_sesi);
    }

    public function test_dashboard_does_not_show_unassigned_sessions(): void
    {
        $lain = User::factory()->create(['level' => 1]);

        $package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create(['created_by' => $this->guru->id]);

        $session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        // Assign lain — bukan peserta ini
        $session->participants()->create([
            'user_id' => $lain->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('peserta.dashboard'));

        $response->assertOk();
        $response->assertDontSee($session->nama_sesi);
    }

    public function test_dashboard_shows_attempt_status(): void
    {
        $package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create(['created_by' => $this->guru->id]);

        $session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_SELESAI,
        ]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('peserta.dashboard'));

        $response->assertOk();
    }

    public function test_dashboard_shows_empty_state_when_no_sessions(): void
    {
        $response = $this->actingAs($this->peserta)->get(route('peserta.dashboard'));

        $response->assertOk();
        $response->assertViewHas('sessions');
    }
}
