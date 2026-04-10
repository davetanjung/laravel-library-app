<?php

namespace Tests\Feature\Auth;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Alur3Test extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_borrow_when_no_stock_available()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // bikin buku yg copies nya 5
        $book = Book::factory()->create([
            'title' => 'Test Book',
            'copies_in_circulation' => 5,
        ]);

        // pinjam semua copies
        Loan::create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'number_borrowed' => 5,
            'is_returned' => false,
            'return_date' => now()->addDays(7)->toDateString(),
        ]);

        // cek available copies nya sisa 0
        $book->refresh();
        $this->assertEquals(0, $book->availableCopies());

        // open dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // pastikan gaada borrow book button
        $response->assertDontSeeText('Borrow book');

        // ada tulisan no copies available 
        $response->assertSeeText('No copies available to borrow');
    }
}