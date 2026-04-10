<?php

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthenticatedTest extends TestCase
{

    use RefreshDatabase;
    public function test_homepage_unauthenticated()
    {
        // untuk test visit homepage
        $response = $this->get('/');

        // kita harus bisa akses homepage
        $response->assertStatus(200);

        // tidak lihat borror button
        $response->assertDontSeeText('borrow');

        // create book dibawah blm boleh keliatan dulu
        $response->assertDontSeeText('Test book');

        // buku yang ditampilkan sesuai dengan saat proses create
        $book = Book::factory()->create(
            ['title' => 'Test book',
             'author' => 'Test author',
             'year' => 1925,
             'copies_in_circulation' => 5
            ]
        );

        // refresh homepage
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSeeText('Test book');
        $response->assertSeeText('Test author');
        $response->assertSeeText(1925);
    }
}