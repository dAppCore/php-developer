{{--
Route Inspector - Interactive route testing tool.

Test routes with custom parameters, headers, and body content.
Only available in local/testing environments.
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <core:heading size="xl">{{ __('developer::developer.route_inspector.title') }}</core:heading>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('developer::developer.route_inspector.description') }}
            </p>
        </div>
        <div class="text-sm text-zinc-500">
            {{ count($this->filteredRoutes) }} {{ Str::plural('route', count($this->filteredRoutes)) }}
        </div>
    </div>

    {{-- Environment warning --}}
    @unless($this->testingAllowed)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>Testing disabled</flux:callout.heading>
            <flux:callout.text>
                Route testing is only available in local and testing environments for security reasons.
            </flux:callout.text>
        </flux:callout>
    @endunless

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Routes list (left panel) --}}
        <div class="xl:col-span-2 space-y-4">
            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-4">
                {{-- Search --}}
                <div class="w-64">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="Search routes..."
                        icon="magnifying-glass"
                    />
                </div>

                {{-- Method filter --}}
                <div class="flex items-center gap-2">
                    @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method)
                        <button
                            wire:click="setMethod('{{ $method }}')"
                            class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium transition {{ $methodFilter === $method ? 'ring-2 ring-offset-1 ring-zinc-400' : '' }} {{ $this->getMethodColour($method) }}"
                        >
                            {{ $method }}
                        </button>
                    @endforeach
                </div>

                @if($search || $methodFilter)
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                        Clear
                    </flux:button>
                @endif
            </div>

            {{-- Routes table --}}
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="max-h-[600px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 sticky top-0">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                                    Method
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                                    URI
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">
                                    Name
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($this->filteredRoutes as $route)
                                <tr
                                    wire:key="route-{{ md5($route['method'] . $route['uri']) }}"
                                    class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition"
                                    wire:click="inspectRoute('{{ $route['uri'] }}', '{{ $route['method'] }}')"
                                >
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium {{ $this->getMethodColour($route['method']) }}">
                                            {{ $route['method'] }}
                                        </span>
                                        @if($route['is_authenticated'])
                                            <flux:icon name="lock-closed" class="inline-block ml-1 w-3 h-3 text-zinc-400" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm text-zinc-900 dark:text-white">
                                        {{ $route['uri'] }}
                                        @if(count($route['parameters'] ?? []) > 0)
                                            <span class="text-zinc-400 text-xs ml-1">
                                                ({{ count($route['parameters']) }} {{ Str::plural('param', count($route['parameters'])) }})
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $route['name'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right" wire:click.stop>
                                        <div class="flex items-center justify-end gap-1">
                                            @if($route['method'] === 'GET' && empty($route['parameters']))
                                                <flux:button
                                                    wire:click="quickTest('{{ $route['uri'] }}', '{{ $route['method'] }}')"
                                                    wire:loading.attr="disabled"
                                                    variant="ghost"
                                                    size="xs"
                                                    icon="play"
                                                    :disabled="!$this->testingAllowed"
                                                >
                                                    Test
                                                </flux:button>
                                            @endif
                                            <flux:button
                                                wire:click="inspectRoute('{{ $route['uri'] }}', '{{ $route['method'] }}')"
                                                variant="ghost"
                                                size="xs"
                                                icon="magnifying-glass"
                                            >
                                                Inspect
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center">
                                        <flux:icon name="map" class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                            No routes found matching your criteria.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- History panel (right sidebar) --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Recent Tests</h3>
                    @if(count($history) > 0)
                        <flux:button wire:click="clearHistory" variant="ghost" size="xs" icon="trash">
                            Clear
                        </flux:button>
                    @endif
                </div>

                @if(count($history) > 0)
                    <div class="space-y-2 max-h-[500px] overflow-y-auto">
                        @foreach($history as $index => $item)
                            <button
                                wire:click="loadFromHistory({{ $index }})"
                                class="w-full text-left p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium {{ $this->getMethodColour($item['method']) }}">
                                        {{ $item['method'] }}
                                    </span>
                                    <span class="font-mono text-xs text-zinc-700 dark:text-zinc-300 truncate flex-1">
                                        {{ $item['uri'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-1 text-xs text-zinc-500">
                                    <span class="{{ $this->getStatusColour($item['status_code']) }}">
                                        {{ $item['status_code'] }}
                                    </span>
                                    <span>{{ $item['response_time'] }}</span>
                                    <span>{{ $item['timestamp'] }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">
                        No tests run yet. Click on a route to inspect and test it.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Inspector modal/drawer --}}
    <flux:modal wire:model="showInspector" class="max-w-4xl">
        @if($selectedRoute)
            <div class="space-y-6">
                {{-- Route header --}}
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded px-2 py-1 text-sm font-medium {{ $this->getMethodColour($selectedRoute['method']) }}">
                                {{ $selectedRoute['method'] }}
                            </span>
                            <code class="font-mono text-lg text-zinc-900 dark:text-white">{{ $selectedRoute['uri'] }}</code>
                        </div>
                        @if($selectedRoute['name'])
                            <p class="mt-1 text-sm text-zinc-500">
                                <span class="font-medium">Name:</span> {{ $selectedRoute['name'] }}
                            </p>
                        @endif
                        <p class="mt-1 text-sm text-zinc-500">
                            <span class="font-medium">Action:</span>
                            <code class="text-xs">{{ $selectedRoute['action'] }}</code>
                        </p>
                    </div>

                    @if($selectedRoute['is_destructive'])
                        <flux:badge color="red" size="sm" icon="exclamation-triangle">
                            Destructive
                        </flux:badge>
                    @endif
                </div>

                {{-- Route details --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    @if($selectedRoute['middleware_string'])
                        <div class="col-span-2">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Middleware:</span>
                            <code class="ml-2 text-xs text-zinc-500">{{ $selectedRoute['middleware_string'] }}</code>
                        </div>
                    @endif

                    @if(count($selectedRoute['methods'] ?? []) > 1)
                        <div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Methods:</span>
                            <div class="flex gap-1 mt-1">
                                @foreach($selectedRoute['methods'] as $method)
                                    <button
                                        wire:click="$set('selectedMethod', '{{ $method }}')"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium transition {{ $selectedMethod === $method ? 'ring-2 ring-offset-1' : 'opacity-50' }} {{ $this->getMethodColour($method) }}"
                                    >
                                        {{ $method }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Destructive warning --}}
                @if(in_array($selectedMethod, ['DELETE', 'PUT', 'PATCH', 'POST']))
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.text>
                            This is a {{ $selectedMethod }} request. It may modify data in your local database.
                        </flux:callout.text>
                    </flux:callout>
                @endif

                {{-- Request builder --}}
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6 space-y-4">
                    <h4 class="font-semibold text-zinc-900 dark:text-white">Request Builder</h4>

                    {{-- Route parameters --}}
                    @if(count($selectedRoute['parameters'] ?? []) > 0)
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Route Parameters
                            </label>
                            @foreach($selectedRoute['parameters'] as $param)
                                <flux:input
                                    wire:model="parameters.{{ $param['name'] }}"
                                    label="{{ $param['name'] }}{{ $param['required'] ? ' *' : '' }}"
                                    placeholder="{{ $param['pattern'] ? 'Pattern: ' . $param['pattern'] : 'Enter value...' }}"
                                />
                            @endforeach
                        </div>
                    @endif

                    {{-- Query parameters --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Query Parameters
                            </label>
                            <flux:button wire:click="addQueryParam" variant="ghost" size="xs" icon="plus">
                                Add
                            </flux:button>
                        </div>
                        @foreach($queryParams as $index => $param)
                            <div class="flex items-center gap-2" wire:key="query-param-{{ $index }}">
                                <flux:input
                                    wire:model="queryParams.{{ $index }}.key"
                                    placeholder="Key"
                                    class="flex-1"
                                />
                                <flux:input
                                    wire:model="queryParams.{{ $index }}.value"
                                    placeholder="Value"
                                    class="flex-1"
                                />
                                <flux:button
                                    wire:click="removeQueryParam({{ $index }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="x-mark"
                                />
                            </div>
                        @endforeach
                    </div>

                    {{-- Body (for POST/PUT/PATCH) --}}
                    @if(in_array($selectedMethod, ['POST', 'PUT', 'PATCH']))
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Request Body (JSON)
                            </label>
                            <flux:textarea
                                wire:model="bodyContent"
                                rows="4"
                                placeholder='{"key": "value"}'
                                class="font-mono text-sm"
                            />
                        </div>
                    @endif

                    {{-- Custom headers --}}
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Custom Headers (one per line: Name: Value)
                        </label>
                        <flux:textarea
                            wire:model="customHeaders"
                            rows="2"
                            placeholder="Authorization: Bearer token&#10;X-Custom-Header: value"
                            class="font-mono text-sm"
                        />
                    </div>

                    {{-- Authentication option --}}
                    <div class="flex items-center gap-3">
                        <flux:checkbox
                            wire:model="useAuthentication"
                            label="Use current session authentication"
                        />
                        @if($selectedRoute['is_authenticated'])
                            <flux:badge size="sm" color="amber">
                                Route requires auth
                            </flux:badge>
                        @endif
                    </div>
                </div>

                {{-- Execute button --}}
                <div class="flex items-center gap-3">
                    <flux:button
                        wire:click="executeTest"
                        wire:loading.attr="disabled"
                        variant="primary"
                        icon="play"
                        :disabled="!$this->testingAllowed"
                    >
                        <span wire:loading.remove wire:target="executeTest">Execute Request</span>
                        <span wire:loading wire:target="executeTest">Executing...</span>
                    </flux:button>

                    <flux:button wire:click="copyAsCurl" variant="ghost" size="sm" icon="clipboard">
                        Copy as cURL
                    </flux:button>
                </div>

                {{-- Response panel --}}
                @if($lastResult)
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-semibold text-zinc-900 dark:text-white">Response</h4>
                            <div class="flex items-center gap-4 text-sm">
                                <span class="{{ $this->getStatusColour($lastResult['status_code']) }} font-semibold">
                                    {{ $lastResult['status_code'] }} {{ $lastResult['status_text'] }}
                                </span>
                                @if(isset($lastResult['response_time_formatted']))
                                    <span class="text-zinc-500">{{ $lastResult['response_time_formatted'] }}</span>
                                @endif
                                @if(isset($lastResult['memory_usage_formatted']))
                                    <span class="text-zinc-500">{{ $lastResult['memory_usage_formatted'] }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Exception display --}}
                        @if(isset($lastResult['exception']) && $lastResult['exception'])
                            <flux:callout variant="danger" icon="exclamation-circle">
                                <flux:callout.heading>{{ $lastResult['exception']['class'] }}</flux:callout.heading>
                                <flux:callout.text>
                                    {{ $lastResult['exception']['message'] }}
                                    <br>
                                    <code class="text-xs">{{ $lastResult['exception']['file'] }}:{{ $lastResult['exception']['line'] }}</code>
                                </flux:callout.text>
                            </flux:callout>
                        @endif

                        {{-- Response headers --}}
                        @if(isset($lastResult['headers']) && count($lastResult['headers']) > 0)
                            <details class="text-sm">
                                <summary class="cursor-pointer font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white">
                                    Headers ({{ count($lastResult['headers']) }})
                                </summary>
                                <div class="mt-2 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs space-y-1">
                                    @foreach($lastResult['headers'] as $name => $value)
                                        <div>
                                            <span class="text-zinc-500">{{ $name }}:</span>
                                            <span class="text-zinc-700 dark:text-zinc-300">{{ Str::limit($value, 100) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif

                        {{-- Response body --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Body
                                    @if(isset($lastResult['body_length']))
                                        <span class="font-normal text-zinc-500">({{ number_format($lastResult['body_length']) }} bytes)</span>
                                    @endif
                                </span>
                                <flux:button wire:click="copyResponse" variant="ghost" size="xs" icon="clipboard">
                                    Copy
                                </flux:button>
                            </div>
                            <div class="relative">
                                <pre class="bg-zinc-900 dark:bg-zinc-950 rounded-lg p-4 overflow-x-auto text-sm {{ $lastResult['is_json'] ?? false ? 'text-emerald-400' : 'text-zinc-300' }} whitespace-pre-wrap max-h-[400px] overflow-y-auto">{{ Str::limit($lastResult['body'] ?? '', 10000) }}</pre>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Footer --}}
                <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="closeInspector" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>

@script
<script>
    // Handle copy to clipboard events
    $wire.on('copy-to-clipboard', ({ content }) => {
        navigator.clipboard.writeText(content).then(() => {
            // Success handled by toast in component
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    });
</script>
@endscript
