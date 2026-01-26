<?php

declare(strict_types=1);

/**
 * Developer module translations (en_GB).
 *
 * Key structure: section.subsection.key
 */

return [
    // Application Logs
    'logs' => [
        'title' => 'Application logs',
        'actions' => [
            'refresh' => 'Refresh',
            'download' => 'Download',
            'clear' => 'Clear logs',
        ],
        'levels' => [
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
            'debug' => 'Debug',
        ],
        'clear_filter' => 'Clear filter',
        'empty' => 'No log entries found.',
    ],

    // Application Routes
    'routes' => [
        'title' => 'Application routes',
        'count' => ':count routes',
        'search_placeholder' => 'Search routes...',
        'clear' => 'Clear',
        'table' => [
            'method' => 'Method',
            'uri' => 'URI',
            'name' => 'Name',
            'action' => 'Action',
        ],
        'empty' => 'No routes found matching your criteria.',
    ],

    // Cache Management
    'cache' => [
        'title' => 'Cache management',
        'cards' => [
            'application' => [
                'title' => 'Application cache',
                'description' => 'Clear cached data',
                'action' => 'Clear cache',
            ],
            'config' => [
                'title' => 'Config cache',
                'description' => 'Clear configuration cache',
                'action' => 'Clear config',
            ],
            'view' => [
                'title' => 'View cache',
                'description' => 'Clear compiled Blade views',
                'action' => 'Clear views',
            ],
            'route' => [
                'title' => 'Route cache',
                'description' => 'Clear route cache',
                'action' => 'Clear routes',
            ],
            'all' => [
                'title' => 'Clear all',
                'description' => 'Clear all caches at once',
                'action' => 'Clear all caches',
            ],
            'optimise' => [
                'title' => 'Optimise',
                'description' => 'Cache config, routes & views',
                'action' => 'Optimise',
            ],
        ],
        'last_action' => 'Last action',
    ],

    // Route Inspector
    'route_inspector' => [
        'title' => 'Route inspector',
        'description' => 'Test and inspect application routes interactively.',
        'search_placeholder' => 'Search routes...',
        'filters' => [
            'clear' => 'Clear filters',
        ],
        'table' => [
            'method' => 'Method',
            'uri' => 'URI',
            'name' => 'Name',
            'actions' => 'Actions',
        ],
        'actions' => [
            'test' => 'Test',
            'inspect' => 'Inspect',
            'execute' => 'Execute request',
            'copy_curl' => 'Copy as cURL',
            'copy_response' => 'Copy response',
        ],
        'request' => [
            'title' => 'Request builder',
            'route_params' => 'Route parameters',
            'query_params' => 'Query parameters',
            'body' => 'Request body (JSON)',
            'headers' => 'Custom headers',
            'add_param' => 'Add parameter',
            'use_auth' => 'Use current session authentication',
        ],
        'response' => [
            'title' => 'Response',
            'headers' => 'Headers',
            'body' => 'Body',
        ],
        'history' => [
            'title' => 'Recent tests',
            'clear' => 'Clear history',
            'empty' => 'No tests run yet.',
        ],
        'warnings' => [
            'testing_disabled' => 'Route testing is only available in local and testing environments.',
            'destructive' => 'This is a :method request. It may modify data in your local database.',
            'requires_auth' => 'Route requires authentication',
        ],
        'empty' => 'No routes found matching your criteria.',
    ],
];
