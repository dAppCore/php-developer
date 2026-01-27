{{--
Database Query Tool.

Execute read-only SQL queries against the application database.
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <core:heading size="xl">Database Query</core:heading>
    </div>

    {{-- Warning banner --}}
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <core:icon name="exclamation-triangle" class="h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
            <div class="text-sm text-amber-800 dark:text-amber-200">
                <p class="font-medium">Read-only access</p>
                <p class="mt-1">Only SELECT, SHOW, DESCRIBE, and EXPLAIN queries are permitted. Results limited to {{ $maxRows }} rows.</p>
            </div>
        </div>
    </div>

    {{-- Connection info --}}
    <div class="flex flex-wrap items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
        <div class="flex items-center gap-2">
            <core:icon name="circle-stack" class="h-4 w-4" />
            <span>Database: <strong class="text-zinc-900 dark:text-white">{{ $this->connectionInfo['database'] }}</strong></span>
        </div>
        <div class="flex items-center gap-2">
            <core:icon name="server" class="h-4 w-4" />
            <span>Driver: <strong class="text-zinc-900 dark:text-white">{{ $this->connectionInfo['driver'] }}</strong></span>
        </div>
    </div>

    {{-- Query input --}}
    <div class="space-y-3">
        <flux:textarea
            wire:model="query"
            placeholder="SELECT * FROM users LIMIT 10;"
            rows="4"
            class="font-mono text-sm"
        />
        <div class="flex items-center gap-2">
            <flux:button
                wire:click="executeQuery"
                wire:loading.attr="disabled"
                :disabled="$processing"
                variant="primary"
                icon="play"
            >
                <span wire:loading.remove wire:target="executeQuery">Execute</span>
                <span wire:loading wire:target="executeQuery">Executing...</span>
            </flux:button>
            <flux:button
                wire:click="clearQuery"
                variant="ghost"
                icon="x-mark"
            >
                Clear
            </flux:button>
        </div>
    </div>

    {{-- Error display --}}
    @if($error)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
            <div class="flex items-start gap-3">
                <core:icon name="x-circle" class="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                <div class="text-sm text-red-800 dark:text-red-200">
                    <p class="font-medium">Query failed</p>
                    <p class="mt-1 font-mono">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Results --}}
    @if(!empty($results))
        <div class="space-y-3">
            {{-- Result stats --}}
            <div class="flex items-center justify-between text-sm text-zinc-600 dark:text-zinc-400">
                <div class="flex items-center gap-4">
                    <span>
                        @if($rowCount > count($results))
                            Showing {{ count($results) }} of {{ number_format($rowCount) }} rows (limited)
                        @else
                            {{ number_format($rowCount) }} {{ Str::plural('row', $rowCount) }}
                        @endif
                    </span>
                    <span>{{ $executionTime }}ms</span>
                </div>
                <span>{{ count($columns) }} {{ Str::plural('column', count($columns)) }}</span>
            </div>

            {{-- Results table --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <flux:table>
                    <flux:table.columns>
                        @foreach($columns as $column)
                            <flux:table.column class="font-mono text-xs">{{ $column }}</flux:table.column>
                        @endforeach
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($results as $row)
                            <flux:table.row>
                                @foreach($columns as $column)
                                    <flux:table.cell class="font-mono text-xs max-w-xs truncate">
                                        @if(is_null($row[$column]))
                                            <span class="text-zinc-400 italic">NULL</span>
                                        @elseif(is_bool($row[$column]))
                                            <span class="text-blue-600 dark:text-blue-400">{{ $row[$column] ? 'true' : 'false' }}</span>
                                        @elseif(is_numeric($row[$column]))
                                            <span class="text-emerald-600 dark:text-emerald-400">{{ $row[$column] }}</span>
                                        @else
                                            {{ Str::limit((string) $row[$column], 100) }}
                                        @endif
                                    </flux:table.cell>
                                @endforeach
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @elseif(!$error && $query && !$processing && $rowCount === 0 && !empty($columns))
        {{-- Empty result set --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <core:icon name="table-cells" class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Query returned no results.</p>
        </div>
    @endif
</div>
