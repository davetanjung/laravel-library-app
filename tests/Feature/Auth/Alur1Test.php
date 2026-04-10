<?php

namespace Tests\Feature\Auth;

use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Alur1Test extends TestCase
{
    use RefreshDatabase;

    public function test_no_book_in_loans()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // belum ada loan
        $response = $this->get('/loans');
        $response->assertStatus(200);
        $response->assertSeeText('You have no active loans');
    }

    public function test_authenticated_dashboard()
    {
        $user = User::factory()->create();

        $dummyBook = Book::factory()->create([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'year' => 2024,
            'copies_in_circulation' => 5,
        ]);

        $books = Book::factory()->count(3)->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // cek semua buku muncul
        foreach ($books as $book) {
            $response->assertSeeText($book->title);
        }

        // cek dummy book
        $response->assertSeeText('Test Book');
        $response->assertSeeText('Test Author');
        $response->assertSeeText('2024');
        $response->assertSeeText('5');

        // ada tombol borrow
        $response->assertSeeText('Borrow book');

        // buka halaman borrow
        $response = $this->get(route('loans.create', ['book' => $dummyBook->id]));
        $response->assertStatus(200);
    }

    public function test_book_loaned()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $book = Book::factory()->create([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'year' => 2024,
            'copies_in_circulation' => 5,
        ]);

        // buka form borrow
        $response = $this->get('/loans/' . $book->id);
        $response->assertStatus(200);
        $response->assertSee('Borrow Book "' . $book->title . '"', false);

        // input -1 (allowed by system)
        $response = $this->post('/loans/' . $book->id, [
            'book_id' => $book->id,
            'number_borrowed' => -1,
            'return_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/loans');

        // cek halaman loans
        $response = $this->get('/loans');
        $response->assertStatus(200);
        $response->assertSeeText($book->title);
        $response->assertSeeText('-1');

        // cek database
        $this->assertDatabaseHas('loans', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'number_borrowed' => -1,
        ]);

        // cek available copies (5 - (-1) = 6)
        $book->refresh();
        $this->assertEquals(6, $book->availableCopies());

        // cek di dashboard
        $response = $this->get('/dashboard');
        $response->assertSeeText('6');

        // ambil loan
        $loan = Loan::where('book_id', $book->id)
            ->where('user_id', $user->id)
            ->first();

        // return buku
        $response = $this->get(route('loans.terminate', ['loan' => $loan->id]));
        $response->assertRedirect('/loans');

        // refresh
        $loan->refresh();
        $book->refresh();

        // cek status return
        $this->assertEquals(1, $loan->is_returned);

        // available kembali normal
        $this->assertEquals(5, $book->availableCopies());

        // cek dashboard lagi
        $response = $this->get('/dashboard');
        $response->assertSeeText('5');
    }
}