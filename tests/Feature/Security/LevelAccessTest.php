<?php

namespace Tests\Feature\Security;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test bahwa setiap level hanya bisa mengakses resource yang diizinkan.
 */
class LevelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $peserta;
    protected User $unauthenticated;
    protected ExamSession $session;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['level' => 3]);
        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);

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

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);
    }

    // ─── Admin routes require level 3 ────────────────────────────────────────

    public function test_soal_search_requires_level_3_admin(): void
    {
        $this->actingAs($this->guru)->getJson(route('admin.soal.search'))->assertForbidden();
        $this->actingAs($this->peserta)->getJson(route('admin.soal.search'))->assertForbidden();
        $this->actingAs($this->admin)->getJson(route('admin.soal.search'))->assertOk();
    }

    // ─── Guru routes require level 2 ─────────────────────────────────────────

    public function test_monitor_data_requires_level_2(): void
    {
        $this->actingAs($this->peserta)
            ->getJson(route('cabt.monitor.data', $this->session))
            ->assertForbidden();

        $this->actingAs($this->guru)
            ->getJson(route('cabt.monitor.data', $this->session))
            ->assertOk();
    }

    public function test_print_routes_require_level_2(): void
    {
        $routes = [
            route('laporan.cetak.nilai', $this->session),
            route('laporan.cetak.daftar-hadir', $this->session),
            route('laporan.cetak.berita-acara', $this->session),
        ];

        foreach ($routes as $url) {
            $this->actingAs($this->peserta)->get($url)->assertForbidden();
            $this->actingAs($this->guru)->get($url)->assertOk();
        }
    }

    // ─── Peserta routes require level 1 ──────────────────────────────────────

    public function test_peserta_dashboard_requires_level_1(): void
    {
        // check.level:1 mensyaratkan level >= 1.
        // Guru (level 2 >= 1) juga diizinkan mengakses peserta dashboard.
        $this->actingAs($this->guru)
            ->get(route('peserta.dashboard'))
            ->assertOk();

        $this->actingAs($this->peserta)
            ->get(route('peserta.dashboard'))
            ->assertOk();
    }

    // ─── Unauthenticated users redirect to login ──────────────────────────────

    public function test_unauthenticated_redirected_from_peserta_routes(): void
    {
        $this->get(route('peserta.dashboard'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_redirected_from_admin_routes(): void
    {
        $this->getJson(route('admin.soal.search'))->assertUnauthorized();
    }

    public function test_unauthenticated_redirected_from_cabt_routes(): void
    {
        $this->getJson(route('cabt.monitor.data', $this->session))->assertUnauthorized();
    }

    public function test_unauthenticated_redirected_from_print_routes(): void
    {
        $this->get(route('laporan.cetak.nilai', $this->session))->assertRedirect(route('login'));
    }

    // ─── Cross-role: peserta cannot access ujian meant for others ────────────

    public function test_peserta_cannot_access_attempt_hasil_of_other_user(): void
    {
        $lain   = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $lain->id,
            'nilai_akhir'     => 80.0,
        ]);

        // Pastikan attempt milik $lain, bukan $peserta
        $this->assertNotEquals($this->peserta->id, $attempt->user_id);

        // Akses attempt orang lain harus gagal (404/403 karena firstOrFail)
        $this->actingAs($this->peserta)
            ->get(route('ujian.hasil', $attempt->id))
            ->assertNotFound();
    }

    public function test_peserta_cannot_submit_attempt_of_other_user(): void
    {
        $lain   = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $lain->id,
            'waktu_mulai'     => now(),
        ]);

        $this->actingAs($this->peserta)
            ->post(route('ujian.submit', $attempt->id))
            ->assertNotFound();
    }
}
