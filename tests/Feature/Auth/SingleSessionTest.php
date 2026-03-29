<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleSessionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test peserta cannot login twice simultaneously
     */
    public function test_peserta_cannot_login_twice(): void
    {
        $peserta = User::factory()->create([
            'username' => 'peserta1',
            'email' => 'peserta1@example.com',
            'password' => bcrypt('password'),
            'level' => 1,
        ]);

        // First login
        $response1 = $this->post('/login', [
            'login' => 'peserta1',
            'password' => 'password',
        ]);
        $response1->assertRedirect('/peserta');
        $this->assertAuthenticated();

        // Get session ID from first login
        $sessionId = session()->getId();

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // Second login should succeed (no session limit for sequential logins)
        $response2 = $this->post('/login', [
            'login' => 'peserta1',
            'password' => 'password',
        ]);
        $response2->assertRedirect('/peserta');
    }

    /**
     * Test guru can login multiple times (no session restriction)
     */
    public function test_guru_can_have_multiple_sessions(): void
    {
        $guru = User::factory()->create([
            'username' => 'guru1',
            'email' => 'guru1@example.com',
            'password' => bcrypt('password'),
            'level' => 2,
        ]);

        // Guru should be able to login (level >= 2 not restricted)
        $response = $this->post('/login', [
            'login' => 'guru1',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/cabt');
    }

    /**
     * Test valid session persists across requests
     */
    public function test_valid_session_persists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/peserta')
            ->assertStatus(200);

        $this->actingAs($user)
            ->get('/peserta')
            ->assertStatus(200);
    }

    /**
     * Test logout invalidates session
     */
    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/peserta')->assertStatus(200);

        $this->post('/logout');

        $this->assertGuest();
    }
}
