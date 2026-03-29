<?php

namespace Tests\Feature\Exam;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Testing\Fakes\StorageFake;
use Tests\TestCase;

class ExamFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected User $peserta;
    protected ExamSession $session;
    protected ExamPackage $package;
    protected ExamAttempt $attempt;
    protected Question $question;
    protected FilesystemAdapter $disk;

    public function setUp(): void
    {
        parent::setUp();

        $this->disk = Storage::fake('local');

        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);

        $this->question = Question::factory()->create(['tipe' => Question::TIPE_URAIAN]);

        $this->package = ExamPackage::factory()->create([
            'created_by'   => $this->guru->id,
            'grading_mode' => ExamPackage::GRADING_REALTIME,
        ]);
        $this->package->questions()->attach($this->question, ['urutan' => 1]);

        $this->session = ExamSession::factory()->create([
            'exam_package_id' => $this->package->id,
            'created_by'      => $this->guru->id,
            'status'          => ExamSession::STATUS_AKTIF,
        ]);

        $this->session->participants()->create([
            'user_id' => $this->peserta->id,
            'status'  => ExamSessionParticipant::STATUS_BELUM,
        ]);

        $this->attempt = ExamAttempt::factory()->berlangsung()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
            'waktu_mulai'     => now(),
        ]);

        // Buat AttemptQuestion agar upload bisa update jawaban_file
        $this->attempt->questions()->create([
            'question_id'     => $this->question->id,
            'urutan'          => 1,
            'jawaban_peserta' => null,
        ]);
    }

    public function test_peserta_can_upload_uraian_file(): void
    {
        $file = UploadedFile::fake()->image('jawaban.jpg', 200, 200);

        $response = $this->actingAs($this->peserta)
            ->postJson(route('ujian.upload-file'), [
                'attempt_id'  => $this->attempt->id,
                'question_id' => $this->question->id,
                'file'        => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['path', 'url']);

        // Pastikan file tersimpan di private disk
        $path = $response->json('path');
        $this->disk->assertExists($path);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('jawaban.jpg');

        $this->postJson(route('ujian.upload-file'), [
            'attempt_id'  => $this->attempt->id,
            'question_id' => $this->question->id,
            'file'        => $file,
        ])->assertUnauthorized();
    }

    public function test_upload_validates_mime_type(): void
    {
        // File .exe harus ditolak
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.upload-file'), [
                'attempt_id'  => $this->attempt->id,
                'question_id' => $this->question->id,
                'file'        => $file,
            ])
            ->assertUnprocessable();
    }

    public function test_upload_requires_attempt_id(): void
    {
        $file = UploadedFile::fake()->image('jawaban.jpg');

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.upload-file'), [
                'question_id' => $this->question->id,
                'file'        => $file,
            ])
            ->assertUnprocessable();
    }

    public function test_upload_returns_403_for_finished_attempt(): void
    {
        $finishedAttempt = ExamAttempt::factory()->selesai()->create([
            'exam_session_id' => $this->session->id,
            'user_id'         => $this->peserta->id,
        ]);

        $file = UploadedFile::fake()->image('jawaban.jpg');

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.upload-file'), [
                'attempt_id'  => $finishedAttempt->id,
                'question_id' => $this->question->id,
                'file'        => $file,
            ])
            ->assertForbidden();
    }

    public function test_upload_updates_attempt_question_jawaban_file(): void
    {
        $file = UploadedFile::fake()->image('jawaban.png');

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.upload-file'), [
                'attempt_id'  => $this->attempt->id,
                'question_id' => $this->question->id,
                'file'        => $file,
            ])
            ->assertOk();

        $aq = $this->attempt->questions()->where('question_id', $this->question->id)->first();
        $this->assertNotNull($aq->jawaban_file);
    }

    public function test_upload_replaces_previous_file(): void
    {
        $this->disk->put("uraian/{$this->attempt->id}/old.jpg", 'old content');

        $aq = $this->attempt->questions()->where('question_id', $this->question->id)->first();
        $aq->update(['jawaban_file' => "uraian/{$this->attempt->id}/old.jpg"]);

        $file = UploadedFile::fake()->image('baru.jpg');

        $this->actingAs($this->peserta)
            ->postJson(route('ujian.upload-file'), [
                'attempt_id'  => $this->attempt->id,
                'question_id' => $this->question->id,
                'file'        => $file,
            ])
            ->assertOk();

        // File lama harus sudah dihapus
        $this->disk->assertMissing("uraian/{$this->attempt->id}/old.jpg");
    }

    public function test_serve_file_requires_authentication(): void
    {
        $this->get(route('ujian.file.uraian', [
            'attemptId' => $this->attempt->id,
            'filename'  => 'jawaban.jpg',
        ]))->assertRedirect(route('login'));
    }

    public function test_serve_file_prevents_path_traversal(): void
    {
        // Controller memanggil basename($filename) sebelum mengakses storage.
        // basename('../../../sensitive.jpg') === 'sensitive.jpg'
        // File 'uraian/{attemptId}/sensitive.jpg' tidak ada → 404.
        // Ini memverifikasi bahwa path traversal tidak bisa lolos.
        $this->actingAs($this->peserta)
            ->get(route('ujian.file.uraian', [
                'attemptId' => $this->attempt->id,
                'filename'  => '../../../sensitive.jpg',
            ]))
            ->assertNotFound();
    }
}
