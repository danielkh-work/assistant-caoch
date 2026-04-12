<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * LogViewerAuthServiceProvider
 *
 * Restricts access to the Log Viewer UI (opcodesio/log-viewer) to
 * users whose 'role' column equals 'head_coach' (the top-level role in the system).
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
     * Access is granted only to users whose role column = 'head_coach'.
     */
    public function boot(): void
    {
        Gate::define('viewLogViewer', function ($user) {
            // Checks the 'role' enum column on the users table directly.
            // Only 'head_coach' (the super admin equivalent) can view the log viewer.
            return $user->role === 'head_coach';
        });
    }
}
