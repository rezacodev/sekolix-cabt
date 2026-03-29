<?php

namespace Tests\Feature\Exam;

use App\Models\AppSetting;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamReviewTest extends TestCase
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
            ->has(Question::factory()->pg()->count(3))
            ->create([
                'created_by'       => $this->guru->id,
                'tampilkan_hasil'  => true,
                'tampilkan_review' => true,
                'grading_mode'     => ExamPackage::GRADING_REALTIME,
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
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Review jawaban
    // ─────────────────────────────────────────────────────────────────────────

    public function test_peserta_can_view_review_when_allowed(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $this->actingAs($this->peserta)
            ->get(route('ujian.review', $attempt->id))
            ->assertOk()
            ->assertViewIs('peserta.review');
    }

    public function test_review_requires_authentication(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $this->get(route('ujian.review', $attempt->id))->assertRedirect(route('login'));
    }

    public function test_peserta_cannot_review_other_peserta_attempt(): void
    {
        $lain    = User::factory()->create(['level' => 1]);
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $lain->id,
        ]);

        $this->actingAs($this->peserta)
            ->get(route('ujian.review', $attempt->id))
            ->assertNotFound();
    }

    public function test_review_returns_403_if_attempt_still_berlangsung(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->actingAs($this->peserta)
            ->get(route('ujian.review', $attempt->id))
            ->assertForbidden();
    }

    public function test_review_returns_403_when_paket_disables_tampilkan_review(): void
    {
        $package2 = ExamPackage::factory()
            ->has(Question::factory()->count(3))
            ->create([
                'created_by'       => $this->guru->id,
                'tampilkan_review' => false,
                'grading_mode'     => ExamPackage::GRADING_REALTIME,
            ]);

        $session2 = ExamSession::factory()->create([
            'exam_package_id' => $package2->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_SELESAI,
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
            ->get(route('ujian.review', $attempt->id))
            ->assertForbidden();
    }

    public function test_review_shows_pembahasan_when_sesi_selesai(): void
    {
        // Setting: show_pembahasan_setelah_sesi = true → hanya tampil jika sesi selesai
        AppSetting::set('show_pembahasan_setelah_sesi', 'true');

        // Sesi sudah STATUS_SELESAI (di setUp)
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('ujian.review', $attempt->id));

        $response->assertOk();
        $response->assertViewHas('showPembahasan', true);
    }

    public function test_review_hides_pembahasan_when_sesi_aktif(): void
    {
        AppSetting::set('show_pembahasan_setelah_sesi', 'true');

        // Buat sesi aktif (bukan selesai)
        $sessionAktif = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $sessionAktif->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_SELESAI,
        ]);

        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $sessionAktif->id,
            'user_id'         => $this->peserta->id,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('ujian.review', $attempt->id));

        $response->assertOk();
        $response->assertViewHas('showPembahasan', false);
    }

    public function test_review_always_shows_pembahasan_when_setting_disabled(): void
    {
        AppSetting::set('show_pembahasan_setelah_sesi', '0');

        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('ujian.review', $attempt->id));

        $response->assertOk();
        $response->assertViewHas('showPembahasan', true);
    }
}
