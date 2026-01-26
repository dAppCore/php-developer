# Core PHP Framework Project

A modular monolith Laravel application built with Core PHP Framework.

## Features

- **Core Framework** - Event-driven module system with lazy loading
- **Admin Panel** - Livewire-powered admin interface with Flux UI
- **REST API** - Scoped API keys, rate limiting, webhooks, OpenAPI docs
- **MCP Tools** - Model Context Protocol for AI agent integration

## Requirements

- PHP 8.2+
- Composer 2.x
- SQLite (default) or MySQL/PostgreSQL
- Node.js 18+ (for frontend assets)

## Installation

```bash
# Clone or create from template
git clone https://github.com/host-uk/core-template.git my-project
cd my-project

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database
touch database/database.sqlite
php artisan migrate

# Start development server
php artisan serve
```

Visit: http://localhost:8000

## Project Structure

```
app/
├── Console/      # Artisan commands
├── Http/         # Controllers & Middleware
├── Models/       # Eloquent models
├── Mod/          # Your custom modules
└── Providers/    # Service providers

config/
└── core.php      # Core framework configuration

routes/
├── web.php       # Public web routes
├── api.php       # REST API routes
└── console.php   # Artisan commands
```

## Creating Modules

```bash
# Create a new module with all features
php artisan make:mod Blog --all

# Create module with specific features
php artisan make:mod Shop --web --api --admin
```

Modules follow the event-driven pattern:

```php
<?php

namespace App\Mod\Blog;

use Core\Events\WebRoutesRegistering;
use Core\Events\ApiRoutesRegistering;
use Core\Events\AdminPanelBooting;

class Boot
{
    public static array $listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
        ApiRoutesRegistering::class => 'onApiRoutes',
        AdminPanelBooting::class => 'onAdminPanel',
    ];

    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->routes(fn() => require __DIR__.'/Routes/web.php');
        $event->views('blog', __DIR__.'/Views');
    }
}
```

## Core Packages

| Package | Description |
|---------|-------------|
| `host-uk/core` | Core framework components |
| `host-uk/core-admin` | Admin panel & Livewire modals |
| `host-uk/core-api` | REST API with scopes & webhooks |
| `host-uk/core-mcp` | Model Context Protocol tools |

## Flux Pro (Optional)

This template uses the free Flux UI components. If you have a Flux Pro license:

```bash
# Configure authentication
composer config http-basic.composer.fluxui.dev your-email your-license-key

# Add the repository
composer config repositories.flux-pro composer https://composer.fluxui.dev

# Install Flux Pro
composer require livewire/flux-pro
```

## Documentation

- [Core PHP Framework](https://github.com/host-uk/core-php)
- [Getting Started Guide](https://host-uk.github.io/core-php/guide/)
- [Architecture](https://host-uk.github.io/core-php/architecture/)

## License

EUPL-1.2 (European Union Public Licence)
