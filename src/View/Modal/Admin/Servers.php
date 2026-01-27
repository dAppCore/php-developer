<?php

declare(strict_types=1);

namespace Core\Developer\View\Modal\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Core\Developer\Models\Server;

#[Title('Server Management')]
#[Layout('hub::admin.layouts.app')]
class Servers extends Component
{
    // Modal states
    public bool $showEditModal = false;

    public bool $showDeleteConfirmation = false;

    public ?int $editingServerId = null;

    public ?int $deletingServerId = null;

    // Form fields
    public string $name = '';

    public string $ip = '';

    public int $port = 22;

    public string $user = 'root';

    public string $private_key = '';

    // Testing state
    public ?int $testingServerId = null;

    public ?string $testResult = null;

    public bool $testSuccess = false;

    public function mount(): void
    {
        $this->checkHadesAccess();
    }

    #[Computed]
    public function servers()
    {
        return Server::ownedByCurrentWorkspace()->orderBy('name')->get();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingServerId = null;
        $this->showEditModal = true;
    }

    public function openEditModal(int $serverId): void
    {
        $server = Server::findOrFail($serverId);

        $this->editingServerId = $serverId;
        $this->name = $server->name;
        $this->ip = $server->ip;
        $this->port = $server->port;
        $this->user = $server->user;
        $this->private_key = ''; // Never expose the key - leave empty to keep existing

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'ip' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'user' => 'required|string|max:255',
        ];

        // Private key is required for new servers
        if (! $this->editingServerId) {
            $rules['private_key'] = 'required|string';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'ip' => $this->ip,
            'port' => $this->port,
            'user' => $this->user,
            'status' => 'pending',
        ];

        // Only update private key if provided
        if (! empty($this->private_key)) {
            $data['private_key'] = $this->private_key;
        }

        if ($this->editingServerId) {
            $server = Server::findOrFail($this->editingServerId);
            $server->update($data);
            Flux::toast('Server updated successfully');
        } else {
            Server::create($data);
            Flux::toast('Server created successfully');
        }

        $this->closeEditModal();
        unset($this->servers);
    }

    public function confirmDelete(int $serverId): void
    {
        $this->deletingServerId = $serverId;
        $this->showDeleteConfirmation = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirmation = false;
        $this->deletingServerId = null;
    }

    public function deleteServer(): void
    {
        if ($this->deletingServerId) {
            Server::findOrFail($this->deletingServerId)->delete();
            Flux::toast('Server deleted');
        }

        $this->cancelDelete();
        unset($this->servers);
    }

    public function testConnection(int $serverId): void
    {
        $this->testingServerId = $serverId;
        $this->testResult = null;
        $this->testSuccess = false;

        $server = Server::findOrFail($serverId);

        if (! $server->hasPrivateKey()) {
            $this->testResult = 'No private key configured';
            $this->testSuccess = false;
            $server->markAsFailed('No private key configured');
            unset($this->servers);

            return;
        }

        try {
            // Create a temporary key file
            $tempKeyPath = sys_get_temp_dir().'/ssh_test_'.uniqid();
            file_put_contents($tempKeyPath, $server->getDecryptedPrivateKey());
            chmod($tempKeyPath, 0600);

            // Test SSH connection with a simple echo command
            $result = Process::timeout(15)->run([
                'ssh',
                '-i', $tempKeyPath,
                '-o', 'StrictHostKeyChecking=no',
                '-o', 'BatchMode=yes',
                '-o', 'ConnectTimeout=10',
                '-p', (string) $server->port,
                "{$server->user}@{$server->ip}",
                'echo "connected"',
            ]);

            // Clean up temp key
            @unlink($tempKeyPath);

            if ($result->successful() && str_contains($result->output(), 'connected')) {
                $this->testResult = 'Connection successful';
                $this->testSuccess = true;
                $server->update([
                    'status' => 'connected',
                    'last_connected_at' => now(),
                ]);
            } else {
                $errorOutput = $result->errorOutput() ?: $result->output();
                $this->testResult = 'Connection failed: '.($errorOutput ?: 'Unknown error');
                $this->testSuccess = false;
                $server->markAsFailed($errorOutput ?: 'Connection failed');
            }
        } catch (\Exception $e) {
            $this->testResult = 'Connection failed: '.$e->getMessage();
            $this->testSuccess = false;
            $server->markAsFailed($e->getMessage());
        }

        $this->testingServerId = null;
        unset($this->servers);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->ip = '';
        $this->port = 22;
        $this->user = 'root';
        $this->private_key = '';
        $this->editingServerId = null;
        $this->testResult = null;
        $this->testSuccess = false;
    }

    private function checkHadesAccess(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    public function render(): View
    {
        return view('developer::admin.servers');
    }
}
