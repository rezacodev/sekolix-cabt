<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionKeyword;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportSoalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected string $tmpDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->guru   = User::factory()->create(['level' => 2]);
        $this->tmpDir = storage_path('app/test_imports');

        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }
    }

    public function tearDown(): void
    {
        // Bersihkan file xlsx sementara setelah setiap test
        foreach (glob($this->tmpDir . '/*.xlsx') as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: buat file xlsx dari array rows
    // ─────────────────────────────────────────────────────────────────────────

    private function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $headers = [
            'tipe_soal', 'teks_soal', 'kategori', 'kesulitan', 'bobot',
            'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e', 'kunci',
        ];

        // Baris 1: header
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Baris 2+: data
        foreach ($rows as $rowIdx => $rowData) {
            foreach ($headers as $col => $key) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $rowData[$key] ?? '');
            }
        }

        $path   = $this->tmpDir . '/' . uniqid('test_', true) . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Import soal PG valid
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_soal_pg_creates_question(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'PG',
            'teks_soal' => 'Soal pilihan ganda',
            'kategori'  => '',
            'kesulitan' => 'sedang',
            'bobot'     => 1,
            'opsi_a'    => 'Opsi A',
            'opsi_b'    => 'Opsi B',
            'opsi_c'    => 'Opsi C',
            'opsi_d'    => 'Opsi D',
            'opsi_e'    => '',
            'kunci'     => 'A',
        ]]);
        $result = ImportService::importSoal($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['errors']);

        $this->assertDatabaseHas('questions', [
            'tipe'      => 'PG',
            'teks_soal' => 'Soal pilihan ganda',
        ]);
    }

    public function test_import_soal_pg_creates_options(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'PG',
            'teks_soal' => 'Soal dengan opsi',
            'kategori'  => '',
            'kesulitan' => 'mudah',
            'bobot'     => 2,
            'opsi_a'    => 'Pilihan A',
            'opsi_b'    => 'Pilihan B',
            'opsi_c'    => 'Pilihan C',
            'opsi_d'    => 'Pilihan D',
            'opsi_e'    => '',
            'kunci'     => 'B',
        ]]);
        $result = ImportService::importSoal($path);

        $this->assertEquals(1, $result['imported']);

        $question = Question::where('teks_soal', 'Soal dengan opsi')->first();
        $this->assertNotNull($question);

        // Harus ada 4 opsi (A,B,C,D) — opsi_e kosong
        $this->assertCount(4, $question->options);
    }

    public function test_import_soal_pg_marks_correct_answer(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'PG',
            'teks_soal' => 'Soal kunci jawaban',
            'kategori'  => '',
            'kesulitan' => 'sulit',
            'bobot'     => 3,
            'opsi_a'    => 'Jawaban A',
            'opsi_b'    => 'Jawaban B',
            'opsi_c'    => 'Jawaban C',
            'opsi_d'    => 'Jawaban D',
            'opsi_e'    => '',
            'kunci'     => 'C',
        ]]);
        ImportService::importSoal($path);

        $question       = Question::where('teks_soal', 'Soal kunci jawaban')->first();
        $correctOption  = $question->options()->where('kode_opsi', 'C')->first();
        $incorrectCount = $question->options()->where('is_correct', false)->count();

        $this->assertTrue((bool) $correctOption->is_correct);
        $this->assertEquals(3, $incorrectCount);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Import soal ISIAN — membuat keywords
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_soal_isian_creates_keywords(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'ISIAN',
            'teks_soal' => 'Isi dengan jawaban yang tepat',
            'kategori'  => '',
            'kesulitan' => 'sedang',
            'bobot'     => 1,
            'opsi_a'    => '', 'opsi_b' => '', 'opsi_c' => '',
            'opsi_d'    => '', 'opsi_e' => '',
            'kunci'     => 'jawaban satu, jawaban dua',
        ]]);
        $result = ImportService::importSoal($path);

        $this->assertEquals(1, $result['imported']);

        $question = Question::where('tipe', 'ISIAN')->first();
        $this->assertNotNull($question);

        $keywords = QuestionKeyword::where('question_id', $question->id)->pluck('keyword')->toArray();
        // QuestionsImport menerapkan strtoupper() pada kunci sebelum disimpan
        $this->assertContains('JAWABAN SATU', $keywords);
        $this->assertContains('JAWABAN DUA', $keywords);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Kategori otomatis dibuat jika belum ada
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_soal_creates_category_if_not_exists(): void
    {
        $this->actingAs($this->guru);

        $this->assertDatabaseMissing('categories', ['nama' => 'Matematika Baru']);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'PG',
            'teks_soal' => 'Soal matematika',
            'kategori'  => 'Matematika Baru',
            'kesulitan' => 'sedang',
            'bobot'     => 1,
            'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
            'opsi_d'    => 'D', 'opsi_e' => '',
            'kunci'     => 'A',
        ]]);
        $result = ImportService::importSoal($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertDatabaseHas('categories', ['nama' => 'Matematika Baru']);

        $category   = Category::where('nama', 'Matematika Baru')->first();
        $question   = Question::where('teks_soal', 'Soal matematika')->first();
        $this->assertEquals($category->id, $question->kategori_id);
    }

    public function test_import_soal_reuses_existing_category(): void
    {
        $this->actingAs($this->guru);

        $category = Category::create(['nama' => 'IPA']);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'PG',
            'teks_soal' => 'Soal IPA',
            'kategori'  => 'IPA',
            'kesulitan' => 'mudah',
            'bobot'     => 1,
            'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
            'opsi_d'    => 'D', 'opsi_e' => '',
            'kunci'     => 'A',
        ]]);
        ImportService::importSoal($path);

        // Hanya boleh ada satu kategori IPA
        $this->assertEquals(1, Category::where('nama', 'IPA')->count());

        $question = Question::where('teks_soal', 'Soal IPA')->first();
        $this->assertEquals($category->id, $question->kategori_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validasi — field wajib
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_soal_rejects_row_missing_teks_soal(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'PG',
            'teks_soal' => '',         // kosong
            'kategori'  => '',
            'kesulitan' => 'sedang',
            'bobot'     => 1,
            'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
            'opsi_d'    => 'D', 'opsi_e' => '',
            'kunci'     => 'A',
        ]]);
        $result = ImportService::importSoal($path);

        $this->assertEquals(0, $result['imported']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Baris 2', $result['errors'][0]);
    }

    public function test_import_soal_rejects_invalid_tipe_soal(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'INVALID',
            'teks_soal' => 'Soal invalid tipe',
            'kategori'  => '',
            'kesulitan' => 'sedang',
            'bobot'     => 1,
            'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
            'opsi_d'    => 'D', 'opsi_e' => '',
            'kunci'     => 'A',
        ]]);
        $result = ImportService::importSoal($path);

        $this->assertEquals(0, $result['imported']);
        $this->assertCount(1, $result['errors']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Baris campuran: sukses dan gagal
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_soal_partial_success_with_mixed_rows(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([
            [
                'tipe_soal' => 'PG',
                'teks_soal' => 'Soal valid pertama',
                'kategori'  => '',
                'kesulitan' => 'mudah',
                'bobot'     => 1,
                'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
                'opsi_d'    => 'D', 'opsi_e' => '',
                'kunci'     => 'A',
            ],
            [
                'tipe_soal' => 'TIDAK_ADA',  // tipe salah
                'teks_soal' => 'Soal invalid',
                'kategori'  => '',
                'kesulitan' => 'sedang',
                'bobot'     => 1,
                'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
                'opsi_d'    => 'D', 'opsi_e' => '',
                'kunci'     => 'A',
            ],
            [
                'tipe_soal' => 'PG',
                'teks_soal' => 'Soal valid ketiga',
                'kategori'  => '',
                'kesulitan' => 'sulit',
                'bobot'     => 2,
                'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
                'opsi_d'    => 'D', 'opsi_e' => '',
                'kunci'     => 'B',
            ],
        ]);
        $result = ImportService::importSoal($path);

        $this->assertEquals(2, $result['imported']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Baris 3', $result['errors'][0]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Import multiple soal sekaligus
    // ─────────────────────────────────────────────────────────────────────────

    public function test_import_soal_returns_correct_imported_count(): void
    {
        $this->actingAs($this->guru);

        $rows = array_map(fn($i) => [
            'tipe_soal' => 'PG',
            'teks_soal' => "Soal nomor {$i}",
            'kategori'  => '',
            'kesulitan' => 'sedang',
            'bobot'     => 1,
            'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
            'opsi_d'    => 'D', 'opsi_e' => '',
            'kunci'     => 'A',
        ], range(1, 5));

        $path   = $this->makeXlsx($rows);
        $result = ImportService::importSoal($path);

        $this->assertEquals(5, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    public function test_import_soal_sets_created_by_to_current_user(): void
    {
        $this->actingAs($this->guru);

        $path   = $this->makeXlsx([[
            'tipe_soal' => 'PG',
            'teks_soal' => 'Soal dengan created_by',
            'kategori'  => '',
            'kesulitan' => 'sedang',
            'bobot'     => 1,
            'opsi_a'    => 'A', 'opsi_b' => 'B', 'opsi_c' => 'C',
            'opsi_d'    => 'D', 'opsi_e' => '',
            'kunci'     => 'A',
        ]]);
        ImportService::importSoal($path);

        $this->assertDatabaseHas('questions', [
            'teks_soal'  => 'Soal dengan created_by',
            'created_by' => $this->guru->id,
        ]);
    }
}
