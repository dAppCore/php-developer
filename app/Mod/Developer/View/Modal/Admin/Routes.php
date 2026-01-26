<?php

declare(strict_types=1);

namespace Mod\Developer\View\Modal\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Title('Application Routes')]
#[Layout('hub::admin.layouts.app')]
class Routes extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $methodFilter = '';

    public array $routes = [];

    public function mount(): void
    {
        $this->checkHadesAccess();
        $this->loadRoutes();
    }

    public function loadRoutes(): void
    {
        $this->routes = collect(Route::getRoutes())->map(function ($route) {
            $methods = $route->methods();
            $method = $methods[0] ?? 'ANY';

            if ($method === 'HEAD') {
                return null;
            }

            return [
                'method' => $method,
                'uri' => '/'.ltrim($route->uri(), '/'),
                'name' => $route->getName(),
                'action' => $this->formatAction($route->getActionName()),
                'middleware' => implode(', ', $route->gatherMiddleware()),
            ];
        })->filter()->values()->toArray();
    }

    private function formatAction(string $action): string
    {
        // Shorten controller names for readability
        return str_replace('App\\Http\\Controllers\\', '', $action);
    }

    public function updatedSearch(): void
    {
        // Filtering is done in the view
    }

    public function setMethod(string $method): void
    {
        $this->methodFilter = $method === $this->methodFilter ? '' : $method;
    }

    public function getFilteredRoutesProperty(): array
    {
        return collect($this->routes)
            ->filter(function ($route) {
                if ($this->methodFilter && $route['method'] !== $this->methodFilter) {
                    return false;
                }

                if ($this->search) {
                    $searchLower = strtolower($this->search);

                    return str_contains(strtolower($route['uri']), $searchLower)
                        || str_contains(strtolower($route['name'] ?? ''), $searchLower)
                        || str_contains(strtolower($route['action']), $searchLower);
                }

                return true;
            })
            ->values()
            ->toArray();
    }

    private function checkHadesAccess(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    public function render(): View
    {
        return view('developer::admin.routes');
    }
}
