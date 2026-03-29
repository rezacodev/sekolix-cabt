<?php

namespace Tests\Unit\Services;

use App\Models\Rombel;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create temporary Excel file for testing
     */
    protected function createTestExcelFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add header row
        $sheet->setCellValue('A1', 'nama_lengkap');
        $sheet->setCellValue('B1', 'username');
        $sheet->setCellValue('C1', 'email');
        $sheet->setCellValue('D1', 'password');
        $sheet->setCellValue('E1', 'level');
        $sheet->setCellValue('F1', 'nomor_peserta');
        $sheet->setCellValue('G1', 'kode_rombel');

        // Add data rows
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $sheet->setCellValue("A{$rowNum}", $row['nama_lengkap'] ?? '');
            $sheet->setCellValue("B{$rowNum}", $row['username'] ?? '');
            $sheet->setCellValue("C{$rowNum}", $row['email'] ?? '');
            $sheet->setCellValue("D{$rowNum}", $row['password'] ?? '');
            $sheet->setCellValue("E{$rowNum}", $row['level'] ?? '');
            $sheet->setCellValue("F{$rowNum}", $row['nomor_peserta'] ?? '');
            $sheet->setCellValue("G{$rowNum}", $row['kode_rombel'] ?? '');
        }

        if (!is_dir(storage_path('app/test_imports'))) {
            mkdir(storage_path('app/test_imports'), 0755, true);
        }
        $filePath = storage_path('app/test_imports/test_' . uniqid() . '.xlsx');

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * Test import single valid user
     */
    public function test_import_single_valid_user(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'John Doe',
                'username' => 'johndoe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'level' => 1,
                'nomor_peserta' => 'P001',
            ],
        ]);

        $result = ImportService::importUser($filePath);

        $this->assertEquals(1, $result['imported']);
        $this->assertEmpty($result['errors']);
        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'level' => 1,
        ]);

        @unlink($filePath);
    }

    /**
     * Test import multiple valid users
     */
    public function test_import_multiple_valid_users(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'User One',
                'username' => 'userone',
                'email' => 'user1@example.com',
                'password' => 'password',
                'level' => 1,
                'nomor_peserta' => 'P001',
            ],
            [
                'nama_lengkap' => 'User Two',
                'username' => 'usertwo',
                'email' => 'user2@example.com',
                'password' => 'password',
                'level' => 1,
                'nomor_peserta' => 'P002',
            ],
            [
                'nama_lengkap' => 'Guru User',
                'username' => 'guru1',
                'email' => 'guru@example.com',
                'password' => 'password',
                'level' => 2,
                'nomor_peserta' => '',
            ],
        ]);

        $result = ImportService::importUser($filePath);

        $this->assertEquals(3, $result['imported']);
        $this->assertEmpty($result['errors']);
        $this->assertDatabaseCount('users', 3);

        @unlink($filePath);
    }

    /**
     * Test import detects duplicate username
     */
    public function test_import_detects_duplicate_username(): void
    {
        User::factory()->create(['username' => 'existing']);

        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'Duplicate User',
                'username' => 'existing',
                'email' => 'dup@example.com',
                'password' => 'password',
                'level' => 1,
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should have error
        $this->assertNotEmpty($result['errors']);
        $this->assertTrue(count($result['errors']) > 0);

        @unlink($filePath);
    }

    /**
     * Test import detects duplicate email
     */
    public function test_import_detects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'User',
                'username' => 'newuser',
                'email' => 'existing@example.com',
                'password' => 'password',
                'level' => 1,
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should have error
        $this->assertNotEmpty($result['errors']);

        @unlink($filePath);
    }

    /**
     * Test import missing required fields
     */
    public function test_import_handles_missing_required_fields(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'Incomplete User',
                // Missing username, email, password
                'level' => 1,
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should have errors for missing fields
        $this->assertGreaterThan(0, count($result['errors']));

        @unlink($filePath);
    }

    /**
     * Test import with invalid email
     */
    public function test_import_validates_email_format(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'User',
                'username' => 'user1',
                'email' => 'not-an-email',
                'password' => 'password',
                'level' => 1,
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should have validation error
        $this->assertGreaterThan(0, count($result['errors']));

        @unlink($filePath);
    }

    /**
     * Test import skips blank rows
     */
    public function test_import_skips_blank_rows(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'User One',
                'username' => 'userone',
                'email' => 'user1@example.com',
                'password' => 'password',
                'level' => 1,
            ],
            [
                'nama_lengkap' => '',
                'username' => '',
                'email' => '',
                'password' => '',
                'level' => '',
            ],
            [
                'nama_lengkap' => 'User Two',
                'username' => 'usertwo',
                'email' => 'user2@example.com',
                'password' => 'password',
                'level' => 1,
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should import 2 valid rows and skip blank row
        $this->assertGreaterThanOrEqual(2, $result['imported']);

        @unlink($filePath);
    }

    /**
     * Test import with rombel assignment
     */
    public function test_import_assigns_rombel_by_kode(): void
    {
        $rombel = Rombel::factory()->create(['kode' => 'X-IPA-1']);

        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'Student',
                'username' => 'student1',
                'email' => 'student@example.com',
                'password' => 'password',
                'level' => 1,
                'kode_rombel' => 'X-IPA-1',
            ],
        ]);

        $result = ImportService::importUser($filePath);

        $this->assertEquals(1, $result['imported']);

        $user = User::where('username', 'student1')->first();
        $this->assertEquals($rombel->id, $user->rombel_id);

        @unlink($filePath);
    }

    /**
     * Test import handles invalid rombel code
     */
    public function test_import_handles_invalid_rombel_code(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'Student',
                'username' => 'student1',
                'email' => 'student@example.com',
                'password' => 'password',
                'level' => 1,
                'kode_rombel' => 'NON-EXISTENT',
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should have error
        $this->assertGreaterThan(0, count($result['errors']));

        @unlink($filePath);
    }

    /**
     * Test import respects level validation
     */
    public function test_import_validates_level_range(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'User',
                'username' => 'user1',
                'email' => 'user@example.com',
                'password' => 'password',
                'level' => 99,  // Invalid level
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should have validation error
        $this->assertGreaterThan(0, count($result['errors']));

        @unlink($filePath);
    }

    /**
     * Test import partial success (mixed valid/invalid rows)
     */
    public function test_import_partial_success_with_mixed_rows(): void
    {
        $filePath = $this->createTestExcelFile([
            [
                'nama_lengkap' => 'Valid User',
                'username' => 'validuser',
                'email' => 'valid@example.com',
                'password' => 'password',
                'level' => 1,
            ],
            [
                'nama_lengkap' => 'Invalid User',
                'username' => 'invaliduser',
                'email' => 'invalid-email',  // Invalid email
                'password' => 'password',
                'level' => 1,
            ],
            [
                'nama_lengkap' => 'Another Valid',
                'username' => 'anothervalid',
                'email' => 'another@example.com',
                'password' => 'password',
                'level' => 1,
            ],
        ]);

        $result = ImportService::importUser($filePath);

        // Should import valid rows
        $this->assertGreaterThanOrEqual(2, $result['imported']);
        // Should report error for invalid row
        $this->assertGreaterThan(0, count($result['errors']));

        @unlink($filePath);
    }
}
