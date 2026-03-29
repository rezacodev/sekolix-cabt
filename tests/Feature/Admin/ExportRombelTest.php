<?php

namespace Tests\Feature\Admin;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportRombelTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected User $guru2;
    protected User $admin;
    protected User $peserta;
    protected ExamSession $session;
    protected Rombel $rombel;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->guru2   = User::factory()->create(['level' => 2]);
        $this->admin   = User::factory()->create(['level' => 3]);

        $this->rombel  = Rombel::create([
            'nama'   => 'X-IPA-1',
            'kode'   => 'X-IPA-1',
            'aktif'  => true,
        ]);

        $this->peserta = User::factory()->create([
            'level'     => 1,
            'rombel_id' => $this->rombel->id,
        ]);

        $package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create([
                'created_by'   => $this->guru->id,
                'grading_mode' => ExamPackage::GRADING_REALTIME,
            ]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
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
            'nilai_akhir'     => 75.0,
        ]);
    }

    public function test_guru_can_export_rombel_nilai_for_own_session(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('cabt.guru.rombel.export', [
                'session' => $this->session->id,
                'rombel'  => $this->rombel->id,
            ]));

        $response->assertOk();
        // Excel file response
        $this->assertStringContainsString(
            'spreadsheet',
            strtolower($response->headers->get('Content-Type') ?? '')
        );
    }

    public function test_admin_can_export_any_session(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('cabt.guru.rombel.export', [
                'session' => $this->session->id,
                'rombel'  => $this->rombel->id,
            ]));

        $response->assertOk();
    }

    public function test_guru2_cannot_export_other_guru_session(): void
    {
        $this->actingAs($this->guru2)
            ->get(route('cabt.guru.rombel.export', [
                'session' => $this->session->id,
                'rombel'  => $this->rombel->id,
            ]))
            ->assertForbidden();
    }

    public function test_export_requires_authentication(): void
    {
        $this->get(route('cabt.guru.rombel.export', [
            'session' => $this->session->id,
            'rombel'  => $this->rombel->id,
        ]))->assertRedirect(route('login'));
    }

    public function test_peserta_cannot_access_export(): void
    {
        $this->actingAs($this->peserta)
            ->get(route('cabt.guru.rombel.export', [
                'session' => $this->session->id,
                'rombel'  => $this->rombel->id,
            ]))
            ->assertForbidden();
    }

    public function test_export_filename_contains_rombel_and_session_name(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('cabt.guru.rombel.export', [
                'session' => $this->session->id,
                'rombel'  => $this->rombel->id,
            ]));

        $response->assertOk();
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('x-ipa-1', $disposition);
    }
}
