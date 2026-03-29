<?php

namespace Tests\Feature\Peserta;

use App\Models\AppSetting;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivescoreTest extends TestCase
{
    use RefreshDatabase;

    protected ExamSession $session;
    protected ExamPackage $package;
    protected User $guru;
    protected User $peserta;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);

        $this->package = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create(['created_by' => $this->guru->id]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);
    }

    // ── livescore show ───────────────────────────────────────────────────────

    public function test_livescore_page_accessible_without_login_when_public(): void
    {
        AppSetting::set('livescore_public', 'true');

        $this->get(route('livescore.show', $this->session))
            ->assertOk()
            ->assertViewIs('peserta.livescore');
    }

    public function test_livescore_redirects_to_login_when_public_disabled(): void
    {
        AppSetting::set('livescore_public', '0');

        $this->get(route('livescore.show', $this->session))
            ->assertRedirect(route('login'));
    }

    public function test_livescore_accessible_when_authenticated_and_public_disabled(): void
    {
        AppSetting::set('livescore_public', '0');

        $this->actingAs($this->peserta)
            ->get(route('livescore.show', $this->session))
            ->assertOk();
    }

    public function test_livescore_returns_404_for_draft_session(): void
    {
        $draft = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_DRAFT,
        ]);

        AppSetting::set('livescore_public', 'true');

        $this->get(route('livescore.show', $draft))->assertNotFound();
    }

    public function test_livescore_accessible_for_selesai_session(): void
    {
        $sessSelesai = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_SELESAI,
        ]);

        AppSetting::set('livescore_public', 'true');

        $this->get(route('livescore.show', $sessSelesai))->assertOk();
    }

    // ── livescore data (JSON) ────────────────────────────────────────────────

    public function test_livescore_data_returns_json(): void
    {
        AppSetting::set('livescore_public', 'true');

        $this->getJson(route('livescore.data', $this->session))
            ->assertOk()
            ->assertJsonStructure(['status', 'updated_at', 'total', 'rata_rata', 'rankings']);
    }

    public function test_livescore_data_includes_completed_attempts(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'nilai_akhir'     => 80.0,
        ]);

        $response = $this->getJson(route('livescore.data', $this->session));

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $data = $response->json();
        $this->assertCount(1, $data['rankings']);
        $this->assertEquals($this->peserta->name, $data['rankings'][0]['nama']);
    }

    public function test_livescore_data_excludes_berlangsung_attempts(): void
    {
        ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $response = $this->getJson(route('livescore.data', $this->session));
        $response->assertOk();
        $response->assertJsonPath('total', 0);
    }

    public function test_livescore_data_returns_404_for_draft_session(): void
    {
        $draft = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_DRAFT,
        ]);

        $this->getJson(route('livescore.data', $draft))->assertNotFound();
    }

    public function test_livescore_data_rankings_sorted_by_nilai_desc(): void
    {
        $peserta2 = User::factory()->create(['level' => 1]);
        $peserta3 = User::factory()->create(['level' => 1]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'nilai_akhir'     => 75.0,
        ]);
        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta2->id,
            'nilai_akhir'     => 90.0,
        ]);
        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta3->id,
            'nilai_akhir'     => 60.0,
        ]);

        $data = $this->getJson(route('livescore.data', $this->session))->json();

        $this->assertEquals(90.0, $data['rankings'][0]['nilai']);
        $this->assertEquals(75.0, $data['rankings'][1]['nilai']);
        $this->assertEquals(60.0, $data['rankings'][2]['nilai']);
    }

    public function test_livescore_data_calculates_rata_rata(): void
    {
        $peserta2 = User::factory()->create(['level' => 1]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'nilai_akhir'     => 80.0,
        ]);
        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta2->id,
            'nilai_akhir'     => 60.0,
        ]);

        $data = $this->getJson(route('livescore.data', $this->session))->json();

        $this->assertEquals(70.0, $data['rata_rata']);
    }
}
