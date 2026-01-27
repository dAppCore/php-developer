<?php

declare(strict_types=1);

namespace Core\Developer\View\Modal\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cache Management')]
#[Layout('hub::admin.layouts.app')]
class Cache extends Component
{
    public string $lastAction = '';

    public string $lastOutput = '';

    public bool $showConfirmation = false;

    public string $pendingAction = '';

    public string $confirmationMessage = '';

    public function mount(): void
    {
        $this->checkHadesAccess();
    }

    public function requestConfirmation(string $action): void
    {
        $this->pendingAction = $action;
        $this->confirmationMessage = match ($action) {
            'cache' => 'This will clear the application cache. Active sessions and cached data will be lost.',
            'config' => 'This will clear the configuration cache. The app will re-read all config files.',
            'view' => 'This will clear compiled Blade templates. Views will be recompiled on next request.',
            'route' => 'This will clear the route cache. Routes will be re-registered on next request.',
            'all' => 'This will clear ALL caches (application, config, views, routes). This may cause temporary slowdown.',
            'optimize' => 'This will cache config, routes, and views for production. Only use in production!',
            default => 'Are you sure you want to proceed?',
        };
        $this->showConfirmation = true;
    }

    public function cancelAction(): void
    {
        $this->showConfirmation = false;
        $this->pendingAction = '';
        $this->confirmationMessage = '';
    }

    public function confirmAction(): void
    {
        $action = $this->pendingAction;
        $this->cancelAction();

        match ($action) {
            'cache' => $this->clearCache(),
            'config' => $this->clearConfig(),
            'view' => $this->clearViews(),
            'route' => $this->clearRoutes(),
            'all' => $this->clearAll(),
            'optimize' => $this->optimise(),
            default => null,
        };
    }

    protected function clearCache(): void
    {
        Artisan::call('cache:clear');
        $this->lastAction = 'cache';
        $this->lastOutput = trim(Artisan::output());
        $this->dispatch('notify', message: 'Application cache cleared');
    }

    protected function clearConfig(): void
    {
        Artisan::call('config:clear');
        $this->lastAction = 'config';
        $this->lastOutput = trim(Artisan::output());
        $this->dispatch('notify', message: 'Configuration cache cleared');
    }

    protected function clearViews(): void
    {
        Artisan::call('view:clear');
        $this->lastAction = 'view';
        $this->lastOutput = trim(Artisan::output());
        $this->dispatch('notify', message: 'View cache cleared');
    }

    protected function clearRoutes(): void
    {
        Artisan::call('route:clear');
        $this->lastAction = 'route';
        $this->lastOutput = trim(Artisan::output());
        $this->dispatch('notify', message: 'Route cache cleared');
    }

    protected function clearAll(): void
    {
        $output = [];

        Artisan::call('cache:clear');
        $output[] = trim(Artisan::output());

        Artisan::call('config:clear');
        $output[] = trim(Artisan::output());

        Artisan::call('view:clear');
        $output[] = trim(Artisan::output());

        Artisan::call('route:clear');
        $output[] = trim(Artisan::output());

        $this->lastAction = 'all';
        $this->lastOutput = implode("\n", $output);
        $this->dispatch('notify', message: 'All caches cleared');
    }

    protected function optimise(): void
    {
        Artisan::call('optimize');
        $this->lastAction = 'optimize';
        $this->lastOutput = trim(Artisan::output());
        $this->dispatch('notify', message: 'Application optimised');
    }

    private function checkHadesAccess(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    public function render(): View
    {
        return view('developer::admin.cache');
    }
}
