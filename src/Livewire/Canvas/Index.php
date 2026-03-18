<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasType;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
    }

    public function setTypeFilter(string $typeKey): void
    {
        $this->typeFilter = $this->typeFilter === $typeKey ? '' : $typeKey;
        $this->resetPage();
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

        // Available canvas types for filter
        $canvasTypes = CanvasType::availableForTeam($teamId)->get();

        $query = Canvas::forTeam($teamId)
            ->with('canvasType')
            ->withCount('buildingBlocks')
            ->with('createdByUser');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->ofType($this->typeFilter);
        }

        $canvases = $query->orderBy('updated_at', 'desc')->paginate(15);

        $statsQuery = Canvas::forTeam($teamId);
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'draft' => (clone $statsQuery)->byStatus('draft')->count(),
            'active' => (clone $statsQuery)->byStatus('active')->count(),
            'archived' => (clone $statsQuery)->byStatus('archived')->count(),
        ];

        return view('canvas::livewire.canvas.index', [
            'canvases' => $canvases,
            'canvasTypes' => $canvasTypes,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
