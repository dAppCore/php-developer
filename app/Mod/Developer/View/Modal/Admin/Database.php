<?php

declare(strict_types=1);

namespace Mod\Developer\View\Modal\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Database Query')]
#[Layout('hub::admin.layouts.app')]
class Database extends Component
{
    public string $query = '';

    public array $results = [];

    public array $columns = [];

    public string $error = '';

    public bool $processing = false;

    public int $rowCount = 0;

    public float $executionTime = 0;

    public int $maxRows = 500;

    protected const MAX_ROWS = 500;

    protected const ALLOWED_STATEMENTS = ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN'];

    public function mount(): void
    {
        $this->checkHadesAccess();
    }

    public function executeQuery(): void
    {
        $this->reset(['results', 'columns', 'error', 'rowCount', 'executionTime']);
        $this->processing = true;

        $normalised = $this->normaliseQuery($this->query);

        if (empty($normalised)) {
            $this->error = 'Please enter a SQL query.';
            $this->processing = false;

            return;
        }

        if (! $this->isReadOnlyQuery($normalised)) {
            $this->error = 'Only read-only queries are allowed (SELECT, SHOW, DESCRIBE, EXPLAIN).';
            $this->processing = false;
            Log::warning('Database query tool: blocked non-read-only query', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'query' => $this->query,
                'ip' => request()->ip(),
            ]);

            return;
        }

        try {
            $startTime = microtime(true);

            $results = DB::select($normalised);

            $this->executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Convert to array and limit results
            $this->results = array_slice(
                array_map(fn ($row) => (array) $row, $results),
                0,
                self::MAX_ROWS
            );

            $this->rowCount = count($results);

            // Extract column names from first result
            if (! empty($this->results)) {
                $this->columns = array_keys($this->results[0]);
            }

            Log::info('Database query executed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'query' => $normalised,
                'row_count' => $this->rowCount,
                'execution_time_ms' => $this->executionTime,
                'ip' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::warning('Database query failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'query' => $normalised,
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
            ]);
        }

        $this->processing = false;
    }

    public function clearQuery(): void
    {
        $this->reset(['query', 'results', 'columns', 'error', 'rowCount', 'executionTime']);
    }

    public function getConnectionInfoProperty(): array
    {
        $connection = DB::connection();

        return [
            'database' => $connection->getDatabaseName(),
            'driver' => $connection->getDriverName(),
        ];
    }

    protected function normaliseQuery(string $query): string
    {
        // Trim whitespace and normalise
        return trim(preg_replace('/\s+/', ' ', $query));
    }

    protected function isReadOnlyQuery(string $query): bool
    {
        // Get first word of query
        $firstWord = strtoupper(strtok($query, ' '));

        return in_array($firstWord, self::ALLOWED_STATEMENTS, true);
    }

    private function checkHadesAccess(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    public function render(): View
    {
        return view('developer::admin.database');
    }
}
