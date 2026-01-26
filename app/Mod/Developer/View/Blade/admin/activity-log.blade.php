{{--
Activity Log Viewer.

Shows activity logs from Spatie activity log package.
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <core:heading size="xl">{{ __('Activity Log') }}</core:heading>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <flux:input wire:model.live.debounce.300ms="searchTerm" placeholder="Search activity..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="filterSubjectType" placeholder="All types">
            <flux:select.option value="">All types</flux:select.option>
            @foreach ($this->subjectTypes as $type)
                <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="filterEvent" placeholder="All events">
            <flux:select.option value="">All events</flux:select.option>
            @foreach ($this->events as $event)
                <flux:select.option value="{{ $event }}">{{ ucfirst($event) }}</flux:select.option>
            @endforeach
        </flux:select>
        @if($searchTerm || $filterSubjectType || $filterEvent)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">Clear</flux:button>
        @endif
    </div>

    {{-- Activity table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Time</flux:table.column>
            <flux:table.column>Event</flux:table.column>
            <flux:table.column>Subject</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Changes</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->activities as $activity)
                <flux:table.row>
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $activity->created_at->format('M j, Y H:i:s') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($activity->event) {
                            'created' => 'green',
                            'updated' => 'blue',
                            'deleted' => 'red',
                            default => 'zinc'
                        }">
                            {{ ucfirst($activity->event ?? 'unknown') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ class_basename($activity->subject_type ?? '') }}</div>
                        @if($activity->subject_id)
                            <div class="text-xs text-zinc-500">ID: {{ $activity->subject_id }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        {{ $activity->description }}
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">
                        @if($activity->causer)
                            {{ $activity->causer->name ?? $activity->causer->email ?? 'System' }}
                        @else
                            <span class="text-zinc-400">System</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($activity->properties && $activity->properties->count())
                            <flux:button
                                x-data
                                x-on:click="$dispatch('show-changes', { properties: {{ json_encode($activity->properties) }} })"
                                variant="ghost"
                                size="xs"
                                icon="eye"
                            >
                                View
                            </flux:button>
                        @else
                            <span class="text-zinc-400 text-xs">—</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="flex flex-col items-center py-12">
                            <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                                <flux:icon name="clock" class="size-8 text-zinc-400" />
                            </div>
                            <flux:heading size="lg">No activity found</flux:heading>
                            <flux:subheading class="mt-1">Activity will appear here as users interact with the system.</flux:subheading>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if($this->activities->hasPages())
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
            {{ $this->activities->links() }}
        </div>
    @endif
</div>
