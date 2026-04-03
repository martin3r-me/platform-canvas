<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Component;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasType;

class Index extends Component
{
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    public string $view = 'active';

    public function updateStatus(int $canvasId, string $status): void
    {
        if (! in_array($status, Canvas::STATUSES)) {
            return;
        }

        $user = Auth::user();
        $teamId = $user->currentTeam?->id;

        $canvas = Canvas::forTeam($teamId)->findOrFail($canvasId);

        if (! $canvas->canTransitionTo($status)) {
            return;
        }

        $canvas->transitionTo($status);
    }

    public function setView(string $view): void
    {
        if (in_array($view, ['active', 'done'])) {
            $this->view = $view;
        }
    }

    public function setTypeFilter(string $typeKey): void
    {
        $this->typeFilter = $this->typeFilter === $typeKey ? '' : $typeKey;
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => null,
            'modelId' => null,
            'subject' => 'Canvas',
            'description' => 'Canvas-Uebersicht',
            'url' => route('canvas.canvases.index'),
            'source' => 'canvas.canvases.index',
            'recipients' => [],
            'meta' => ['view_type' => 'index'],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $teamId = $team?->id;

        $canvasTypes = CanvasType::availableForTeam($teamId)->get();

        $query = Canvas::forTeam($teamId)
            ->with(['canvasType', 'createdByUser', 'tags', 'contextColors'])
            ->withCount('buildingBlocks');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->typeFilter) {
            $query->ofType($this->typeFilter);
        }

        $allCanvases = $query->orderBy('updated_at', 'desc')->get();

        // Counts fuer Tab-Badges
        $activeCount = $allCanvases->whereIn('status', Canvas::ACTIVE_STATUSES)->count();
        $doneCount = $allCanvases->whereIn('status', Canvas::DONE_STATUSES)->count();

        // Typ-Counts fuer Chips (ueber alle, unabhaengig von typeFilter)
        $typeCounts = Canvas::forTeam($teamId)
            ->with('canvasType')
            ->get()
            ->groupBy(fn ($c) => $c->canvasType?->key ?? '_none')
            ->map->count();
        $totalCount = $typeCounts->sum();

        // Nur die Statuses der aktiven Ansicht
        $visibleStatuses = $this->view === 'active' ? Canvas::ACTIVE_STATUSES : Canvas::DONE_STATUSES;

        // Nach Status gruppieren (Funnel-Reihenfolge)
        $grouped = collect();
        foreach ($visibleStatuses as $status) {
            $grouped[$status] = $allCanvases->where('status', $status)->values();
        }

        // Stats nur fuer sichtbare Statuses
        $stats = [];
        foreach ($visibleStatuses as $status) {
            $stats[$status] = $grouped[$status]->count();
        }
        $stats['total'] = collect($stats)->sum();

        return view('canvas::livewire.canvas.index', [
            'grouped' => $grouped,
            'canvasTypes' => $canvasTypes,
            'stats' => $stats,
            'visibleStatuses' => $visibleStatuses,
            'activeCount' => $activeCount,
            'doneCount' => $doneCount,
            'typeCounts' => $typeCounts,
            'totalCount' => $totalCount,
        ])->layout('platform::layouts.app');
    }
}
