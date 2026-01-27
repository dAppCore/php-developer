<?php

declare(strict_types=1);

namespace Core\Developer\View\Modal\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Core\Developer\Data\RouteTestResult;
use Core\Developer\Services\RouteTestService;

/**
 * Route Inspector - interactive route testing for developers.
 *
 * Allows testing routes directly from the browser with custom parameters,
 * headers, and body content. Only available in local/testing environments.
 */
#[Title('Route Inspector')]
#[Layout('hub::admin.layouts.app')]
class RouteInspector extends Component
{
    // Search/filter state
    #[Url]
    public string $search = '';

    #[Url]
    public string $methodFilter = '';

    // Selected route state
    public bool $showInspector = false;

    public ?array $selectedRoute = null;

    public string $selectedMethod = 'GET';

    // Request builder state
    public array $parameters = [];

    public array $queryParams = [];

    public string $bodyContent = '';

    public string $customHeaders = '';

    public bool $useAuthentication = false;

    // Response state
    public ?array $lastResult = null;

    public bool $executing = false;

    // History
    public array $history = [];

    protected RouteTestService $routeTestService;

    public function boot(RouteTestService $routeTestService): void
    {
        $this->routeTestService = $routeTestService;
    }

    public function mount(): void
    {
        $this->checkHadesAccess();

        if (! $this->routeTestService->isTestingAllowed()) {
            session()->flash('error', 'Route testing is only available in local/testing environments.');
        }
    }

    #[Computed(cache: true)]
    public function routes(): array
    {
        return $this->routeTestService->getRoutes();
    }

    #[Computed]
    public function filteredRoutes(): array
    {
        return collect($this->routes)
            ->filter(function ($route) {
                // Method filter
                if ($this->methodFilter && $route['method'] !== $this->methodFilter) {
                    return false;
                }

                // Search filter
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

    #[Computed]
    public function testingAllowed(): bool
    {
        return $this->routeTestService->isTestingAllowed();
    }

    public function setMethod(string $method): void
    {
        $this->methodFilter = $method === $this->methodFilter ? '' : $method;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->methodFilter = '';
    }

    /**
     * Open the inspector for a specific route.
     */
    public function inspectRoute(string $uri, string $method): void
    {
        $route = $this->routeTestService->findRoute($uri, $method);

        if (! $route) {
            Flux::toast('Route not found', variant: 'danger');

            return;
        }

        $this->selectedRoute = $this->routeTestService->formatRoute($route);
        $this->selectedMethod = $method;
        $this->lastResult = null;
        $this->bodyContent = '';
        $this->customHeaders = '';

        // Initialise parameter inputs
        $this->parameters = [];
        foreach ($this->selectedRoute['parameters'] ?? [] as $param) {
            $this->parameters[$param['name']] = '';
        }

        $this->queryParams = [];
        $this->showInspector = true;
    }

    /**
     * Close the inspector panel.
     */
    public function closeInspector(): void
    {
        $this->showInspector = false;
        $this->selectedRoute = null;
        $this->lastResult = null;
    }

    /**
     * Quick test a GET route (no modal).
     */
    public function quickTest(string $uri, string $method = 'GET'): void
    {
        if (! $this->testingAllowed) {
            Flux::toast('Route testing not allowed in this environment', variant: 'danger');

            return;
        }

        if ($method !== 'GET') {
            // For non-GET, open the inspector instead
            $this->inspectRoute($uri, $method);

            return;
        }

        $route = $this->routeTestService->findRoute($uri, $method);
        if (! $route) {
            Flux::toast('Route not found', variant: 'danger');

            return;
        }

        // If route has required parameters, open inspector
        $params = $this->routeTestService->extractRouteParameters($route);
        $hasRequired = collect($params)->contains('required', true);

        if ($hasRequired) {
            $this->inspectRoute($uri, $method);

            return;
        }

        // Otherwise, execute directly
        $this->selectedRoute = $this->routeTestService->formatRoute($route);
        $this->selectedMethod = $method;
        $this->executeTest();
    }

    /**
     * Execute the test request.
     */
    public function executeTest(): void
    {
        if (! $this->testingAllowed) {
            Flux::toast('Route testing not allowed in this environment', variant: 'danger');

            return;
        }

        if (! $this->selectedRoute) {
            return;
        }

        $this->executing = true;
        $this->lastResult = null;

        try {
            $route = $this->routeTestService->findRoute(
                $this->selectedRoute['uri'],
                $this->selectedMethod
            );

            if (! $route) {
                throw new \RuntimeException('Route not found');
            }

            // Parse custom headers
            $headers = $this->parseHeaders($this->customHeaders);

            // Parse body content
            $body = [];
            if (! empty($this->bodyContent) && in_array($this->selectedMethod, ['POST', 'PUT', 'PATCH'])) {
                $decoded = json_decode($this->bodyContent, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $body = $decoded;
                }
            }

            // Get authenticated user if requested
            $user = $this->useAuthentication ? auth()->user() : null;

            // Log the test request
            Log::info('Route test executed', [
                'user_id' => auth()->id(),
                'route' => $this->selectedRoute['uri'],
                'method' => $this->selectedMethod,
                'ip' => request()->ip(),
            ]);

            // Execute the request
            $result = $this->routeTestService->executeRequest(
                route: $route,
                method: $this->selectedMethod,
                parameters: array_filter($this->parameters),
                queryParams: array_filter($this->queryParams),
                bodyParams: $body,
                headers: $headers,
                authenticatedUser: $user,
            );

            $this->lastResult = $result->toArray();

            // Add to history
            $this->addToHistory($result);

            // Show appropriate toast
            if ($result->isSuccessful()) {
                Flux::toast("Request completed: {$result->statusCode} {$result->getStatusText()}", variant: 'success');
            } elseif ($result->hasException()) {
                Flux::toast("Request failed: {$result->exception->getMessage()}", variant: 'danger');
            } else {
                Flux::toast("Request completed: {$result->statusCode} {$result->getStatusText()}", variant: 'warning');
            }
        } catch (\Throwable $e) {
            $this->lastResult = [
                'status_code' => 500,
                'status_text' => 'Internal Server Error',
                'body' => $e->getMessage(),
                'exception' => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ];

            Flux::toast("Error: {$e->getMessage()}", variant: 'danger');
        }

        $this->executing = false;

        // If not showing inspector, show it now with results
        if (! $this->showInspector && $this->lastResult) {
            $this->showInspector = true;
        }
    }

    /**
     * Add query parameter field.
     */
    public function addQueryParam(): void
    {
        $this->queryParams[] = ['key' => '', 'value' => ''];
    }

    /**
     * Remove query parameter field.
     */
    public function removeQueryParam(int $index): void
    {
        unset($this->queryParams[$index]);
        $this->queryParams = array_values($this->queryParams);
    }

    /**
     * Copy response to clipboard (via JavaScript).
     */
    public function copyResponse(): void
    {
        $this->dispatch('copy-to-clipboard', content: $this->lastResult['body'] ?? '');
    }

    /**
     * Copy as cURL command.
     */
    public function copyAsCurl(): void
    {
        if (! $this->selectedRoute) {
            return;
        }

        $route = $this->routeTestService->findRoute(
            $this->selectedRoute['uri'],
            $this->selectedMethod
        );

        if (! $route) {
            return;
        }

        $url = config('app.url').$this->routeTestService->buildUri(
            $route,
            array_filter($this->parameters),
            array_filter(collect($this->queryParams)->pluck('value', 'key')->toArray())
        );

        $curl = "curl -X {$this->selectedMethod} '{$url}'";

        // Add headers
        $headers = $this->parseHeaders($this->customHeaders);
        foreach ($headers as $name => $value) {
            $curl .= " \\\n  -H '{$name}: {$value}'";
        }

        // Add body
        if (! empty($this->bodyContent)) {
            $curl .= " \\\n  -H 'Content-Type: application/json'";
            $curl .= " \\\n  -d '".addslashes($this->bodyContent)."'";
        }

        $this->dispatch('copy-to-clipboard', content: $curl);
        Flux::toast('cURL command copied to clipboard');
    }

    /**
     * Clear test history.
     */
    public function clearHistory(): void
    {
        $this->history = [];
        Flux::toast('History cleared');
    }

    /**
     * Load a previous test from history.
     */
    public function loadFromHistory(int $index): void
    {
        if (! isset($this->history[$index])) {
            return;
        }

        $item = $this->history[$index];
        $this->inspectRoute($item['uri'], $item['method']);

        if (isset($item['parameters'])) {
            $this->parameters = $item['parameters'];
        }

        if (isset($item['query_params'])) {
            $this->queryParams = $item['query_params'];
        }

        if (isset($item['body'])) {
            $this->bodyContent = $item['body'];
        }

        if (isset($item['headers'])) {
            $this->customHeaders = $item['headers'];
        }
    }

    /**
     * Get colour class for HTTP method.
     */
    public function getMethodColour(string $method): string
    {
        return $this->routeTestService->getMethodColour($method);
    }

    /**
     * Get colour class for status code.
     */
    public function getStatusColour(int $statusCode): string
    {
        return $this->routeTestService->getStatusColour($statusCode);
    }

    /**
     * Parse header string into array.
     */
    protected function parseHeaders(string $headerString): array
    {
        $headers = [];

        if (empty($headerString)) {
            return $headers;
        }

        $lines = explode("\n", $headerString);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[trim($name)] = trim($value);
        }

        return $headers;
    }

    /**
     * Add result to history.
     */
    protected function addToHistory(RouteTestResult $result): void
    {
        array_unshift($this->history, [
            'uri' => $result->uri,
            'method' => $result->method,
            'status_code' => $result->statusCode,
            'response_time' => $result->getFormattedResponseTime(),
            'timestamp' => now()->format('H:i:s'),
            'parameters' => $this->parameters,
            'query_params' => $this->queryParams,
            'body' => $this->bodyContent,
            'headers' => $this->customHeaders,
        ]);

        // Keep only last 20 entries
        $this->history = array_slice($this->history, 0, 20);
    }

    private function checkHadesAccess(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    public function render(): View
    {
        return view('developer::admin.route-inspector');
    }
}
