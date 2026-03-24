# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**core-developer** (`lthn/php-developer`) — developer tools module for the Core PHP Framework. Provides an admin panel for debugging and monitoring: log viewer, route inspector, database explorer, cache management, activity log, and SSH server connections.

- PHP 8.2+ / Laravel 11-12 / Livewire 3-4
- Module namespace: `Core\Developer\`
- Dependencies: `lthn/php` (core framework), `core/php-admin` (admin panel)
- All features gated behind "Hades" (god-mode) authorization

## Commands

```bash
# Tests
composer test                                 # All tests
composer test -- --testsuite=Unit             # Unit only
composer test -- --testsuite=Feature          # Feature only
composer test -- --filter="test name"         # Single test

# Code style (PSR-12 via Laravel Pint)
composer lint                                 # Fix code style
./vendor/bin/pint --dirty                     # Format changed files only

# Frontend
npm run dev      # Vite dev server
npm run build    # Production build
```

## Architecture

### Event-Driven Lazy Loading

The module registers via `Boot` (a `ServiceProvider` + `AdminMenuProvider`). Nothing loads until events fire:

```php
public static array $listens = [
    AdminPanelBooting::class => 'onAdminPanel',  // loads routes, views
    ConsoleBooting::class => 'onConsole',          // registers artisan commands
];
```

`boot()` always runs (translations, config merge, admin menu registration, rate limiters, query logging in local env).

### Routing

All routes defined in `src/Routes/admin.php`, loaded only when `AdminPanelBooting` fires:

- **Admin pages:** `/hub/dev/*` — Livewire components, route names `hub.dev.*`
- **API endpoints:** `/hub/api/dev/*` — JSON controller actions, route names `hub.api.dev.*`, each with throttle middleware

Both groups use `RequireHades` middleware. Rate limiters (`dev-cache-clear`, `dev-logs`, `dev-routes`, `dev-session`) are configured in `Boot::configureRateLimiting()`.

### Livewire Components

Located at `src/View/Modal/Admin/`. Use attribute-based syntax:

```php
#[Title('Application Logs')]
#[Layout('hub::admin.layouts.app')]
class Logs extends Component
```

Each component calls `$this->checkHadesAccess()` in `mount()` (private method that aborts 403). Views referenced as `developer::admin.{name}`, files in `src/View/Blade/admin/`.

### Authorization: Hades

Two enforcement layers:
1. `RequireHades` middleware on all route groups — checks `$user->isHades()`
2. `checkHadesAccess()` in each Livewire component's `mount()` — redundant guard

### SSH / Remote Server Management

`RemoteServerManager` trait (in `src/Concerns/`) provides SSH operations via phpseclib3. The `Server` model stores connection details with `private_key` encrypted via Laravel's `encrypted` cast. Key pattern:

```php
$this->withConnection($server, function () {
    $this->run('git pull');
});
```

The trait verifies workspace ownership before connecting.

### Multi-Tenancy

The `Server` model uses `BelongsToWorkspace` trait for workspace isolation, plus `LogsActivity` (Spatie) and `SoftDeletes`.

### Localization

All UI strings in `src/Lang/en_GB/developer.php`. Key structure: `developer::developer.{section}.{key}`.

## Testing

Tests use Pest-style syntax. Use-case acceptance tests live in `src/Tests/UseCase/`. Standard PHPUnit test directories: `tests/Unit/` and `tests/Feature/`.

PHPUnit configured with SQLite `:memory:`, Telescope/Pulse disabled (`phpunit.xml`).

## Key Conventions

- All PHP files use `declare(strict_types=1)`
- Controller extends `Core\Front\Controller` (not Laravel's base)
- Services injected via constructor (`DevController`) or resolved from container (`app(LogReaderService::class)`)
- `LogReaderService` auto-redacts sensitive data (API keys, tokens, credentials) in log output
- Config lives in `src/config.php`, merged as `developer.*` — SSH timeouts, Hades token, Horizon notification settings
- Module also overrides Pulse vendor views: `view()->addNamespace('pulse', ...)`
