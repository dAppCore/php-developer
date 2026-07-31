<?php

declare(strict_types=1);

use Core\Developer\Controllers\DevController;
use Core\Developer\Middleware\RequireHades;
use Core\Developer\View\Modal\Admin\ActivityLog;
use Core\Developer\View\Modal\Admin\Cache;
use Core\Developer\View\Modal\Admin\Database;
use Core\Developer\View\Modal\Admin\Logs;
use Core\Developer\View\Modal\Admin\RouteInspector;
use Core\Developer\View\Modal\Admin\Routes;
use Core\Developer\View\Modal\Admin\Servers;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Developer Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('hub')->name('hub.')->group(function () {
    // Developer tools (Hades only) - authorization enforced via middleware
    Route::prefix('dev')
        ->name('dev.')
        ->middleware(RequireHades::class)
        ->group(function () {
            Route::get('/logs', Logs::class)->name('logs');
            Route::get('/routes', Routes::class)->name('routes');
            Route::get('/cache', Cache::class)->name('cache');
            Route::get('/activity', ActivityLog::class)->name('activity');
            Route::get('/servers', Servers::class)->name('servers');
            Route::get('/database', Database::class)->name('database');
            Route::get('/route-inspector', RouteInspector::class)->name('route-inspector');
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
    ->middleware(RequireHades::class)
    ->group(function () {
        Route::get('/logs', [DevController::class, 'logs'])
            ->middleware('throttle:dev-logs')
            ->name('logs');

        Route::get('/routes', [DevController::class, 'routes'])
            ->middleware('throttle:dev-routes')
            ->name('routes');

        Route::get('/session', [DevController::class, 'session'])
            ->middleware('throttle:dev-session')
            ->name('session');

        Route::post('/clear/{type}', [DevController::class, 'clear'])
            ->middleware('throttle:dev-cache-clear')
            ->name('clear');
    });
