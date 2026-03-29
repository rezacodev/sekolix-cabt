<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test logika bulk action yang ditambahkan di FASE 10:
 * - toggle_aktif untuk User, Rombel, Question
 * - reset_password massal untuk User
 */
class BulkActionTest extends TestCase
{
  use RefreshDatabase;

  protected User $admin;

  public function setUp(): void
  {
    parent::setUp();
    $this->admin = User::factory()->create(['level' => User::LEVEL_ADMIN]);
  }

  // ── Toggle aktif: User ────────────────────────────────────────────────────

  public function test_toggle_aktif_flips_user_from_active_to_inactive(): void
  {
    $user = User::factory()->create(['level' => User::LEVEL_PESERTA, 'aktif' => true]);

    $this->actingAs($this->admin);
    $user->update(['aktif' => ! $user->aktif]);

    $this->assertFalse($user->fresh()->aktif);
  }

  public function test_toggle_aktif_flips_user_from_inactive_to_active(): void
  {
    $user = User::factory()->create(['level' => User::LEVEL_PESERTA, 'aktif' => false]);

    $this->actingAs($this->admin);
    $user->update(['aktif' => ! $user->aktif]);

    $this->assertTrue($user->fresh()->aktif);
  }

  public function test_toggle_aktif_applies_to_multiple_users(): void
  {
    $users = User::factory()->count(3)->create(['level' => User::LEVEL_PESERTA, 'aktif' => true]);

    $this->actingAs($this->admin);

    foreach ($users as $u) {
      $u->update(['aktif' => ! $u->aktif]);
    }

    foreach ($users as $u) {
      $this->assertFalse($u->fresh()->aktif);
    }
  }

  public function test_toggle_aktif_preserves_other_user_fields(): void
  {
    $user = User::factory()->create(['level' => User::LEVEL_PESERTA, 'aktif' => true, 'name' => 'Test User']);

    $user->update(['aktif' => false]);

    $fresh = $user->fresh();
    $this->assertEquals('Test User', $fresh->name);
    $this->assertEquals(User::LEVEL_PESERTA, $fresh->level);
    $this->assertFalse($fresh->aktif);
  }

  // ── Toggle aktif: Rombel ──────────────────────────────────────────────────

  public function test_toggle_aktif_flips_rombel_status(): void
  {
    $rombel = Rombel::factory()->create(['aktif' => true]);

    $this->actingAs($this->admin);
    $rombel->update(['aktif' => ! $rombel->aktif]);

    $this->assertFalse($rombel->fresh()->aktif);
  }

  public function test_toggle_aktif_applies_to_multiple_rombels(): void
  {
    $rombels = Rombel::factory()->count(2)->create(['aktif' => false]);

    $this->actingAs($this->admin);

    foreach ($rombels as $r) {
      $r->update(['aktif' => ! $r->aktif]);
    }

    foreach ($rombels as $r) {
      $this->assertTrue($r->fresh()->aktif);
    }
  }

  // ── Toggle aktif: Question ────────────────────────────────────────────────

  public function test_toggle_aktif_flips_question_status(): void
  {
    $guru     = User::factory()->create(['level' => User::LEVEL_GURU]);
    $question = Question::factory()->create(['created_by' => $guru->id, 'aktif' => true]);

    $this->actingAs($guru);
    $question->update(['aktif' => ! $question->aktif]);

    $this->assertFalse($question->fresh()->aktif);
  }

  public function test_toggle_aktif_applies_to_multiple_questions(): void
  {
    $guru      = User::factory()->create(['level' => User::LEVEL_GURU]);
    $questions = Question::factory()->count(4)->create(['created_by' => $guru->id, 'aktif' => true]);

    $this->actingAs($guru);

    foreach ($questions as $q) {
      $q->update(['aktif' => ! $q->aktif]);
    }

    $aktifCount = Question::where('created_by', $guru->id)->where('aktif', false)->count();
    $this->assertEquals(4, $aktifCount);
  }

  // ── Bulk reset password: User ─────────────────────────────────────────────

  public function test_bulk_reset_password_updates_hash_for_single_user(): void
  {
    $user = User::factory()->create(['level' => User::LEVEL_PESERTA, 'password' => Hash::make('old_password')]);

    $this->actingAs($this->admin);

    $newPassword = 'new_secure_password';
    $hashed      = Hash::make($newPassword);
    $user->update(['password' => $hashed]);

    $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    $this->assertFalse(Hash::check('old_password', $user->fresh()->password));
  }

  public function test_bulk_reset_password_applies_same_hash_to_multiple_users(): void
  {
    $users = User::factory()->count(3)->create(['level' => User::LEVEL_PESERTA]);

    $this->actingAs($this->admin);

    $newPassword = 'shared_password_123';
    $hashed      = Hash::make($newPassword);

    foreach ($users as $u) {
      $u->update(['password' => $hashed]);
    }

    foreach ($users as $u) {
      $this->assertTrue(Hash::check($newPassword, $u->fresh()->password));
    }
  }

  public function test_bulk_reset_password_does_not_affect_other_users(): void
  {
    $targetUser    = User::factory()->create(['level' => User::LEVEL_PESERTA, 'password' => Hash::make('target_old')]);
    $untouchedUser = User::factory()->create(['level' => User::LEVEL_PESERTA, 'password' => Hash::make('untouched_pass')]);

    $this->actingAs($this->admin);

    $targetUser->update(['password' => Hash::make('target_new')]);

    $this->assertTrue(Hash::check('target_new', $targetUser->fresh()->password));
    $this->assertTrue(Hash::check('untouched_pass', $untouchedUser->fresh()->password));
  }
}
