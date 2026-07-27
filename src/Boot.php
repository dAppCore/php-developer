<?php

declare(strict_types=1);

namespace Core\Developer;

use Core\Events\AdminPanelBooting;
use Core\Events\ConsoleBooting;
use Core\Front\Admin\AdminMenuRegistry;
use Core\Front\Admin\Contracts\AdminMenuProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class Boot extends ServiceProvider implements AdminMenuProvider
{
    protected string $moduleName = 'developer';

    /**
     * Events this module listens to for lazy loading.
     *
     * @var array<class-string, string>
     */
    public static array $listens = [
        AdminPanelBooting::class => 'onAdminPanel',
        ConsoleBooting::class => 'onConsole',
    ];

    /** Guards against double registration when onAdminPanel() also fires. */
    protected bool $routesRegistered = false;

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/Lang', 'developer');
        $this->mergeConfigFrom(__DIR__.'/config.php', 'developer');

        app(AdminMenuRegistry::class)->register($this);

        $this->loadViewsFrom(__DIR__.'/View/Blade', $this->moduleName);
        $this->registerAdminRoutes();

        $this->configureRateLimiting();

        // Enable query logging in local environment for dev bar
        if ($this->app->environment('local')) {
            DB::enableQueryLog();
        }
    }

    /**
     * Register the developer routes.
     *
     * boot() registers the admin menu unconditionally, but the routes it links
     * to were only registered from onAdminPanel() — which fires off the static
     * $listens array. That array is wired by ModuleScanner, and ModuleScanner
     * only scans app/Core, app/Mod and app/Website. This package lives under
     * vendor/, so it was never scanned: the menu appeared, its routes did not
     * exist, and the panel died with "Route [hub.dev.logs] not defined" as soon
     * as anything rendered the sidebar.
     *
     * Registering here keeps the menu and its targets in step no matter how the
     * provider was discovered. onAdminPanel() still registers them for hosts
     * where the event does fire, so the guard below keeps that idempotent.
     */
    protected function registerAdminRoutes(): void
    {
        if ($this->routesRegistered || ! file_exists(__DIR__.'/Routes/admin.php')) {
            return;
        }

        $this->routesRegistered = true;

        $this->loadRoutesFrom(__DIR__.'/Routes/admin.php');
    }

    /**
     * Configure rate limiters for developer API endpoints.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limit for cache clear operations: 10 per minute per user
        // Prevents accidental rapid cache clears that could impact performance
        RateLimiter::for('dev-cache-clear', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit for log reading: 30 per minute per user
        // Moderate limit as reading logs is read-only
        RateLimiter::for('dev-logs', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit for route listing: 30 per minute per user
        // Read-only operation, moderate limit
        RateLimiter::for('dev-routes', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit for session info: 60 per minute per user
        // Read-only operation, higher limit for debugging
        RateLimiter::for('dev-session', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Admin menu items for this module.
     */
    public function adminMenuItems(): array
    {
        return [
            // Admin menu (Hades only)
            [
                'group' => 'admin',
                'priority' => 80,
                'admin' => true,
                'item' => fn () => [
                    'label' => 'Dev Tools',
                    'icon' => 'code',
                    'color' => 'lime',
                    'active' => request()->routeIs('hub.dev.*'),
                    'children' => [
                        ['label' => 'Logs', 'icon' => 'scroll', 'href' => route('hub.dev.logs'), 'active' => request()->routeIs('hub.dev.logs')],
                        ['label' => 'Activity', 'icon' => 'clock', 'href' => route('hub.dev.activity'), 'active' => request()->routeIs('hub.dev.activity')],
                        ['label' => 'Servers', 'icon' => 'server', 'href' => route('hub.dev.servers'), 'active' => request()->routeIs('hub.dev.servers')],
                        ['label' => 'Database', 'icon' => 'circle-stack', 'href' => route('hub.dev.database'), 'active' => request()->routeIs('hub.dev.database')],
                        ['label' => 'Routes', 'icon' => 'route', 'href' => route('hub.dev.routes'), 'active' => request()->routeIs('hub.dev.routes')],
                        ['label' => 'Route Inspector', 'icon' => 'beaker', 'href' => route('hub.dev.route-inspector'), 'active' => request()->routeIs('hub.dev.route-inspector')],
                        ['label' => 'Cache', 'icon' => 'database', 'href' => route('hub.dev.cache'), 'active' => request()->routeIs('hub.dev.cache')],
                    ],
                ],
            ],
        ];
    }

    /**
     * Global permissions required for Developer menu items.
     */
    public function menuPermissions(): array
    {
        return []; // Items use 'admin' flag for Hades-only access
    }

    /**
     * Check if user can view Developer menu items.
     */
    public function canViewMenu(?object $user, ?object $workspace): bool
    {
        return $user !== null; // Authenticated users - items filter by admin flag
    }

    public function register(): void
    {
        //
    }

    // -------------------------------------------------------------------------
    // Event-driven handlers
    // -------------------------------------------------------------------------

    public function onAdminPanel(AdminPanelBooting $event): void
    {
        $event->views($this->moduleName, __DIR__.'/View/Blade');

        // Override Pulse vendor views
        view()->addNamespace('pulse', __DIR__.'/View/Blade/vendor/pulse');

        if (! $this->routesRegistered && file_exists(__DIR__.'/Routes/admin.php')) {
            $this->routesRegistered = true;
            $event->routes(fn () => require __DIR__.'/Routes/admin.php');
        }
    }

    public function onConsole(ConsoleBooting $event): void
    {
        $event->command(Console\Commands\CopyDeviceFrames::class);
    }
}
