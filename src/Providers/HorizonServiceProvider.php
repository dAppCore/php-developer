<?php

declare(strict_types=1);

namespace Core\Developer\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        $this->configureNotifications();
    }

    /**
     * Configure Horizon notification routing from config.
     */
    protected function configureNotifications(): void
    {
        $smsTo = config('developer.horizon.sms_to');
        if ($smsTo) {
            Horizon::routeSmsNotificationsTo($smsTo);
        }

        $mailTo = config('developer.horizon.mail_to');
        if ($mailTo) {
            Horizon::routeMailNotificationsTo($mailTo);
        }

        $slackWebhook = config('developer.horizon.slack_webhook');
        if ($slackWebhook) {
            $slackChannel = config('developer.horizon.slack_channel', '#alerts');
            Horizon::routeSlackNotificationsTo($slackWebhook, $slackChannel);
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return $user?->isHades() ?? false;
        });
    }
}
