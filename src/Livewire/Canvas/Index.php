<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasType;

class Index extends Component
{
    public string $search = '';
    public string $typeFilter = '';

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
            ->with('canvasType', 'createdByUser')
            ->withCount('buildingBlocks');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->typeFilter) {
            $query->ofType($this->typeFilter);
        }

        $allCanvases = $query->orderBy('updated_at', 'desc')->get();

        // Nach Status gruppieren (Funnel-Reihenfolge)
        $grouped = collect();
        foreach (Canvas::STATUSES as $status) {
            $grouped[$status] = $allCanvases->where('status', $status)->values();
        }

        // Stats
        $stats = [];
        foreach (Canvas::STATUSES as $status) {
            $stats[$status] = $grouped[$status]->count();
        }
        $stats['total'] = $allCanvases->count();

        return view('canvas::livewire.canvas.index', [
            'grouped' => $grouped,
            'canvasTypes' => $canvasTypes,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
