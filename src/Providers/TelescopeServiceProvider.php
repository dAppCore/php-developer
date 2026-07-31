<?php

declare(strict_types=1);

namespace Core\Developer\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class TelescopeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Skip if Telescope is not installed
        if (! class_exists(Telescope::class)) {
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
        if (! class_exists(Telescope::class)) {
            return;
        }

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
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

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
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
