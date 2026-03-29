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

class PrintTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected User $guru2;
    protected User $admin;
    protected User $peserta;
    protected ExamSession $session;
    protected ExamPackage $package;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->guru2   = User::factory()->create(['level' => 2]);
        $this->admin   = User::factory()->create(['level' => 3]);
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
            'status'          => ExamSession::STATUS_SELESAI,
        ]);

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_SELESAI,
        ]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'nilai_akhir'     => 80.0,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cetak rekap nilai
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guru_can_print_rekap_nilai_for_own_session(): void
    {
        $this->actingAs($this->guru)
            ->get(route('laporan.cetak.nilai', $this->session))
            ->assertOk()
            ->assertViewIs('print.rekap-nilai');
    }

    public function test_admin_can_print_rekap_nilai_for_any_session(): void
    {
        $this->actingAs($this->admin)
            ->get(route('laporan.cetak.nilai', $this->session))
            ->assertOk();
    }

    public function test_guru2_cannot_print_rekap_nilai_for_other_session(): void
    {
        $this->actingAs($this->guru2)
            ->get(route('laporan.cetak.nilai', $this->session))
            ->assertForbidden();
    }

    public function test_print_nilai_requires_authentication(): void
    {
        $this->get(route('laporan.cetak.nilai', $this->session))
            ->assertRedirect(route('login'));
    }

    public function test_peserta_cannot_print_nilai(): void
    {
        $this->actingAs($this->peserta)
            ->get(route('laporan.cetak.nilai', $this->session))
            ->assertForbidden();
    }

    public function test_rekap_nilai_view_has_required_data(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('laporan.cetak.nilai', $this->session));

        $response->assertOk();
        $response->assertViewHas('session');
        $response->assertViewHas('rekap');
        $response->assertViewHas('statistik');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cetak daftar hadir
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guru_can_print_daftar_hadir(): void
    {
        $this->actingAs($this->guru)
            ->get(route('laporan.cetak.daftar-hadir', $this->session))
            ->assertOk()
            ->assertViewIs('print.daftar-hadir');
    }

    public function test_guru2_cannot_print_daftar_hadir_for_other_session(): void
    {
        $this->actingAs($this->guru2)
            ->get(route('laporan.cetak.daftar-hadir', $this->session))
            ->assertForbidden();
    }

    public function test_daftar_hadir_view_has_kehadiran_data(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('laporan.cetak.daftar-hadir', $this->session));

        $response->assertOk();
        $response->assertViewHas('kehadiran');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cetak berita acara
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guru_can_print_berita_acara(): void
    {
        $this->actingAs($this->guru)
            ->get(route('laporan.cetak.berita-acara', $this->session))
            ->assertOk()
            ->assertViewIs('print.berita-acara');
    }

    public function test_guru2_cannot_print_berita_acara_for_other_session(): void
    {
        $this->actingAs($this->guru2)
            ->get(route('laporan.cetak.berita-acara', $this->session))
            ->assertForbidden();
    }

    public function test_berita_acara_view_has_required_data(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('laporan.cetak.berita-acara', $this->session));

        $response->assertOk();
        $response->assertViewHas('session');
        $response->assertViewHas('kehadiran');
        $response->assertViewHas('rekap');
    }
}
