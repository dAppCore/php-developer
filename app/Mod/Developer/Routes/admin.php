<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Developer Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('hub')->name('hub.')->group(function () {
    // Developer tools (Hades only) - authorization checked in components
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('/logs', \Mod\Developer\View\Modal\Admin\Logs::class)->name('logs');
        Route::get('/routes', \Mod\Developer\View\Modal\Admin\Routes::class)->name('routes');
        Route::get('/cache', \Mod\Developer\View\Modal\Admin\Cache::class)->name('cache');
        Route::get('/activity', \Mod\Developer\View\Modal\Admin\ActivityLog::class)->name('activity');
        Route::get('/servers', \Mod\Developer\View\Modal\Admin\Servers::class)->name('servers');
        Route::get('/database', \Mod\Developer\View\Modal\Admin\Database::class)->name('database');
        Route::get('/route-inspector', \Mod\Developer\View\Modal\Admin\RouteInspector::class)->name('route-inspector');
    });
});

/*
|--------------------------------------------------------------------------
| Developer API Routes
|--------------------------------------------------------------------------
| These routes use the RequireHades middleware for authorization and
| rate limiting to prevent abuse of sensitive operations.
*/

Route::prefix('hub/api/dev')
    ->name('hub.api.dev.')
    ->middleware(\Mod\Developer\Middleware\RequireHades::class)
    ->group(function () {
        Route::get('/logs', [\Mod\Developer\Controllers\DevController::class, 'logs'])
            ->middleware('throttle:dev-logs')
            ->name('logs');

        Route::get('/routes', [\Mod\Developer\Controllers\DevController::class, 'routes'])
            ->middleware('throttle:dev-routes')
            ->name('routes');

        Route::get('/session', [\Mod\Developer\Controllers\DevController::class, 'session'])
            ->middleware('throttle:dev-session')
            ->name('session');

        Route::post('/clear/{type}', [\Mod\Developer\Controllers\DevController::class, 'clear'])
            ->middleware('throttle:dev-cache-clear')
            ->name('clear');
    });
