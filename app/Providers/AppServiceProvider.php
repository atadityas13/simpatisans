<?php

namespace App\Providers;

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
        // Share active semester to all views
        \Illuminate\Support\Facades\View::share('activeSemester', app(\App\Services\SemesterService::class)->getActiveSemester());
    }
}
