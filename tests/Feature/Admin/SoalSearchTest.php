<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $peserta;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['level' => 3]);
        $this->guru    = User::factory()->create(['level' => 2]);
        $this->peserta = User::factory()->create(['level' => 1]);
    }

    public function test_admin_can_search_soal(): void
    {
        Question::factory()->pg()->create([
            'tipe'     => Question::TIPE_PG,
            'teks_soal' => '<p>Soal matematika</p>',
            'aktif'    => true,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.soal.search', ['q' => 'matematika']))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_search_requires_at_least_level_3(): void
    {
        // Level 2 (guru) tidak bisa akses
        $this->actingAs($this->guru)
            ->getJson(route('admin.soal.search'))
            ->assertForbidden();
    }

    public function test_search_requires_authentication(): void
    {
        $this->getJson(route('admin.soal.search'))->assertUnauthorized();
    }

    public function test_search_returns_all_active_soal_when_no_filter(): void
    {
        Question::factory()->pg()->count(5)->create(['aktif' => true]);
        Question::factory()->pg()->create(['aktif' => false]);  // tidak aktif

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.soal.search'));

        $response->assertOk();
        $results = $response->json();
        // Hanya yang aktif — 5 buah
        $this->assertCount(5, $results);
    }

    public function test_search_filters_by_tipe(): void
    {
        Question::factory()->create(['tipe' => Question::TIPE_PG,    'aktif' => true]);
        Question::factory()->create(['tipe' => Question::TIPE_ISIAN,  'aktif' => true]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.soal.search', ['tipe' => Question::TIPE_PG]));

        $response->assertOk();
        $results = $response->json();
        $this->assertCount(1, $results);
        $this->assertEquals(Question::TIPE_PG, $results[0]['tipe']);
    }

    public function test_search_filters_by_kesulitan(): void
    {
        Question::factory()->create(['tingkat_kesulitan' => 'mudah', 'aktif' => true]);
        Question::factory()->create(['tingkat_kesulitan' => 'sulit',  'aktif' => true]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.soal.search', ['kesulitan' => 'mudah']));

        $response->assertOk();
        $results = $response->json();
        $this->assertCount(1, $results);
    }

    public function test_search_filters_by_kategori_id(): void
    {
        $kat1 = Category::create(['nama' => 'Matematika']);
        $kat2 = Category::create(['nama' => 'IPA']);

        Question::factory()->create(['kategori_id' => $kat1->id, 'aktif' => true]);
        Question::factory()->create(['kategori_id' => $kat2->id, 'aktif' => true]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.soal.search', ['kategori_id' => $kat1->id]));

        $response->assertOk();
        $results = $response->json();
        $this->assertCount(1, $results);
    }

    public function test_search_returns_expected_json_structure(): void
    {
        Question::factory()->pg()->create(['aktif' => true]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.soal.search'));

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => ['id', 'tipe', 'tipe_label', 'teks_soal', 'kesulitan', 'bobot', 'kategori'],
        ]);
    }

    public function test_search_strips_html_from_teks_soal(): void
    {
        Question::factory()->create([
            'teks_soal' => '<p>Soal <strong>bersih</strong></p>',
            'aktif'     => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.soal.search'));
        $result   = $response->json()[0];

        $this->assertStringNotContainsString('<p>', $result['teks_soal']);
    }

    public function test_search_limits_result_to_50(): void
    {
        Question::factory()->count(60)->create(['aktif' => true]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.soal.search'));

        $this->assertCount(50, $response->json());
    }
}
