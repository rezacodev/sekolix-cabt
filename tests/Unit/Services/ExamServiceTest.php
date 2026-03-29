<?php

namespace Tests\Unit\Services;

use App\Models\AttemptLog;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use App\Services\ExamService;
use App\Services\ScoringService;
use App\Services\ShuffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExamService $examService;
    protected ExamSession $session;
    protected ExamPackage $package;
    protected User $peserta;
    protected User $guru;

    public function setUp(): void
    {
        parent::setUp();

        $this->examService = new ExamService(
            new ShuffleService(),
            new ScoringService()
        );

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
            'status'          => 'aktif',
        ]);

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // validasiWaktu
    // ─────────────────────────────────────────────────────────────────────────

    public function test_validasi_waktu_returns_sisa_detik_for_active_attempt(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now()->subMinutes(10),
        ]);

        $result = $this->examService->validasiWaktu($attempt->id);

        $this->assertArrayHasKey('sisa_detik', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertGreaterThan(0, $result['sisa_detik']);
        $this->assertEquals(ExamAttempt::STATUS_BERLANGSUNG, $result['status']);
    }

    public function test_validasi_waktu_returns_zero_if_already_selesai(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $result = $this->examService->validasiWaktu($attempt->id);

        $this->assertEquals(0, $result['sisa_detik']);
        $this->assertEquals(ExamAttempt::STATUS_SELESAI, $result['status']);
    }

    public function test_validasi_waktu_auto_submits_when_time_expired(): void
    {
        // durasi 60 menit, tapi mulai 2 jam lalu → sudah habis
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now()->subHours(2),
        ]);

        $result = $this->examService->validasiWaktu($attempt->id);

        $this->assertEquals(0, $result['sisa_detik']);
        $this->assertEquals(ExamAttempt::STATUS_TIMEOUT, $result['status']);

        // Attempt seharusnya sudah berubah status
        $attempt->refresh();
        $this->assertEquals(ExamAttempt::STATUS_TIMEOUT, $attempt->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // catatLog
    // ─────────────────────────────────────────────────────────────────────────

    public function test_catat_log_creates_attempt_log_record(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->catatLog($attempt->id, AttemptLog::EVENT_TAB_SWITCH, 'detail test');

        $this->assertDatabaseHas('attempt_logs', [
            'attempt_id' => $attempt->id,
            'event_type' => AttemptLog::EVENT_TAB_SWITCH,
            'detail'     => 'detail test',
        ]);
    }

    public function test_catat_log_creates_record_without_detail(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->catatLog($attempt->id, AttemptLog::EVENT_BLUR);

        $this->assertDatabaseHas('attempt_logs', [
            'attempt_id' => $attempt->id,
            'event_type' => AttemptLog::EVENT_BLUR,
            'detail'     => null,
        ]);
    }

    public function test_catat_log_can_create_multiple_logs(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->catatLog($attempt->id, AttemptLog::EVENT_TAB_SWITCH);
        $this->examService->catatLog($attempt->id, AttemptLog::EVENT_TAB_SWITCH);
        $this->examService->catatLog($attempt->id, AttemptLog::EVENT_TAB_SWITCH);

        $count = AttemptLog::where('attempt_id', $attempt->id)
            ->where('event_type', AttemptLog::EVENT_TAB_SWITCH)
            ->count();

        $this->assertEquals(3, $count);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // autoSubmit — timeout
    // ─────────────────────────────────────────────────────────────────────────

    public function test_auto_submit_timeout_sets_status_timeout(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $result = $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TIMEOUT);

        $this->assertEquals(ExamAttempt::STATUS_TIMEOUT, $result->status);
    }

    public function test_auto_submit_timeout_sets_waktu_selesai(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TIMEOUT);

        $attempt->refresh();
        $this->assertNotNull($attempt->waktu_selesai);
    }

    public function test_auto_submit_timeout_creates_log_entry(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TIMEOUT);

        $this->assertDatabaseHas('attempt_logs', [
            'attempt_id' => $attempt->id,
            'event_type' => AttemptLog::EVENT_TIMEOUT,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // autoSubmit — tab switch (diskualifikasi)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_auto_submit_tab_switch_sets_status_diskualifikasi(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $result = $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TAB_SWITCH);

        $this->assertEquals(ExamAttempt::STATUS_DISKUALIFIKASI, $result->status);
    }

    public function test_auto_submit_tab_switch_creates_log_entry(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TAB_SWITCH);

        $this->assertDatabaseHas('attempt_logs', [
            'attempt_id' => $attempt->id,
            'event_type' => AttemptLog::EVENT_TAB_SWITCH,
        ]);
    }

    public function test_auto_submit_updates_participant_status_to_diskualifikasi(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TAB_SWITCH);

        $this->assertDatabaseHas('exam_session_participants', [
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'status'          => ExamSessionParticipant::STATUS_DISKUALIFIKASI,
        ]);
    }

    public function test_auto_submit_timeout_updates_participant_status_to_selesai(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TIMEOUT);

        $this->assertDatabaseHas('exam_session_participants', [
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'status'          => ExamSessionParticipant::STATUS_SELESAI,
        ]);
    }

    public function test_auto_submit_is_idempotent_when_already_selesai(): void
    {
        $attempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        // Panggil dua kali — tidak boleh throw error
        $result1 = $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TIMEOUT);
        $result2 = $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TIMEOUT);

        $this->assertEquals(ExamAttempt::STATUS_SELESAI, $result1->status);
        $this->assertEquals(ExamAttempt::STATUS_SELESAI, $result2->status);
    }

    public function test_auto_submit_calculates_jumlah_kosong(): void
    {
        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        // Buat 3 attempt_questions: 1 dijawab, 2 kosong
        $questions = $this->package->questions;
        AttemptQuestion::factory()->create([
            'attempt_id'      => $attempt->id,
            'question_id'     => $questions[0]->id,
            'jawaban_peserta' => 'A',
        ]);
        AttemptQuestion::factory()->create([
            'attempt_id'      => $attempt->id,
            'question_id'     => $questions[1]->id,
            'jawaban_peserta' => null,
        ]);
        AttemptQuestion::factory()->create([
            'attempt_id'      => $attempt->id,
            'question_id'     => $questions[2]->id,
            'jawaban_peserta' => null,
        ]);

        $this->examService->autoSubmit($attempt->id, AttemptLog::EVENT_TIMEOUT);

        $attempt->refresh();
        $this->assertEquals(2, $attempt->jumlah_kosong);
    }
}
