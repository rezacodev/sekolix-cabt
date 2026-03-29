<?php

namespace Tests\Unit\Services;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\AttemptQuestion;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $reportService;
    protected ExamSession $session;
    protected ExamPackage $package;
    protected User $guru;

    public function setUp(): void
    {
        parent::setUp();

        $this->reportService = new ReportService();

        $this->guru = User::factory()->create(['level' => 2]);

        $this->package = ExamPackage::factory()->create([
            'created_by'    => $this->guru->id,
            'durasi_menit'  => 60,
            'grading_mode'  => ExamPackage::GRADING_REALTIME,
        ]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => 'aktif',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // rekapNilai
    // ─────────────────────────────────────────────────────────────────────────

    public function test_rekap_nilai_returns_empty_when_no_attempts(): void
    {
        $rekap = $this->reportService->rekapNilai($this->session->id);

        $this->assertCount(0, $rekap);
    }

    public function test_rekap_nilai_returns_one_entry_per_peserta(): void
    {
        $peserta1 = User::factory()->create(['level' => 1]);
        $peserta2 = User::factory()->create(['level' => 1]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta1->id,
            'nilai_akhir'     => 80,
            'attempt_ke'      => 1,
        ]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta2->id,
            'nilai_akhir'     => 70,
            'attempt_ke'      => 1,
        ]);

        $rekap = $this->reportService->rekapNilai($this->session->id);

        $this->assertCount(2, $rekap);
    }

    public function test_rekap_nilai_picks_highest_attempt_ke_per_user(): void
    {
        $peserta = User::factory()->create(['level' => 1]);

        // attempt pertama — nilai rendah
        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta->id,
            'nilai_akhir'     => 40,
            'attempt_ke'      => 1,
        ]);

        // attempt kedua — nilai lebih tinggi
        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta->id,
            'nilai_akhir'     => 75,
            'attempt_ke'      => 2,
        ]);

        $rekap = $this->reportService->rekapNilai($this->session->id);

        $this->assertCount(1, $rekap);
        $this->assertEquals(75, $rekap->first()->nilai_akhir);
        $this->assertEquals(2, $rekap->first()->attempt_ke);
    }

    public function test_rekap_nilai_excludes_berlangsung_attempts(): void
    {
        $peserta = User::factory()->create(['level' => 1]);

        ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta->id,
            'waktu_mulai'     => now(),
        ]);

        $rekap = $this->reportService->rekapNilai($this->session->id);

        $this->assertCount(0, $rekap);
    }

    public function test_rekap_nilai_includes_timeout_and_diskualifikasi(): void
    {
        $peserta1 = User::factory()->create(['level' => 1]);
        $peserta2 = User::factory()->create(['level' => 1]);

        ExamAttempt::factory()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta1->id,
            'status'          => ExamAttempt::STATUS_TIMEOUT,
            'nilai_akhir'     => 50,
            'attempt_ke'      => 1,
            'waktu_mulai'     => now()->subHour(),
            'waktu_selesai'   => now()->subMinutes(30),
        ]);

        ExamAttempt::factory()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta2->id,
            'status'          => ExamAttempt::STATUS_DISKUALIFIKASI,
            'nilai_akhir'     => 20,
            'attempt_ke'      => 1,
            'waktu_mulai'     => now()->subHour(),
            'waktu_selesai'   => now()->subMinutes(30),
        ]);

        $rekap = $this->reportService->rekapNilai($this->session->id);

        $this->assertCount(2, $rekap);
    }

    public function test_rekap_nilai_calculates_durasi_detik(): void
    {
        $peserta = User::factory()->create(['level' => 1]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta->id,
            'nilai_akhir'     => 90,
            'attempt_ke'      => 1,
            'waktu_mulai'     => now()->subMinutes(45),
            'waktu_selesai'   => now()->subMinutes(5),
        ]);

        $rekap = $this->reportService->rekapNilai($this->session->id);

        $this->assertNotNull($rekap->first()->durasi_detik);
        $this->assertGreaterThan(0, $rekap->first()->durasi_detik);
    }

    public function test_rekap_nilai_result_has_expected_fields(): void
    {
        $peserta = User::factory()->create(['level' => 1, 'name' => 'Budi', 'nomor_peserta' => '001']);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta->id,
            'nilai_akhir'     => 85,
            'jumlah_benar'    => 17,
            'jumlah_salah'    => 2,
            'jumlah_kosong'   => 1,
            'attempt_ke'      => 1,
        ]);

        $item = $this->reportService->rekapNilai($this->session->id)->first();

        $this->assertObjectHasProperty('no', $item);
        $this->assertObjectHasProperty('nama', $item);
        $this->assertObjectHasProperty('nomor_peserta', $item);
        $this->assertObjectHasProperty('nilai_akhir', $item);
        $this->assertObjectHasProperty('jumlah_benar', $item);
        $this->assertObjectHasProperty('jumlah_salah', $item);
        $this->assertObjectHasProperty('jumlah_kosong', $item);
        $this->assertObjectHasProperty('status', $item);
        $this->assertObjectHasProperty('attempt_ke', $item);
        $this->assertObjectHasProperty('durasi_detik', $item);
        $this->assertEquals('Budi', $item->nama);
        $this->assertEquals('001', $item->nomor_peserta);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // statistikNilai
    // ─────────────────────────────────────────────────────────────────────────

    public function test_statistik_nilai_returns_zeros_when_no_attempts(): void
    {
        $statistik = $this->reportService->statistikNilai($this->session->id);

        $this->assertEquals(0, $statistik['total_peserta']);
        $this->assertEquals(0.0, $statistik['rata_rata']);
        $this->assertEquals(0.0, $statistik['nilai_tertinggi']);
        $this->assertEquals(0.0, $statistik['nilai_terendah']);
        $this->assertEquals(0.0, $statistik['median']);
    }

    public function test_statistik_nilai_calculates_avg_max_min(): void
    {
        $peserta1 = User::factory()->create(['level' => 1]);
        $peserta2 = User::factory()->create(['level' => 1]);
        $peserta3 = User::factory()->create(['level' => 1]);

        foreach ([60.0, 80.0, 100.0] as $i => $nilai) {
            ExamAttempt::factory()->selesai()->create([
                'exam_session_id' => $this->session->id,
                'user_id'         => [$peserta1->id, $peserta2->id, $peserta3->id][$i],
                'nilai_akhir'     => $nilai,
                'attempt_ke'      => 1,
            ]);
        }

        $statistik = $this->reportService->statistikNilai($this->session->id);

        $this->assertEquals(3, $statistik['total_peserta']);
        $this->assertEquals(80.0, $statistik['rata_rata']);
        $this->assertEquals(100.0, $statistik['nilai_tertinggi']);
        $this->assertEquals(60.0, $statistik['nilai_terendah']);
        $this->assertEquals(80.0, $statistik['median']);
    }

    public function test_statistik_nilai_median_even_count(): void
    {
        $users = User::factory()->count(4)->create(['level' => 1]);
        $nilaiArr = [50.0, 60.0, 80.0, 90.0];

        foreach ($users as $i => $user) {
            ExamAttempt::factory()->selesai()->create([
                'exam_session_id' => $this->session->id,
                'user_id'         => $user->id,
                'nilai_akhir'     => $nilaiArr[$i],
                'attempt_ke'      => 1,
            ]);
        }

        $statistik = $this->reportService->statistikNilai($this->session->id);

        // median of [50, 60, 80, 90] = (60+80)/2 = 70
        $this->assertEquals(70.0, $statistik['median']);
    }

    public function test_statistik_nilai_ignores_null_nilai_akhir(): void
    {
        $peserta1 = User::factory()->create(['level' => 1]);
        $peserta2 = User::factory()->create(['level' => 1]);

        ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta1->id,
            'nilai_akhir'     => 80,
            'attempt_ke'      => 1,
        ]);

        ExamAttempt::factory()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta2->id,
            'status'          => ExamAttempt::STATUS_SELESAI,
            'nilai_akhir'     => null,
            'attempt_ke'      => 1,
            'waktu_mulai'     => now()->subHour(),
            'waktu_selesai'   => now()->subMinutes(10),
        ]);

        $statistik = $this->reportService->statistikNilai($this->session->id);

        // total_peserta = 2 (keduanya masuk rekap), tapi rata-rata hanya berdasar yg punya nilai
        $this->assertEquals(2, $statistik['total_peserta']);
        $this->assertEquals(80.0, $statistik['rata_rata']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // rekapKehadiran
    // ─────────────────────────────────────────────────────────────────────────

    public function test_rekap_kehadiran_returns_empty_when_no_participants(): void
    {
        $kehadiran = $this->reportService->rekapKehadiran($this->session->id);

        $this->assertEquals(0, $kehadiran['total']);
        $this->assertEquals(0, $kehadiran['selesai']);
        $this->assertEquals(0, $kehadiran['sedang']);
        $this->assertEquals(0, $kehadiran['belum']);
        $this->assertEquals(0, $kehadiran['diskualifikasi']);
    }

    public function test_rekap_kehadiran_counts_statuses_correctly(): void
    {
        $statuses = [
            ExamSessionParticipant::STATUS_SELESAI,
            ExamSessionParticipant::STATUS_SELESAI,
            ExamSessionParticipant::STATUS_SEDANG,
            ExamSessionParticipant::STATUS_BELUM,
            ExamSessionParticipant::STATUS_DISKUALIFIKASI,
        ];

        foreach ($statuses as $status) {
            $user = User::factory()->create(['level' => 1]);
            $this->session->participants()->create([
                'user_id' => $user->id,
                'status'  => $status,
            ]);
        }

        $kehadiran = $this->reportService->rekapKehadiran($this->session->id);

        $this->assertEquals(5, $kehadiran['total']);
        $this->assertEquals(2, $kehadiran['selesai']);
        $this->assertEquals(1, $kehadiran['sedang']);
        $this->assertEquals(1, $kehadiran['belum']);
        $this->assertEquals(1, $kehadiran['diskualifikasi']);
    }

    public function test_rekap_kehadiran_list_contains_all_participants(): void
    {
        $users = User::factory()->count(3)->create(['level' => 1]);

        foreach ($users as $user) {
            $this->session->participants()->create([
                'user_id' => $user->id,
                'status'  => ExamSessionParticipant::STATUS_BELUM,
            ]);
        }

        $kehadiran = $this->reportService->rekapKehadiran($this->session->id);

        $this->assertCount(3, $kehadiran['list']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // statistikSoal
    // ─────────────────────────────────────────────────────────────────────────

    public function test_statistik_soal_returns_empty_for_missing_session(): void
    {
        $result = $this->reportService->statistikSoal(99999);

        $this->assertTrue($result->isEmpty());
    }

    public function test_statistik_soal_returns_one_entry_per_question(): void
    {
        $question1 = Question::factory()->create([
            'tipe'       => 'PG',
            'created_by' => $this->guru->id,
        ]);
        $question2 = Question::factory()->create([
            'tipe'       => 'PG',
            'created_by' => $this->guru->id,
        ]);

        $this->package->questions()->attach([
            $question1->id => ['urutan' => 1],
            $question2->id => ['urutan' => 2],
        ]);

        $statistik = $this->reportService->statistikSoal($this->session->id);

        $this->assertCount(2, $statistik);
    }

    public function test_statistik_soal_calculates_persen_benar(): void
    {
        $question = Question::factory()->create([
            'tipe'       => 'PG',
            'bobot'      => 10,
            'created_by' => $this->guru->id,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'kode_opsi'   => 'A',
            'is_correct'  => true,
        ]);

        $this->package->questions()->attach([$question->id => ['urutan' => 1]]);

        // 2 peserta selesai: 1 benar, 1 salah
        $peserta1 = User::factory()->create(['level' => 1]);
        $peserta2 = User::factory()->create(['level' => 1]);

        $attempt1 = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta1->id,
        ]);
        $attempt2 = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $peserta2->id,
        ]);

        AttemptQuestion::factory()->create([
            'attempt_id'  => $attempt1->id,
            'question_id' => $question->id,
            'is_correct'  => true,
        ]);
        AttemptQuestion::factory()->create([
            'attempt_id'  => $attempt2->id,
            'question_id' => $question->id,
            'is_correct'  => false,
        ]);

        $statistik = $this->reportService->statistikSoal($this->session->id);
        $soal = $statistik->first();

        $this->assertEquals(2, $soal->total_jawab);
        $this->assertEquals(1, $soal->jumlah_benar);
        $this->assertEquals(50.0, $soal->persen_benar);
    }

    public function test_statistik_soal_result_has_expected_fields(): void
    {
        $question = Question::factory()->create([
            'tipe'       => 'PG',
            'created_by' => $this->guru->id,
        ]);
        $this->package->questions()->attach([$question->id => ['urutan' => 1]]);

        $statistik = $this->reportService->statistikSoal($this->session->id);
        $soal = $statistik->first();

        $this->assertObjectHasProperty('no', $soal);
        $this->assertObjectHasProperty('question', $soal);
        $this->assertObjectHasProperty('tipe', $soal);
        $this->assertObjectHasProperty('total_jawab', $soal);
        $this->assertObjectHasProperty('jumlah_benar', $soal);
        $this->assertObjectHasProperty('jumlah_salah', $soal);
        $this->assertObjectHasProperty('jumlah_kosong', $soal);
        $this->assertObjectHasProperty('persen_benar', $soal);
        $this->assertObjectHasProperty('distribusi_opsi', $soal);
    }

    public function test_statistik_soal_distribusi_opsi_for_pg_question(): void
    {
        $question = Question::factory()->create([
            'tipe'       => 'PG',
            'created_by' => $this->guru->id,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'kode_opsi'   => 'A',
            'teks_opsi'   => 'Opsi A',
            'is_correct'  => true,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'kode_opsi'   => 'B',
            'teks_opsi'   => 'Opsi B',
            'is_correct'  => false,
        ]);

        $this->package->questions()->attach([$question->id => ['urutan' => 1]]);

        $statistik = $this->reportService->statistikSoal($this->session->id);
        $distribusi = $statistik->first()->distribusi_opsi;

        $this->assertArrayHasKey('A', $distribusi);
        $this->assertArrayHasKey('B', $distribusi);
        $this->assertTrue($distribusi['A']['correct']);
        $this->assertFalse($distribusi['B']['correct']);
    }
}
