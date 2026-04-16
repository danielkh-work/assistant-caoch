<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * LogViewerAuthServiceProvider
 *
 * Restricts access to the Log Viewer UI (opcodesio/log-viewer) to
 * the specific admin email address defined below.
 *
 * The log-viewer package natively checks the 'viewLogViewer' Gate,
 * so defining it here is all that is needed — no route changes required.
 */
class LogViewerAuthServiceProvider extends ServiceProvider
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
     *
     * Defines the 'viewLogViewer' Gate checked by opcodesio/log-viewer.
     * Access is granted only to the user with email = 'human@admin.com'.
     */
    public function boot(): void
    {
        Gate::define('viewLogViewer', function ($user) {
            // Only the user with this specific email can access the log viewer.
            return $user->email === 'human@admin.com';
        });
    }
}
