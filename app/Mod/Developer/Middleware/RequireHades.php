<?php

declare(strict_types=1);

namespace Mod\Developer\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to require Hades (god-mode) access.
 *
 * Apply to routes that should only be accessible by users with Hades tier.
 *
 * Usage in routes:
 *   Route::middleware(['auth', 'hades'])->group(function () {
 *       Route::get('/dev/logs', ...);
 *   });
 */
class RequireHades
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isHades()) {
            abort(403, 'Hades access required');
        }

        return $next($request);
    }
}
