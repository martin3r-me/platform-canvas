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
        $canvas->update(['status' => $status]);
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
            ->visibleTo($user)
            ->with(['canvasType', 'createdByUser', 'tags', 'contextColors'])
            ->withCount('buildingBlocks')
            ->withCount([
                'workshopNotes as ws_notes_count' => fn ($q) => $q->where('type', 'note'),
                'workshopNotes as ws_text_count' => fn ($q) => $q->where('type', 'text'),
                'workshopNotes as ws_image_count' => fn ($q) => $q->whereIn('type', ['image', 'image_grid']),
                'workshopNotes as ws_video_count' => fn ($q) => $q->where('type', 'video'),
                'workshopNotes as ws_kanban_count' => fn ($q) => $q->where('type', 'kanban'),
                'workshopNotes as ws_section_count' => fn ($q) => $q->where('type', 'section'),
                'workshopNotes as ws_shape_count' => fn ($q) => $q->where('type', 'shape'),
                'workshopNotes as ws_connector_count' => fn ($q) => $q->where('type', 'connector'),
            ]);

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
            ->visibleTo($user)
            ->with('canvasType')
            ->get()
            ->groupBy(fn ($c) => $c->canvasType?->key ?? '_none')
            ->map->count();
        $totalCount = $typeCounts->sum();

        if ($this->view === 'active') {
            // Aktiv-Tab: flat list sorted by updated_at (already sorted)
            $activeCanvases = $allCanvases->whereIn('status', Canvas::ACTIVE_STATUSES)->values();
            $grouped = collect();

            $stats = [
                'total' => $activeCanvases->count(),
            ];
        } else {
            // Erledigt-Tab: grouped by completed/discarded
            $grouped = collect();
            foreach (Canvas::DONE_STATUSES as $status) {
                $grouped[$status] = $allCanvases->where('status', $status)->values();
            }
            $activeCanvases = collect();

            $stats = [];
            foreach (Canvas::DONE_STATUSES as $status) {
                $stats[$status] = $grouped[$status]->count();
            }
            $stats['total'] = collect($stats)->sum();
        }

        return view('canvas::livewire.canvas.index', [
            'grouped' => $grouped,
            'activeCanvases' => $activeCanvases ?? collect(),
            'canvasTypes' => $canvasTypes,
            'stats' => $stats,
            'activeCount' => $activeCount,
            'doneCount' => $doneCount,
            'typeCounts' => $typeCounts,
            'totalCount' => $totalCount,
        ])->layout('platform::layouts.app');
    }
}
