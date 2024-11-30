<?php

namespace App\Observers;

use App\Models\Borrowing;

class BorrowingObserver
{
    /**
     * Handle the Borrowing "created" event.
     */
    public function created(Borrowing $borrowing): void
    {
        $book = $borrowing->book;
        $book->decrement('stock');
        $book->increment('borrowed');
    }

    /**
     * Handle the Borrowing "updated" event.
     */
    public function updated(Borrowing $borrowing): void
    {
        if ($borrowing->isDirty('status')) {
            $book = $borrowing->book;
            if ($borrowing->status === 'borrowed') {
                $book->decrement('stock');
                $book->increment('borrowed');
            } elseif ($borrowing->status === 'returned') {
                $book->increment('stock');
                $book->decrement('borrowed');
            }
        }
    }

    /**
     * Handle the Borrowing "deleted" event.
     */
    public function deleted(Borrowing $borrowing): void
    {
        if ($borrowing->status === 'borrowed') {
            $book = $borrowing->book;
            $book->increment('stock');
            $book->decrement('borrowed');
        }
    }

    /**
     * Handle the Borrowing "restored" event.
     */
    public function restored(Borrowing $borrowing): void
    {
        //
    }

    /**
     * Handle the Borrowing "force deleted" event.
     */
    public function forceDeleted(Borrowing $borrowing): void
    {
        //
    }
}