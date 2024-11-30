<?php

namespace App\Providers;

use App\Models\Borrowing;
use App\Observers\BorrowingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
      Borrowing::observe(BorrowingObserver::class);
    }
}
