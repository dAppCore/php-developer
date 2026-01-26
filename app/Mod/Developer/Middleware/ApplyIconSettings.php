<?php

declare(strict_types=1);

namespace Mod\Developer\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyIconSettings
{
    /**
     * Read icon style and size preferences from cookies (set by JavaScript)
     * and apply them to the session for use by the <x-icon> Blade component.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Read from cookie (synced from localStorage by JS) or use defaults
            $iconStyle = $request->cookie('icon-style', 'fa-notdog fa-solid');
            $iconSize = $request->cookie('icon-size', 'fa-lg');

            // Store in session for Blade component access
            // Wrapped in try-catch to handle session errors gracefully
            session(['icon-style' => $iconStyle, 'icon-size' => $iconSize]);
        } catch (\Throwable) {
            // Session write failed - continue without icon settings
            // ResilientSession middleware will handle the actual error
        }

        return $next($request);
    }
}
