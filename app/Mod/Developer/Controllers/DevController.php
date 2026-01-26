<?php

namespace Mod\Developer\Controllers;

use Core\Front\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mod\Developer\Services\LogReaderService;

class DevController extends Controller
{
    public function __construct(
        protected LogReaderService $logReader
    ) {}


    /**
     * Get recent log entries.
     */
    public function logs(): JsonResponse
    {
        $logFile = $this->logReader->getDefaultLogPath();
        $logs = $this->logReader->readLogEntries($logFile, maxLines: 100);

        // Truncate messages and return most recent first, limited to 20
        $logs = array_map(function (array $log): array {
            $log['message'] = Str::limit($log['message'], 200);

            return $log;
        }, $logs);

        return response()->json(array_slice(array_reverse($logs), 0, 20));
    }

    /**
     * Get all routes.
     */
    public function routes(): JsonResponse
    {
        $this->authorize();

        $routes = collect(Route::getRoutes())->map(function ($route) {
            $methods = $route->methods();
            $method = $methods[0] ?? 'ANY';

            // Skip HEAD method entries
            if ($method === 'HEAD') {
                return null;
            }

            return [
                'method' => $method,
                'uri' => '/'.ltrim($route->uri(), '/'),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        })->filter()->values()->toArray();

        return response()->json($routes);
    }

    /**
     * Get session and request info.
     */
    public function session(Request $request): JsonResponse
    {
        $this->authorize();

        return response()->json([
            'id' => session()->getId(),
            'ip' => $request->ip(),
            'user_agent' => Str::limit($request->userAgent(), 100),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);
    }

    /**
     * Clear cache.
     */
    public function clear(string $type): JsonResponse
    {
        $this->authorize();

        $commands = [
            'cache' => 'cache:clear',
            'config' => 'config:clear',
            'view' => 'view:clear',
            'route' => 'route:clear',
            'all' => ['cache:clear', 'config:clear', 'view:clear', 'route:clear'],
        ];

        if (! isset($commands[$type])) {
            return response()->json(['message' => 'Invalid cache type'], 400);
        }

        $toRun = is_array($commands[$type]) ? $commands[$type] : [$commands[$type]];
        $output = [];

        foreach ($toRun as $command) {
            Artisan::call($command);
            $output[] = trim(Artisan::output());
        }

        return response()->json([
            'message' => implode("\n", $output),
            'type' => $type,
        ]);
    }
}
