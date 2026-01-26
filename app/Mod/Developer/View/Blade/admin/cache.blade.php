{{--
Cache Management.

Provides controls for clearing various Laravel caches.
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <core:heading size="xl">{{ __('developer::developer.cache.title') }}</core:heading>
    </div>

    {{-- Cache actions --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- Application cache --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <core:icon name="archive-box" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('developer::developer.cache.cards.application.title') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('developer::developer.cache.cards.application.description') }}</p>
                </div>
            </div>
            <core:button wire:click="requestConfirmation('cache')" class="mt-4 w-full" variant="ghost">
                {{ __('developer::developer.cache.cards.application.action') }}
            </core:button>
        </div>

        {{-- Config cache --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <core:icon name="cog" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('developer::developer.cache.cards.config.title') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('developer::developer.cache.cards.config.description') }}</p>
                </div>
            </div>
            <core:button wire:click="requestConfirmation('config')" class="mt-4 w-full" variant="ghost">
                {{ __('developer::developer.cache.cards.config.action') }}
            </core:button>
        </div>

        {{-- View cache --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <core:icon name="eye" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('developer::developer.cache.cards.view.title') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('developer::developer.cache.cards.view.description') }}</p>
                </div>
            </div>
            <core:button wire:click="requestConfirmation('view')" class="mt-4 w-full" variant="ghost">
                {{ __('developer::developer.cache.cards.view.action') }}
            </core:button>
        </div>

        {{-- Route cache --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <core:icon name="map" class="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('developer::developer.cache.cards.route.title') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('developer::developer.cache.cards.route.description') }}</p>
                </div>
            </div>
            <core:button wire:click="requestConfirmation('route')" class="mt-4 w-full" variant="ghost">
                {{ __('developer::developer.cache.cards.route.action') }}
            </core:button>
        </div>

        {{-- Clear all --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                    <core:icon name="trash" class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('developer::developer.cache.cards.all.title') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('developer::developer.cache.cards.all.description') }}</p>
                </div>
            </div>
            <core:button wire:click="requestConfirmation('all')" class="mt-4 w-full" variant="danger">
                {{ __('developer::developer.cache.cards.all.action') }}
            </core:button>
        </div>

        {{-- Optimise --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <core:icon name="bolt" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('developer::developer.cache.cards.optimise.title') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('developer::developer.cache.cards.optimise.description') }}</p>
                </div>
            </div>
            <core:button wire:click="requestConfirmation('optimize')" class="mt-4 w-full" variant="primary">
                {{ __('developer::developer.cache.cards.optimise.action') }}
            </core:button>
        </div>
    </div>

    {{-- Last action output --}}
    @if($lastOutput)
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                <core:icon name="check-circle" class="h-4 w-4 text-green-500" />
                {{ __('developer::developer.cache.last_action') }}: {{ $lastAction }}
            </div>
            <pre class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{{ $lastOutput }}</pre>
        </div>
    @endif

    {{-- Confirmation modal --}}
    <flux:modal wire:model="showConfirmation" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Confirm Action</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $confirmationMessage }}</p>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="cancelAction" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="confirmAction" variant="danger">Confirm</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
