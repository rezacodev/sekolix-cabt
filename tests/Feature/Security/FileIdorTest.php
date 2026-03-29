<?php

namespace Tests\Feature\Security;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test IDOR prevention: peserta tidak bisa akses file orang lain.
 */
class FileIdorTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected User $peserta;
    protected User $lain;
    protected ExamAttempt $attemptLain;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);
        $this->lain    = User::factory()->create(['level' => 1]);

        $package = ExamPackage::factory()
            ->has(Question::factory()->count(2))
            ->create([
                'created_by'   => $this->guru->id,
                'grading_mode' => ExamPackage::GRADING_REALTIME,
            ]);

        $session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $session->participants()->create([
            'user_id' => $this->lain->id,
            'status'  => ExamSessionParticipant::STATUS_SELESAI,
        ]);

        $this->attemptLain = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $session->id,
            'user_id'         => $this->lain->id,
        ]);

        // Simpan file fiktif atas nama attempt orang lain
        Storage::disk('local')->put(
            "uraian/{$this->attemptLain->id}/rahasia.jpg",
            'sensitive content'
        );
    }

    public function test_peserta_cannot_access_other_user_uraian_file(): void
    {
        $this->actingAs($this->peserta)
            ->get(route('ujian.file.uraian', [
                'attemptId' => $this->attemptLain->id,
                'filename'  => 'rahasia.jpg',
            ]))
            ->assertForbidden();
    }

    public function test_guru_can_access_any_uraian_file(): void
    {
        Storage::disk('local')->put(
            "uraian/{$this->attemptLain->id}/rahasia.jpg",
            'content'
        );

        $this->actingAs($this->guru)
            ->get(route('ujian.file.uraian', [
                'attemptId' => $this->attemptLain->id,
                'filename'  => 'rahasia.jpg',
            ]))
            ->assertOk();
    }

    public function test_owner_can_access_own_uraian_file(): void
    {
        $package = ExamPackage::factory()
            ->has(Question::factory()->count(1))
            ->create([
                'created_by'   => $this->guru->id,
                'grading_mode' => ExamPackage::GRADING_REALTIME,
            ]);

        $session = ExamSession::factory()->create([
            'exam_package_id' => $package->id,
            'created_by'      => $this->guru->id,
        ]);

        $session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);

        $attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        Storage::disk('local')->put("uraian/{$attempt->id}/milik_saya.jpg", 'my content');

        $this->actingAs($this->peserta)
            ->get(route('ujian.file.uraian', [
                'attemptId' => $attempt->id,
                'filename'  => 'milik_saya.jpg',
            ]))
            ->assertOk();
    }

    public function test_path_traversal_is_blocked(): void
    {
        // Coba akses file di luar folder ujian dengan ../
        $this->actingAs($this->peserta)
            ->get(route('ujian.file.uraian', [
                'attemptId' => $this->attemptLain->id,
                'filename'  => '../../../etc/passwd',
            ]))
            ->assertNotFound();
    }

    public function test_file_idor_via_jawab_endpoint(): void
    {
        // Coba AJAX simpan jawaban attempt orang lain
        $session2 = ExamSession::factory()->create([
            'exam_package_id' => ExamPackage::factory()
                ->has(Question::factory()->count(1))
                ->create(['created_by' => $this->guru->id, 'grading_mode' => ExamPackage::GRADING_REALTIME])
                ->id,
            'created_by' => $this->guru->id,
            'status'     => ExamSession::STATUS_AKTIF,
        ]);

        $session2->participants()->create([
            'user_id' => $this->lain->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);

        $lainAttempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $session2->id,
            'user_id'         => $this->lain->id,
            'waktu_mulai'     => now(),
        ]);

        $question = $session2->package->questions->first();

        // Peserta (bukan pemilik) coba simpan jawaban
        $this->actingAs($this->peserta)
            ->postJson(route('ujian.jawab'), [
                'attempt_id'  => $lainAttempt->id,
                'question_id' => $question->id,
                'jawaban'     => 'A',
            ])
            ->assertForbidden();
    }

    public function test_file_upload_idor_blocked(): void
    {
        // Coba upload file ke attempt orang lain
        $session2 = ExamSession::factory()->create([
            'exam_package_id' => ExamPackage::factory()
                ->has(Question::factory()->count(1))
                ->create(['created_by' => $this->guru->id, 'grading_mode' => ExamPackage::GRADING_REALTIME])
                ->id,
            'created_by' => $this->guru->id,
            'status'     => ExamSession::STATUS_AKTIF,
        ]);

        $session2->participants()->create([
            'user_id' => $this->lain->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);

        $lainAttempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $session2->id,
            'user_id'         => $this->lain->id,
            'waktu_mulai'     => now(),
        ]);

        $question = $session2->package->questions->first();

        $file = UploadedFile::fake()->image('hack.jpg');

        // Peserta coba upload ke attempt orang lain → harus 404 (firstOrFail by owner)
        $this->actingAs($this->peserta)
            ->postJson(route('ujian.upload-file'), [
                'attempt_id'  => $lainAttempt->id,
                'question_id' => $question->id,
                'file'        => $file,
            ])
            ->assertNotFound();
    }
}
