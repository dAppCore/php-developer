{{--
Server Management.

CRUD interface for managing SSH server connections.
--}}

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <core:heading size="xl">Server Management</core:heading>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">
            Add Server
        </flux:button>
    </div>

    {{-- Test result notification --}}
    @if($testResult)
        <flux:callout :variant="$testSuccess ? 'success' : 'danger'" :icon="$testSuccess ? 'check-circle' : 'exclamation-circle'">
            {{ $testResult }}
        </flux:callout>
    @endif

    {{-- Servers table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Connection</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Last Connected</flux:table.column>
            <flux:table.column class="text-right">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->servers as $server)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $server->name }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <code class="text-sm bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded font-mono">
                            {{ $server->connection_string }}
                        </code>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($server->status) {
                            'connected' => 'green',
                            'pending' => 'amber',
                            'failed' => 'red',
                            default => 'zinc'
                        }">
                            {{ ucfirst($server->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm text-zinc-500">
                        {{ $server->last_connected_at?->diffForHumans() ?? 'Never' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-2">
                            <flux:button
                                wire:click="testConnection({{ $server->id }})"
                                wire:loading.attr="disabled"
                                wire:target="testConnection({{ $server->id }})"
                                variant="ghost"
                                size="sm"
                                icon="signal"
                            >
                                <span wire:loading.remove wire:target="testConnection({{ $server->id }})">Test</span>
                                <span wire:loading wire:target="testConnection({{ $server->id }})">Testing...</span>
                            </flux:button>
                            <flux:button
                                wire:click="openEditModal({{ $server->id }})"
                                variant="ghost"
                                size="sm"
                                icon="pencil"
                            >
                                Edit
                            </flux:button>
                            <flux:button
                                wire:click="confirmDelete({{ $server->id }})"
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                class="text-red-600 hover:text-red-700"
                            >
                                Delete
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="flex flex-col items-center py-12">
                            <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                                <flux:icon name="server" class="size-8 text-zinc-400" />
                            </div>
                            <flux:heading size="lg">No servers configured</flux:heading>
                            <flux:subheading class="mt-1">Add your first server to get started with remote management.</flux:subheading>
                            <flux:button wire:click="openCreateModal" variant="primary" icon="plus" class="mt-4">
                                Add Server
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Create/Edit modal --}}
    <flux:modal wire:model="showEditModal" class="max-w-lg">
        <div class="space-y-6">
            <flux:heading size="lg">
                {{ $editingServerId ? 'Edit Server' : 'Add Server' }}
            </flux:heading>

            <div class="space-y-4">
                {{-- Server name --}}
                <div>
                    <flux:label for="name">Server Name</flux:label>
                    <flux:input
                        id="name"
                        wire:model="name"
                        placeholder="Production Server"
                    />
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- IP Address --}}
                <div>
                    <flux:label for="ip">IP Address or Hostname</flux:label>
                    <flux:input
                        id="ip"
                        wire:model="ip"
                        placeholder="192.168.1.100 or server.example.com"
                    />
                    @error('ip')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Port and User --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:label for="port">SSH Port</flux:label>
                        <flux:input
                            id="port"
                            wire:model="port"
                            type="number"
                            min="1"
                            max="65535"
                        />
                        @error('port')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:label for="user">SSH User</flux:label>
                        <flux:input
                            id="user"
                            wire:model="user"
                            placeholder="root"
                        />
                        @error('user')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Private Key --}}
                <div>
                    <flux:label for="private_key">
                        Private Key
                        @if($editingServerId)
                            <span class="text-zinc-500 font-normal">(leave empty to keep existing)</span>
                        @endif
                    </flux:label>
                    <flux:textarea
                        id="private_key"
                        wire:model="private_key"
                        rows="6"
                        placeholder="-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----"
                        class="font-mono text-xs"
                    />
                    @error('private_key')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-zinc-500">
                        The private key is encrypted at rest and never displayed after saving.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeEditModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="save" variant="primary">
                    {{ $editingServerId ? 'Update Server' : 'Add Server' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete confirmation modal --}}
    <flux:modal wire:model="showDeleteConfirmation" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete Server</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Are you sure you want to delete this server? This action cannot be undone.
            </p>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="cancelDelete" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="deleteServer" variant="danger">Delete Server</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
