<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Cart\Models\Cart;
use Modules\Store\Models\Store;
use Modules\Store\Models\StudentBook;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Subscribe student to books in cart and flatten them into student_books table.
     */
    public function subscribeToBooks(Request $request)
    {
        $studentId = $request->input('student_id');

        if (!$studentId) {
            return res_data('student_id is required', 'failed', 400);
        }

        // 1. Get items from cart for this student
        $cartItems = Cart::where('student_id', $studentId)
            ->where('type', 'books')
            ->get();

        if ($cartItems->isEmpty()) {
            return res_data('No books found in cart for this student.', 'failed', 400);
        }

        return DB::transaction(function () use ($studentId, $cartItems) {
            foreach ($cartItems as $cartItem) {
                // Get the store item and its books
                $storeItem = Store::with('books')->find($cartItem->item_id);
                
                if (!$storeItem) continue;

                $booksCount = $storeItem->books->count();

                foreach ($storeItem->books as $index => $book) {
                    // Logic for naming:
                    // If multiple books: "Store Name - Book 1"
                    // If single book: "Store Name"
                    $displayName = $booksCount > 1 
                        ? $storeItem->title . " - كتاب " . ($index + 1)
                        : $storeItem->title;

                    // Save into student library
                    StudentBook::firstOrCreate([
                        'student_id' => $studentId,
                        'store_id'   => $storeItem->id,
                        'book_url'   => $book->book_url
                    ], [
                        'book_name'  => $displayName
                    ]);
                }
            }

            // Clear cart
            Cart::where('student_id', $studentId)
                ->where('type', 'books')
                ->delete();

            return res_data('Books subscribed and added to your library successfully.', 200);
        });
    }

    /**
     * Get student's library (the flattened books list)
     */
    public function getMyLibrary(Request $request)
    {
        $studentId = $request->input('student_id') ?? (auth()->user() ? auth()->user()->id : null);

        if (!$studentId) {
            return res_data('student_id is required', 'failed', 400);
        }

        $library = StudentBook::with('storeItem')->where('student_id', $studentId)->get();

        $formatted = $library->map(function ($book) {
            return [
                'id'          => $book->id,
                'book_name'   => $book->book_name,
                'book_url'    => $book->book_url,
                'image'       => $book->storeItem ? $book->storeItem->image : null,
                'created_at'  => $book->created_at
            ];
        });

        return res_data($formatted, 'success', 200);
    }
}
