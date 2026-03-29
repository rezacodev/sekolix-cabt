<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login with valid credentials using username
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'level' => 1,
        ]);

        $response = $this->post('/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/peserta');
    }

    /**
     * Test successful login with valid email
     */
    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'level' => 1,
        ]);

        $response = $this->post('/login', [
            'login' => 'testuser@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/peserta');
    }

    /**
     * Test login with invalid credentials
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->post('/login', [
            'login' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    /**
     * Test login with nonexistent user
     */
    public function test_user_cannot_login_with_nonexistent_user(): void
    {
        $response = $this->post('/login', [
            'login' => 'nonexistent',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    /**
     * Test redirect to dashboard for peserta (level 1)
     */
    public function test_peserta_redirected_to_ujian_dashboard(): void
    {
        $user = User::factory()->create([
            'username' => 'peserta',
            'email' => 'peserta@example.com',
            'password' => bcrypt('password'),
            'level' => 1,
        ]);

        $response = $this->post('/login', [
            'login' => 'peserta',
            'password' => 'password',
        ]);

        $response->assertRedirect('/peserta');
    }

    /**
     * Test redirect to admin panel for guru (level 2)
     */
    public function test_guru_redirected_to_admin_panel(): void
    {
        $user = User::factory()->create([
            'username' => 'guru',
            'email' => 'guru@example.com',
            'password' => bcrypt('password'),
            'level' => 2,
        ]);

        $response = $this->post('/login', [
            'login' => 'guru',
            'password' => 'password',
        ]);

        $response->assertRedirect('/cabt');
    }

    /**
     * Test redirect to admin panel for admin (level 3)
     */
    public function test_admin_redirected_to_admin_panel(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'level' => 3,
        ]);

        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect('/cabt');
    }

    /**
     * Test inactive user cannot login
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'inactive',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'aktif' => false,
        ]);

        $response = $this->post('/login', [
            'login' => 'inactive',
            'password' => 'password',
        ]);

        // Should still authenticate but may be checked later by middleware
        // Depending on implementation - this tests if system handles inactive users
        $this->assertGuest();
    }

    /**
     * Test login page is accessible
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Test authenticated user cannot access login page
     */
    public function test_authenticated_user_redirected_from_login(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/login');

        // Should redirect away from login page
        $response->assertRedirect();
    }
}
