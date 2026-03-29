<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\ExamPackageResource;
use App\Filament\Resources\ExamSessionResource;
use App\Filament\Resources\GradingResource;
use App\Filament\Resources\LaporanResource;
use App\Filament\Resources\QuestionResource;
use App\Filament\Resources\RombelResource;
use App\Filament\Resources\UserResource;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi access policy dan query-scoping hasil FASE 10.
 * Menggunakan static method langsung (bukan HTTP Filament page)
 * karena resource Filament adalah Livewire — policy method-nya lebih cepat & stabil diuji secara langsung.
 */
class FilamentResourcePolicyTest extends TestCase
{
  use RefreshDatabase;

  protected User $superAdmin;
  protected User $admin;
  protected User $guru;
  protected User $guru2;
  protected User $peserta;

  public function setUp(): void
  {
    parent::setUp();

    $this->superAdmin = User::factory()->create(['level' => User::LEVEL_SUPER_ADMIN]);
    $this->admin      = User::factory()->create(['level' => User::LEVEL_ADMIN]);
    $this->guru       = User::factory()->create(['level' => User::LEVEL_GURU]);
    $this->guru2      = User::factory()->create(['level' => User::LEVEL_GURU]);
    $this->peserta    = User::factory()->create(['level' => User::LEVEL_PESERTA]);
  }

  // ── UserResource: hanya Admin+ ───────────────────────────────────────────

  public function test_user_resource_canViewAny_requires_admin(): void
  {
    $this->actingAs($this->peserta);
    $this->assertFalse(UserResource::canViewAny());

    $this->actingAs($this->guru);
    $this->assertFalse(UserResource::canViewAny());

    $this->actingAs($this->admin);
    $this->assertTrue(UserResource::canViewAny());

    $this->actingAs($this->superAdmin);
    $this->assertTrue(UserResource::canViewAny());
  }

  // ── RombelResource: hanya Admin+ ────────────────────────────────────────

  public function test_rombel_resource_canViewAny_requires_admin(): void
  {
    $this->actingAs($this->peserta);
    $this->assertFalse(RombelResource::canViewAny());

    $this->actingAs($this->guru);
    $this->assertFalse(RombelResource::canViewAny());

    $this->actingAs($this->admin);
    $this->assertTrue(RombelResource::canViewAny());
  }

  // ── LaporanResource: Guru+ ───────────────────────────────────────────────

  public function test_laporan_resource_canAccess_requires_guru(): void
  {
    $this->actingAs($this->peserta);
    $this->assertFalse(LaporanResource::canAccess());

    $this->actingAs($this->guru);
    $this->assertTrue(LaporanResource::canAccess());

    $this->actingAs($this->admin);
    $this->assertTrue(LaporanResource::canAccess());
  }

  // ── GradingResource: Guru+ ───────────────────────────────────────────────

  public function test_grading_resource_canAccess_requires_guru(): void
  {
    $this->actingAs($this->peserta);
    $this->assertFalse(GradingResource::canAccess());

    $this->actingAs($this->guru);
    $this->assertTrue(GradingResource::canAccess());

    $this->actingAs($this->admin);
    $this->assertTrue(GradingResource::canAccess());
  }

  // ── LaporanResource::getEloquentQuery() scoping ──────────────────────────

  public function test_laporan_query_guru_only_sees_own_sessions(): void
  {
    $package = ExamPackage::factory()->create(['created_by' => $this->guru->id]);

    ExamSession::factory()->create([
      'exam_package_id' => $package->id,
      'created_by'      => $this->guru->id,
      'status'          => ExamSession::STATUS_AKTIF,
    ]);
    ExamSession::factory()->create([
      'exam_package_id' => $package->id,
      'created_by'      => $this->guru2->id,
      'status'          => ExamSession::STATUS_SELESAI,
    ]);

    $this->actingAs($this->guru);
    $count = LaporanResource::getEloquentQuery()->count();

    $this->assertEquals(1, $count, 'Guru seharusnya hanya melihat 1 sesi miliknya sendiri.');
  }

  public function test_laporan_query_admin_sees_all_sessions(): void
  {
    $package = ExamPackage::factory()->create(['created_by' => $this->guru->id]);

    ExamSession::factory()->count(3)->create([
      'exam_package_id' => $package->id,
      'created_by'      => $this->guru->id,
      'status'          => ExamSession::STATUS_AKTIF,
    ]);
    ExamSession::factory()->create([
      'exam_package_id' => $package->id,
      'created_by'      => $this->guru2->id,
      'status'          => ExamSession::STATUS_SELESAI,
    ]);

    $this->actingAs($this->admin);
    $count = LaporanResource::getEloquentQuery()->count();

    $this->assertEquals(4, $count, 'Admin seharusnya melihat semua 4 sesi.');
  }

  // ── ExamSessionResource::getEloquentQuery() scoping ─────────────────────

  public function test_exam_session_query_guru_only_sees_own(): void
  {
    $package = ExamPackage::factory()->create(['created_by' => $this->guru->id]);

    ExamSession::factory()->create(['exam_package_id' => $package->id, 'created_by' => $this->guru->id]);
    ExamSession::factory()->count(2)->create(['exam_package_id' => $package->id, 'created_by' => $this->guru2->id]);

    $this->actingAs($this->guru);
    $this->assertEquals(1, ExamSessionResource::getEloquentQuery()->count());

    $this->actingAs($this->admin);
    $this->assertEquals(3, ExamSessionResource::getEloquentQuery()->count());
  }

  // ── ExamPackageResource::getEloquentQuery() scoping ─────────────────────

  public function test_exam_package_query_guru_only_sees_own(): void
  {
    ExamPackage::factory()->count(2)->create(['created_by' => $this->guru->id]);
    ExamPackage::factory()->create(['created_by' => $this->guru2->id]);

    $this->actingAs($this->guru);
    $this->assertEquals(2, ExamPackageResource::getEloquentQuery()->count());

    $this->actingAs($this->admin);
    $this->assertEquals(3, ExamPackageResource::getEloquentQuery()->count());
  }

  // ── GradingResource::getEloquentQuery() scoping ──────────────────────────

  public function test_grading_query_only_shows_manual_sessions(): void
  {
    $manualPackage   = ExamPackage::factory()->create(['created_by' => $this->guru->id, 'grading_mode' => ExamPackage::GRADING_MANUAL]);
    $realtimePackage = ExamPackage::factory()->create(['created_by' => $this->guru->id, 'grading_mode' => ExamPackage::GRADING_REALTIME]);

    ExamSession::factory()->create(['exam_package_id' => $manualPackage->id,   'created_by' => $this->guru->id]);
    ExamSession::factory()->create(['exam_package_id' => $realtimePackage->id, 'created_by' => $this->guru->id]);

    $this->actingAs($this->guru);
    $this->assertEquals(1, GradingResource::getEloquentQuery()->count(), 'Grading hanya menampilkan sesi dengan paket manual.');
  }

  public function test_grading_query_guru_only_sees_own_manual_sessions(): void
  {
    $manualPackage = ExamPackage::factory()->create(['created_by' => $this->guru->id, 'grading_mode' => ExamPackage::GRADING_MANUAL]);

    ExamSession::factory()->create(['exam_package_id' => $manualPackage->id, 'created_by' => $this->guru->id]);
    ExamSession::factory()->create(['exam_package_id' => $manualPackage->id, 'created_by' => $this->guru2->id]);

    $this->actingAs($this->guru);
    $this->assertEquals(1, GradingResource::getEloquentQuery()->count());

    $this->actingAs($this->admin);
    $this->assertEquals(2, GradingResource::getEloquentQuery()->count());
  }

  // ── QuestionResource::getEloquentQuery() scoping ─────────────────────────

  public function test_question_query_guru_only_sees_own(): void
  {
    Question::factory()->count(3)->create(['created_by' => $this->guru->id]);
    Question::factory()->count(2)->create(['created_by' => $this->guru2->id]);

    $this->actingAs($this->guru);
    $this->assertEquals(3, QuestionResource::getEloquentQuery()->count());

    $this->actingAs($this->admin);
    $this->assertEquals(5, QuestionResource::getEloquentQuery()->count());
  }
}
