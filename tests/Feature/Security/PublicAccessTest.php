<?php

namespace Tests\Feature\Security;

use App\Models\AppSetting;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected ExamSession $session;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create(['level' => 2]);

        $package = ExamPackage::factory()
            ->has(Question::factory()->count(2))
            ->create([
                'created_by'   => $this->guru->id,
                'grading_mode' => ExamPackage::GRADING_REALTIME,
            ]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Livescore publik — bisa akses tanpa login
    // ─────────────────────────────────────────────────────────────────────────

    public function test_livescore_show_accessible_publicly_when_enabled(): void
    {
        AppSetting::set('livescore_public', 'true');

        $this->get(route('livescore.show', $this->session))->assertOk();
    }

    public function test_livescore_data_accessible_publicly(): void
    {
        $this->getJson(route('livescore.data', $this->session))->assertOk();
    }

    public function test_livescore_requires_login_when_setting_disabled(): void
    {
        AppSetting::set('livescore_public', '0');

        $this->get(route('livescore.show', $this->session))
            ->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Protected routes tidak bisa diakses tanpa login
    // ─────────────────────────────────────────────────────────────────────────

    public function test_peserta_dashboard_not_public(): void
    {
        $this->get(route('peserta.dashboard'))->assertRedirect(route('login'));
    }

    public function test_ujian_show_not_public(): void
    {
        $this->get(route('ujian.show', $this->session->id))->assertRedirect(route('login'));
    }

    public function test_ujian_jawab_not_public(): void
    {
        $this->postJson(route('ujian.jawab'), [])->assertUnauthorized();
    }

    public function test_ujian_log_not_public(): void
    {
        $peserta = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->postJson(route('ujian.log', $attempt->id))->assertUnauthorized();
    }

    public function test_monitor_data_not_public(): void
    {
        $this->getJson(route('cabt.monitor.data', $this->session))->assertUnauthorized();
    }

    public function test_print_routes_not_public(): void
    {
        $routes = [
            route('laporan.cetak.nilai', $this->session),
            route('laporan.cetak.daftar-hadir', $this->session),
            route('laporan.cetak.berita-acara', $this->session),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_file_serve_not_public(): void
    {
        $this->get(route('ujian.file.uraian', [
            'attemptId' => 1,
            'filename'  => 'file.jpg',
        ]))->assertRedirect(route('login'));
    }
}
