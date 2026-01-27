<?php

declare(strict_types=1);

namespace Core\Developer\Services;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route as RouteFacade;
use Core\Developer\Data\RouteTestResult;
use Throwable;

/**
 * Service for testing Laravel routes.
 *
 * Provides functionality to execute test requests against routes and format
 * the responses for display in the developer tools.
 *
 * IMPORTANT: This service should only be used in local/development environments.
 */
class RouteTestService
{
    /**
     * Methods that can modify data - require extra confirmation.
     */
    public const array DESTRUCTIVE_METHODS = ['DELETE', 'PATCH', 'PUT', 'POST'];

    /**
     * Methods that are safe to auto-test.
     */
    public const array SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Default headers for test requests.
     */
    protected array $defaultHeaders = [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    /**
     * Check if route testing is allowed in current environment.
     */
    public function isTestingAllowed(): bool
    {
        return App::environment(['local', 'testing']);
    }

    /**
     * Get all available routes for testing.
     *
     * @return array<array{
     *     method: string,
     *     uri: string,
     *     name: string|null,
     *     action: string,
     *     middleware: array,
     *     parameters: array,
     *     domain: string|null,
     *     is_api: bool,
     *     is_destructive: bool
     * }>
     */
    public function getRoutes(): array
    {
        return collect(RouteFacade::getRoutes())
            ->map(fn (Route $route) => $this->formatRoute($route))
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Get a specific route by its name or URI.
     */
    public function findRoute(string $name, ?string $method = null): ?Route
    {
        $routes = RouteFacade::getRoutes();

        // First try to find by name
        if ($route = $routes->getByName($name)) {
            return $route;
        }

        // Then try to find by URI
        foreach ($routes as $route) {
            $uri = '/'.ltrim($route->uri(), '/');
            if ($uri === $name || $route->uri() === ltrim($name, '/')) {
                if ($method === null || in_array(strtoupper($method), $route->methods())) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Format a route for display.
     */
    public function formatRoute(Route $route): ?array
    {
        $methods = $route->methods();
        $method = $methods[0] ?? 'ANY';

        // Skip HEAD-only routes
        if ($method === 'HEAD' && count($methods) === 1) {
            return null;
        }

        // Prefer GET over HEAD when both present
        if ($method === 'HEAD' && in_array('GET', $methods)) {
            $method = 'GET';
        }

        $middleware = $route->gatherMiddleware();

        return [
            'method' => $method,
            'methods' => array_filter($methods, fn ($m) => $m !== 'HEAD'),
            'uri' => '/'.ltrim($route->uri(), '/'),
            'name' => $route->getName(),
            'action' => $this->formatAction($route->getActionName()),
            'action_full' => $route->getActionName(),
            'middleware' => $middleware,
            'middleware_string' => implode(', ', $middleware),
            'parameters' => $this->extractRouteParameters($route),
            'domain' => $route->getDomain(),
            'is_api' => $this->isApiRoute($route),
            'is_destructive' => in_array($method, self::DESTRUCTIVE_METHODS),
            'is_authenticated' => $this->requiresAuthentication($middleware),
            'has_csrf' => $this->requiresCsrf($middleware, $method),
        ];
    }

    /**
     * Extract parameters from a route.
     *
     * @return array<array{name: string, required: bool, pattern: string|null}>
     */
    public function extractRouteParameters(Route $route): array
    {
        $parameters = [];
        $parameterNames = $route->parameterNames();
        $patterns = $route->wheres ?? [];
        $uri = $route->uri();

        foreach ($parameterNames as $name) {
            // Check if parameter is optional (wrapped in {?param})
            $isOptional = preg_match('/\{'.preg_quote($name, '/').'?\??\}/', $uri, $matches)
                && str_contains($matches[0] ?? '', '?');

            $parameters[] = [
                'name' => $name,
                'required' => ! $isOptional,
                'pattern' => $patterns[$name] ?? null,
            ];
        }

        return $parameters;
    }

    /**
     * Build a test request for a route.
     */
    public function buildTestRequest(
        Route $route,
        string $method = 'GET',
        array $parameters = [],
        array $queryParams = [],
        array $bodyParams = [],
        array $headers = [],
    ): Request {
        // Build URI with parameters
        $uri = $this->buildUri($route, $parameters, $queryParams);

        // Merge headers
        $allHeaders = array_merge($this->defaultHeaders, $headers);

        // Create request
        $request = Request::create(
            uri: $uri,
            method: $method,
            parameters: $bodyParams,
            server: $this->convertHeadersToServer($allHeaders),
        );

        // Add JSON content type for body requests
        if (! empty($bodyParams) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $request->headers->set('Content-Type', 'application/json');
        }

        return $request;
    }

    /**
     * Build URI with parameters substituted.
     */
    public function buildUri(Route $route, array $parameters = [], array $queryParams = []): string
    {
        $uri = '/'.ltrim($route->uri(), '/');

        // Replace route parameters
        foreach ($parameters as $name => $value) {
            $uri = preg_replace('/\{'.preg_quote($name, '/').'(\?)?\}/', (string) $value, $uri);
        }

        // Remove any remaining optional parameters
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);

        // Add query string
        if (! empty($queryParams)) {
            $uri .= '?'.http_build_query($queryParams);
        }

        return $uri;
    }

    /**
     * Execute a test request against a route.
     */
    public function executeRequest(
        Route $route,
        string $method = 'GET',
        array $parameters = [],
        array $queryParams = [],
        array $bodyParams = [],
        array $headers = [],
        ?object $authenticatedUser = null,
    ): RouteTestResult {
        if (! $this->isTestingAllowed()) {
            return new RouteTestResult(
                statusCode: 403,
                headers: [],
                body: 'Route testing is only allowed in local/testing environments.',
                responseTime: 0,
                memoryUsage: 0,
                method: $method,
                uri: $this->buildUri($route, $parameters, $queryParams),
            );
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $uri = $this->buildUri($route, $parameters, $queryParams);

        try {
            // Build request
            $request = $this->buildTestRequest(
                $route,
                $method,
                $parameters,
                $queryParams,
                $bodyParams,
                $headers,
            );

            // Set authenticated user if provided
            if ($authenticatedUser) {
                $request->setUserResolver(fn () => $authenticatedUser);
            }

            // Handle the request through the kernel
            $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
            $response = $kernel->handle($request);

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            // Extract response details
            $responseHeaders = [];
            foreach ($response->headers->all() as $name => $values) {
                $responseHeaders[$name] = implode(', ', $values);
            }

            return new RouteTestResult(
                statusCode: $response->getStatusCode(),
                headers: $responseHeaders,
                body: $response->getContent(),
                responseTime: ($endTime - $startTime) * 1000,
                memoryUsage: max(0, $endMemory - $startMemory),
                method: $method,
                uri: $uri,
            );
        } catch (Throwable $e) {
            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            return new RouteTestResult(
                statusCode: 500,
                headers: [],
                body: $e->getMessage(),
                responseTime: ($endTime - $startTime) * 1000,
                memoryUsage: max(0, $endMemory - $startMemory),
                exception: $e,
                method: $method,
                uri: $uri,
            );
        }
    }

    /**
     * Format the response for display.
     */
    public function formatResponse(RouteTestResult $result): array
    {
        return $result->toArray();
    }

    /**
     * Get method colour class.
     */
    public function getMethodColour(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'POST' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'PUT' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'PATCH' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            'DELETE' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'OPTIONS' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
            default => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        };
    }

    /**
     * Get status code colour class.
     */
    public function getStatusColour(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'text-green-600 dark:text-green-400',
            $statusCode >= 300 && $statusCode < 400 => 'text-blue-600 dark:text-blue-400',
            $statusCode >= 400 && $statusCode < 500 => 'text-amber-600 dark:text-amber-400',
            $statusCode >= 500 => 'text-red-600 dark:text-red-400',
            default => 'text-zinc-600 dark:text-zinc-400',
        };
    }

    /**
     * Format action name for display.
     */
    protected function formatAction(string $action): string
    {
        // Shorten common prefixes
        $action = str_replace('App\\Http\\Controllers\\', '', $action);
        $action = str_replace('App\\Mod\\', 'Mod\\', $action);

        return $action;
    }

    /**
     * Check if route is an API route.
     */
    protected function isApiRoute(Route $route): bool
    {
        $uri = $route->uri();
        $middleware = $route->gatherMiddleware();

        return str_starts_with($uri, 'api/')
            || in_array('api', $middleware)
            || str_contains(implode(',', $middleware), 'api');
    }

    /**
     * Check if route requires authentication.
     */
    protected function requiresAuthentication(array $middleware): bool
    {
        $authMiddleware = ['auth', 'auth:sanctum', 'auth:api', 'auth:web'];

        foreach ($middleware as $m) {
            if (in_array($m, $authMiddleware) || str_starts_with($m, 'auth:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if route requires CSRF protection.
     */
    protected function requiresCsrf(array $middleware, string $method): bool
    {
        // Only non-GET methods need CSRF
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return false;
        }

        // Check for web middleware (which includes CSRF)
        return in_array('web', $middleware)
            || in_array('VerifyCsrfToken', $middleware);
    }

    /**
     * Convert headers array to server format.
     */
    protected function convertHeadersToServer(array $headers): array
    {
        $server = [];

        foreach ($headers as $name => $value) {
            $key = 'HTTP_'.strtoupper(str_replace('-', '_', $name));
            $server[$key] = $value;
        }

        return $server;
    }
}
