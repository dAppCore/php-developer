<?php

declare(strict_types=1);

namespace Core\Developer\View\Modal\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Core\Developer\Services\LogReaderService;

#[Title('Application Logs')]
#[Layout('hub::admin.layouts.app')]
class Logs extends Component
{
    public array $logs = [];

    public int $limit = 50;

    public string $levelFilter = '';

    public function mount(): void
    {
        $this->checkHadesAccess();
        $this->loadLogs();
    }

    public function loadLogs(): void
    {
        $logReader = app(LogReaderService::class);
        $logFile = $logReader->getDefaultLogPath();

        $levelFilter = $this->levelFilter ?: null;
        $this->logs = $logReader->readLogEntries($logFile, maxLines: 500, levelFilter: $levelFilter);

        // Reverse to show most recent first and limit
        $this->logs = array_slice(array_reverse($this->logs), 0, $this->limit);
    }

    public function refresh(): void
    {
        $this->loadLogs();
    }

    public function setLevel(string $level): void
    {
        $this->levelFilter = $level === $this->levelFilter ? '' : $level;
        $this->loadLogs();
    }

    public function clearLogs(): void
    {
        $logReader = app(LogReaderService::class);
        $logFile = $logReader->getDefaultLogPath();
        $previousSize = $logReader->clearLogFile($logFile);

        if ($previousSize !== false) {
            // Audit log the clear action
            Log::info('Application logs cleared', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'previous_size_bytes' => $previousSize,
                'ip' => request()->ip(),
            ]);
        }

        $this->logs = [];
    }

    public function downloadLogs()
    {
        $logReader = app(LogReaderService::class);
        $logFile = $logReader->getCurrentLogPath();

        if (! file_exists($logFile)) {
            session()->flash('error', 'Log file not found.');

            return;
        }

        $filename = 'laravel-'.date('Y-m-d-His').'.log';

        return response()->streamDownload(function () use ($logFile, $logReader) {
            // Read the file and redact sensitive data before sending
            $handle = fopen($logFile, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    echo $logReader->redactSensitiveData($line);
                }
                fclose($handle);
            }
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    private function checkHadesAccess(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    public function render(): View
    {
        return view('developer::admin.logs');
    }
}
