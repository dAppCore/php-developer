# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **core-developer**, a developer tools module for the Core PHP Framework. It provides an admin panel for debugging and monitoring applications, including log viewer, route inspector, database explorer, cache management, and SSH server connections.

**Key characteristics:**
- PHP 8.2+ / Laravel 11-12 / Livewire 3-4
- L1 module using `Core\Developer\` namespace
- Requires `host-uk/core` and `host-uk/core-admin` dependencies
- All features require "Hades" (god-mode) authorization

## Common Commands

```bash
# Install dependencies
composer install
npm install

# Run tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature

# Frontend development
npm run dev      # Vite dev server
npm run build    # Production build
```

## Architecture

### Event-Driven Module Pattern

The module uses Core Framework's event-driven lazy loading via the `Boot` class:

```php
public static array $listens = [
    AdminPanelBooting::class => 'onAdminPanel',
    ConsoleBooting::class => 'onConsole',
];
```

Routes, views, and commands are only loaded when these events fire.

### Key Components

| Directory | Purpose |
|-----------|---------|
| `src/Boot.php` | Service provider, event handlers, admin menu registration |
| `src/View/Modal/Admin/` | Livewire page components (Logs, Routes, Cache, etc.) |
| `src/Services/` | Business logic (LogReaderService, RouteTestService) |
| `src/Controllers/` | API endpoints for developer tools |
| `src/Middleware/` | RequireHades (authorization), ApplyIconSettings |

### Authorization

All developer features require Hades access:
- Middleware: `RequireHades` checks `auth()->user()->isHades()`
- Livewire components call `$this->checkHadesAccess()` in `mount()`
- Rate limiting configured for API endpoints (dev-cache-clear, dev-logs, dev-routes, dev-session)

### Routing

- Admin pages: `/hub/dev/*` (Livewire components)
- API endpoints: `/hub/api/dev/*` (controller actions with throttling)
- Route names prefixed with `hub.dev.` and `hub.api.dev.`

### Livewire Component Pattern

Uses modern attribute-based syntax:
```php
#[Title('Application Logs')]
#[Layout('hub::admin.layouts.app')]
class Logs extends Component
```

Views located at `src/View/Blade/admin/` and referenced as `developer::admin.{name}`.

### Security Features

LogReaderService automatically redacts sensitive data:
- API keys (Stripe, GitHub, AWS)
- Tokens (Bearer, JWT)
- Database credentials
- Partial email/IP redaction
- Credit card numbers
- Private keys

### Multi-Tenancy

Models use the `BelongsToWorkspace` trait for workspace isolation. The Server model also uses `LogsActivity` and `SoftDeletes`.

## Testing

Tests use Pest-style syntax in `src/Tests/UseCase/`:
```php
describe('Developer Tools', function () {
    it('can view the logs page', function () { ... });
});
```

PHPUnit configuration uses SQLite in-memory database with Telescope/Pulse disabled.

## Localization

All strings in `src/Lang/en_GB/developer.php`. Reference as:
```php
__('developer::developer.logs.title')
```