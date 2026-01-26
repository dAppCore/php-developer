<?php

declare(strict_types=1);

namespace Mod\Developer\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TelescopeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Skip if Telescope is not installed
        if (! class_exists(\Laravel\Telescope\Telescope::class)) {
            return;
        }

        $this->gate();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Skip if Telescope is not installed (production without dev dependencies)
        if (! class_exists(\Laravel\Telescope\Telescope::class)) {
            return;
        }

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        \Laravel\Telescope\Telescope::filter(function (\Laravel\Telescope\IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        \Laravel\Telescope\Telescope::hideRequestParameters(['_token']);

        \Laravel\Telescope\Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user = null) {
            // Always allow in local environment
            if (app()->environment('local')) {
                return true;
            }

            // In production, require Hades tier
            return $user?->isHades() ?? false;
        });
    }
}
