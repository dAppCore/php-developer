<?php

declare(strict_types=1);

namespace Mod\Developer\View\Modal\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Title('Activity Log')]
#[Layout('hub::admin.layouts.app')]
class ActivityLog extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $searchTerm = '';

    #[Url(as: 'type')]
    public string $filterSubjectType = '';

    #[Url(as: 'event')]
    public string $filterEvent = '';

    public function updatingSearchTerm(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSubjectType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEvent(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->checkHadesAccess();
    }

    #[Computed]
    public function activities()
    {
        $query = Activity::query()
            ->with('causer')
            ->latest();

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('subject_type', 'like', '%'.$this->searchTerm.'%');
            });
        }

        if ($this->filterSubjectType) {
            $query->where('subject_type', $this->filterSubjectType);
        }

        if ($this->filterEvent) {
            $query->where('event', $this->filterEvent);
        }

        return $query->paginate(50);
    }

    #[Computed]
    public function subjectTypes(): array
    {
        return Activity::query()
            ->select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->map(fn ($type) => class_basename($type))
            ->sort()
            ->values()
            ->toArray();
    }

    #[Computed]
    public function events(): array
    {
        return Activity::query()
            ->select('event')
            ->distinct()
            ->whereNotNull('event')
            ->pluck('event')
            ->sort()
            ->values()
            ->toArray();
    }

    public function clearFilters(): void
    {
        $this->searchTerm = '';
        $this->filterSubjectType = '';
        $this->filterEvent = '';
        $this->resetPage();
    }

    private function checkHadesAccess(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    public function render(): View
    {
        return view('developer::admin.activity-log');
    }
}
