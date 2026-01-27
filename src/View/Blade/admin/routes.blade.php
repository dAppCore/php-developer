{{--
Application Routes viewer.

Displays all registered Laravel routes with filtering.
Click on a route to open it in the Route Inspector for testing.
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <core:heading size="xl">{{ __('developer::developer.routes.title') }}</core:heading>
        <div class="flex items-center gap-4">
            <div class="text-sm text-zinc-500">
                {{ __('developer::developer.routes.count', ['count' => count($this->filteredRoutes)]) }}
            </div>
            <flux:button href="{{ route('hub.dev.route-inspector') }}" variant="ghost" size="sm" icon="beaker">
                Route Inspector
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-4">
        {{-- Search --}}
        <div class="w-64">
            <core:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="{{ __('developer::developer.routes.search_placeholder') }}"
                icon="magnifying-glass"
            />
        </div>

        {{-- Method filter --}}
        <div class="flex items-center gap-2">
            @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                @php
                    $methodColors = [
                        'GET' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'POST' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        'PUT' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'PATCH' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        'DELETE' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    ];
                @endphp
                <button
                    wire:click="setMethod('{{ $method }}')"
                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium transition {{ $methodFilter === $method ? 'ring-2 ring-offset-1 ring-zinc-400' : '' }} {{ $methodColors[$method] }}"
                >
                    {{ $method }}
                </button>
            @endforeach
        </div>

        @if($search || $methodFilter)
            <core:button wire:click="$set('search', ''); $set('methodFilter', '')" variant="ghost" size="sm">
                <core:icon name="x-mark" class="h-4 w-4" />
                {{ __('developer::developer.routes.clear') }}
            </core:button>
        @endif
    </div>

    {{-- Routes table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                        {{ __('developer::developer.routes.table.method') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                        {{ __('developer::developer.routes.table.uri') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                        {{ __('developer::developer.routes.table.name') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                        {{ __('developer::developer.routes.table.action') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">
                        Test
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($this->filteredRoutes as $route)
                    @php
                        $methodColors = [
                            'GET' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'POST' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                            'PUT' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            'PATCH' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                            'DELETE' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            'ANY' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                        ];
                        $color = $methodColors[$route['method']] ?? 'bg-zinc-100 text-zinc-700';
                        $inspectorUrl = route('hub.dev.route-inspector', [
                            'search' => $route['uri'],
                            'methodFilter' => $route['method'],
                        ]);
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 group">
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium {{ $color }}">
                                {{ $route['method'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm text-zinc-900 dark:text-white">
                            <a
                                href="{{ $inspectorUrl }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400 hover:underline"
                            >
                                {{ $route['uri'] }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $route['name'] ?? '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs" title="{{ $route['action'] }}">
                            {{ $route['action'] }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a
                                href="{{ $inspectorUrl }}"
                                class="inline-flex items-center gap-1 text-xs text-zinc-500 hover:text-blue-600 dark:hover:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <flux:icon name="beaker" class="w-4 h-4" />
                                Test
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center">
                            <core:icon name="map" class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('developer::developer.routes.empty') }}
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
