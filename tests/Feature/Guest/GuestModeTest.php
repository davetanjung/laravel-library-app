<?php

namespace Tests\Feature\Guest;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_mode()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertDontSeeText('borrow');
    }

    public function test_protected_route()
    {
        $response = $this->get('/loans');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_register()
    {
        Book::factory()->create();

        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');

        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertSeeText('Borrow book');
    }

    public function test_login()
    {
        Book::factory()->create();

        $user = User::create([
            'name' => 'namee',
            'email' => 'newuser@example.com',
            'password' => 'password'
        ]);

        $response = $this->post('/login', [
            'email' => 'newuser@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');

        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertSeeText('Borrow book');
    }
}