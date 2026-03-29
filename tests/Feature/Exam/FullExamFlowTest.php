<?php

namespace Tests\Feature\Exam;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration test: alur ujian penuh dari halaman konfirmasi hingga hasil.
 */
class FullExamFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected User $peserta;
    protected ExamPackage $package;
    protected ExamSession $session;
    protected Question $soal1;
    protected Question $soal2;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);

        $this->soal1 = Question::factory()->pg()->create();
        $this->soal2 = Question::factory()->pg()->create();

        $this->package = ExamPackage::factory()->create([
            'created_by'        => $this->guru->id,
            'grading_mode'      => ExamPackage::GRADING_REALTIME,
            'tampilkan_hasil'   => true,
            'tampilkan_review'  => true,
            'max_pengulangan'   => 0, // unlimited
            'acak_soal'         => false,
            'durasi_menit'      => 60,
        ]);
        $this->package->questions()->attach($this->soal1, ['urutan' => 1]);
        $this->package->questions()->attach($this->soal2, ['urutan' => 2]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
            'token_akses'     => null,
        ]);

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);
    }

    /**
     * Peserta dapat mengakses halaman konfirmasi sebelum mulai ujian.
     */
    public function test_peserta_can_view_halaman_konfirmasi(): void
    {
        $response = $this->actingAs($this->peserta)
            ->get(route('ujian.show', $this->session->id));

        $response->assertOk();
        $response->assertViewIs('peserta.konfirmasi');
    }

    /**
     * Ujian dimulai dengan POST /mulai dan redirect ke kerjakan.
     */
    public function test_peserta_can_mulai_ujian(): void
    {
        $response = $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $response->assertRedirect(route('ujian.kerjakan', $this->session->id));

        $this->assertDatabaseHas('exam_attempts', [
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'status'          => ExamAttempt::STATUS_BERLANGSUNG,
        ]);
    }

    /**
     * Halaman kerjakan tampil setelah attempt berlangsung.
     */
    public function test_peserta_can_access_halaman_kerjakan(): void
    {
        // Buat attempt terlebih dahulu
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $response = $this->actingAs($this->peserta)
            ->get(route('ujian.kerjakan', $this->session->id));

        $response->assertOk();
        $response->assertViewIs('peserta.ujian');
    }

    /**
     * Peserta dapat menjawab soal secara AJAX.
     */
    public function test_peserta_can_jawab_soal(): void
    {
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
            ->where('user_id', $this->peserta->id)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->first();

        $aq = $attempt->questions()->first();

        $response = $this->actingAs($this->peserta)
            ->postJson(route('ujian.jawab'), [
                'attempt_id'  => $attempt->id,
                'question_id' => $aq->question_id,
                'jawaban'     => 'A',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attempt_questions', [
            'attempt_id'       => $attempt->id,
            'question_id'      => $aq->question_id,
            'jawaban_peserta'  => 'A',
        ]);
    }

    /**
     * Status endpoint mengembalikan sisa waktu untuk attempt aktif.
     */
    public function test_status_returns_sisa_waktu(): void
    {
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
            ->where('user_id', $this->peserta->id)
            ->first();

        $response = $this->actingAs($this->peserta)
            ->getJson(route('ujian.status', $attempt->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['sisa_detik', 'status']);

        $this->assertGreaterThan(0, $response->json('sisa_detik'));
    }

    /**
     * Peserta dapat submit manual dan redirect ke hasil.
     */
    public function test_peserta_can_submit_ujian(): void
    {
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
            ->where('user_id', $this->peserta->id)
            ->first();

        $response = $this->actingAs($this->peserta)
            ->post(route('ujian.submit', $attempt->id));

        // Redirect ke hasil jika tampilkan_hasil = true
        $response->assertRedirect(route('ujian.hasil', $attempt->id));

        $this->assertDatabaseHas('exam_attempts', [
            'id'     => $attempt->id,
            'status' => ExamAttempt::STATUS_SELESAI,
        ]);
    }

    /**
     * Setelah submit, peserta dapat melihat halaman hasil.
     */
    public function test_peserta_can_view_hasil(): void
    {
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
            ->where('user_id', $this->peserta->id)
            ->first();

        $this->actingAs($this->peserta)
            ->post(route('ujian.submit', $attempt->id));

        $response = $this->actingAs($this->peserta)
            ->get(route('ujian.hasil', $attempt->id));

        $response->assertOk();
        $response->assertViewIs('peserta.hasil');
    }

    /**
     * Setelah submit, peserta dapat melihat halaman review jawaban.
     */
    public function test_peserta_can_view_review(): void
    {
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $attempt = ExamAttempt::where('exam_session_id', $this->session->id)
            ->where('user_id', $this->peserta->id)
            ->first();

        $this->actingAs($this->peserta)
            ->post(route('ujian.submit', $attempt->id));

        $response = $this->actingAs($this->peserta)
            ->get(route('ujian.review', $attempt->id));

        $response->assertOk();
        $response->assertViewIs('peserta.review');
    }

    /**
     * Jika sesi membutuhkan token, mulai tanpa token harus gagal.
     */
    public function test_mulai_fails_with_wrong_token(): void
    {
        $this->session->update(['token_akses' => 'RAHASIA123']);

        $response = $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id), [
                'token' => 'SALAH',
            ]);

        $response->assertSessionHasErrors('token');
        $this->assertDatabaseMissing('exam_attempts', [
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);
    }

    /**
     * Jika sesi membutuhkan token, mulai dengan token benar harus sukses.
     */
    public function test_mulai_succeeds_with_correct_token(): void
    {
        $this->session->update(['token_akses' => 'RAHASIA123']);

        $response = $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id), [
                'token' => 'RAHASIA123',
            ]);

        $response->assertRedirect(route('ujian.kerjakan', $this->session->id));
        $this->assertDatabaseHas('exam_attempts', [
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'status'          => ExamAttempt::STATUS_BERLANGSUNG,
        ]);
    }

    /**
     * Halaman konfirmasi redirect ke kerjakan jika ada attempt berlangsung.
     */
    public function test_show_redirects_to_kerjakan_if_attempt_aktif(): void
    {
        // Mulai ujian terlebih dahulu
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        // Akses halaman konfirmasi → harus redirect ke kerjakan
        $response = $this->actingAs($this->peserta)
            ->get(route('ujian.show', $this->session->id));

        $response->assertRedirect(route('ujian.kerjakan', $this->session->id));
    }

    /**
     * Peserta yang tidak terdaftar tidak bisa akses halaman ujian.
     */
    public function test_unregistered_peserta_cannot_start_exam(): void
    {
        $lain = User::factory()->create(['level' => 1]);

        $response = $this->actingAs($lain)
            ->get(route('ujian.show', $this->session->id));

        $response->assertNotFound();
    }

    /**
     * Attempt ke-2 dibuat jika max_pengulangan = 0 (unlimited).
     */
    public function test_unlimited_retry_allows_second_attempt(): void
    {
        $this->package->update(['max_pengulangan' => 0]);

        // Attempt pertama: mulai dan submit
        $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $a1 = ExamAttempt::where('exam_session_id', $this->session->id)
            ->where('user_id', $this->peserta->id)
            ->first();

        $this->actingAs($this->peserta)
            ->post(route('ujian.submit', $a1->id));

        // Attempt kedua
        $response = $this->actingAs($this->peserta)
            ->post(route('ujian.mulai', $this->session->id));

        $response->assertRedirect(route('ujian.kerjakan', $this->session->id));

        $this->assertEquals(
            2,
            ExamAttempt::where('exam_session_id', $this->session->id)
                ->where('user_id', $this->peserta->id)
                ->count()
        );
    }
}
