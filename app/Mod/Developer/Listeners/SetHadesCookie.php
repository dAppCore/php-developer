<?php

declare(strict_types=1);

namespace Mod\Developer\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cookie;

class SetHadesCookie
{
    /**
     * Set the Hades debug cookie on successful login.
     *
     * This enables god-mode debug access for the user.
     * The cookie contains an encrypted version of HADES_TOKEN.
     * To revoke access, change HADES_TOKEN in the environment.
     */
    public function handle(Login $event): void
    {
        $hadesToken = config('developer.hades_token');

        if (empty($hadesToken)) {
            return;
        }

        // Set encrypted cookie that lasts 1 year
        // Cookie is HTTP-only and secure in production
        Cookie::queue(Cookie::make(
            name: 'hades',
            value: encrypt($hadesToken),
            minutes: 60 * 24 * 365, // 1 year
            path: '/',
            domain: config('session.domain'),
            secure: config('app.env') === 'production',
            httpOnly: true,
            sameSite: 'lax'
        ));
    }
}
