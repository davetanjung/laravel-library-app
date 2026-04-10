<?php

namespace Tests\Feature\Auth;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Alur2Test extends TestCase
{
    use RefreshDatabase;

    public function test_borrow_exceeding_available_copies_should_fail()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // create book 10 copies
        $book = Book::factory()->create([
            'title' => 'Test Book',
            'copies_in_circulation' => 10,
        ]);

        // pastikan available copies nya 10
        $this->assertEquals(10, $book->availableCopies());

        // buka form pinjam buku
        $response = $this->get('/loans/' . $book->id);
        $response->assertStatus(200);
        $response->assertSee('Borrow Book "' . $book->title . '"', false);

        // coba pinjam lebih dari available copies, 11>10
        $response = $this->post('/loans/' . $book->id, [
            'book_id' => $book->id,
            'number_borrowed' => 11,
            'return_date' => now()->addDays(7)->toDateString(),
        ]);

        // redirect balik
        $response->assertStatus(302);

        // pastikan ada error karena number_borrowed
        $response->assertSessionHasErrors('number_borrowed');

        // pastikan tidak ke record dalam database
        $this->assertDatabaseMissing('loans', [
            'book_id' => $book->id,
            'number_borrowed' => 11,
        ]);

        // pastikan available copies masih 10
        $book->refresh();
        $this->assertEquals(10, $book->availableCopies());

        // cek di homepage lagi available copies nya
        $response = $this->get('/dashboard');
        $response->assertSeeText('10');
    }
}