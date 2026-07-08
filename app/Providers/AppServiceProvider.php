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
        if (! $this->app->bound('view')) {
            return;
        }

        // Share active semester to web views only (skip API/CLI to avoid boot errors)
        try {
            \Illuminate\Support\Facades\View::share(
                'activeSemester',
                app(\App\Services\SemesterService::class)->getActiveSemester(),
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\View::share('activeSemester', null);
        }
    }
}
