{{--
Application Logs viewer.

Displays recent Laravel log entries with filtering by level.
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <core:heading size="xl">{{ __('developer::developer.logs.title') }}</core:heading>
        <div class="flex items-center gap-2">
            <core:button wire:click="refresh" variant="ghost" size="sm">
                <core:icon name="arrow-path" class="h-4 w-4" />
                {{ __('developer::developer.logs.actions.refresh') }}
            </core:button>
            <core:button wire:click="downloadLogs" variant="ghost" size="sm">
                <core:icon name="arrow-down-tray" class="h-4 w-4" />
                {{ __('developer::developer.logs.actions.download') }}
            </core:button>
            <core:button wire:click="clearLogs" variant="danger" size="sm">
                <core:icon name="trash" class="h-4 w-4" />
                {{ __('developer::developer.logs.actions.clear') }}
            </core:button>
        </div>
    </div>

    {{-- Level filters --}}
    <div class="flex flex-wrap items-center gap-2">
        @foreach(['error', 'warning', 'info', 'debug'] as $level)
            @php
                $levelColors = [
                    'error' => 'red',
                    'warning' => 'amber',
                    'info' => 'blue',
                    'debug' => 'zinc',
                ];
                $color = $levelColors[$level] ?? 'zinc';
            @endphp
            <core:button
                wire:click="setLevel('{{ $level }}')"
                variant="{{ $levelFilter === $level ? 'primary' : 'ghost' }}"
                size="sm"
            >
                {{ __('developer::developer.logs.levels.' . $level) }}
            </core:button>
        @endforeach

        @if($levelFilter)
            <core:button wire:click="setLevel('')" variant="ghost" size="sm">
                <core:icon name="x-mark" class="h-4 w-4" />
                {{ __('developer::developer.logs.clear_filter') }}
            </core:button>
        @endif
    </div>

    {{-- Logs list --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @forelse($logs as $log)
            @php
                $levelColors = [
                    'error' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                    'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'debug' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
                ];
                $color = $levelColors[$log['level']] ?? 'bg-zinc-100 text-zinc-700';
            @endphp
            <div class="flex items-start gap-4 border-b border-zinc-100 p-4 last:border-0 dark:border-zinc-700">
                {{-- Level badge --}}
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $color }}">
                        {{ $log['level'] }}
                    </span>
                </div>

                {{-- Message --}}
                <div class="min-w-0 flex-1">
                    <p class="font-mono text-sm text-zinc-900 dark:text-white break-all">
                        {{ $log['message'] }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-400">
                        {{ $log['time'] }}
                    </p>
                </div>
            </div>
        @empty
            <div class="px-4 py-12 text-center">
                <core:icon name="document-text" class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('developer::developer.logs.empty') }}
                </p>
            </div>
        @endforelse
    </div>
</div>
