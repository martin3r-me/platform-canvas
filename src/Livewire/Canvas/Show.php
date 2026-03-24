<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\AnalysisService;

class Show extends Component
{
    public Canvas $canvas;

    public function mount(Canvas $canvas): void
    {
        abort_unless($canvas->team_id === Auth::user()->currentTeam->id, 403);
        $this->canvas = $canvas;
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => 'Canvas',
            'modelId' => $this->canvas->id,
            'subject' => $this->canvas->name,
            'description' => $this->canvas->canvasType?->name ?? 'Canvas',
            'url' => route('canvas.canvases.show', $this->canvas),
            'source' => 'canvas.canvases.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show'],
        ]);
    }

    public function createPublicLink(): void
    {
        $this->canvas->generatePublicToken();
    }

    public function togglePublicLink(): void
    {
        $this->canvas->update(['is_public' => ! $this->canvas->is_public]);
    }

    public function render()
    {
        $this->canvas->load(['canvasType', 'buildingBlocks.entries', 'createdByUser', 'snapshots']);

        $canvasData = $this->canvas->toCanvasArray();
        $analysisData = (new AnalysisService())->analyze($this->canvas);
        $layout = $this->canvas->canvasType?->layout ?? [];
        $blockDefs = $this->canvas->canvasType?->block_definitions ?? [];

        return view('canvas::livewire.canvas.show', [
            'canvasData' => $canvasData,
            'analysisData' => $analysisData,
            'layout' => $layout,
            'blockDefs' => $blockDefs,
        ])->layout('platform::layouts.app');
    }
}
