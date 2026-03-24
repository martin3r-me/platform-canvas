<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Component;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasComment;

class PublicShow extends Component
{
    public Canvas $canvas;

    public string $commentContent = '';
    public ?int $commentBlockId = null;

    public function mount(string $token): void
    {
        $this->canvas = Canvas::where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();
    }

    public function addComment(): void
    {
        $this->validate([
            'commentContent' => 'required|string|min:1|max:5000',
            'commentBlockId' => 'nullable|integer|exists:canvas_building_blocks,id',
        ]);

        // Ensure block belongs to this canvas
        if ($this->commentBlockId) {
            $blockBelongsToCanvas = $this->canvas->buildingBlocks()
                ->where('id', $this->commentBlockId)
                ->exists();

            if (! $blockBelongsToCanvas) {
                return;
            }
        }

        $this->canvas->comments()->create([
            'content' => $this->commentContent,
            'building_block_id' => $this->commentBlockId,
        ]);

        $this->reset('commentContent', 'commentBlockId');
    }

    public function render()
    {
        $this->canvas->load(['canvasType', 'buildingBlocks.entries', 'comments.buildingBlock']);

        $canvasData = $this->canvas->toCanvasArray();
        $layout = $this->canvas->canvasType?->layout ?? [];
        $blockDefs = $this->canvas->canvasType?->block_definitions ?? [];
        $comments = $this->canvas->comments()->orderBy('created_at', 'desc')->get();

        return view('canvas::livewire.canvas.public-show', [
            'canvasData' => $canvasData,
            'layout' => $layout,
            'blockDefs' => $blockDefs,
            'comments' => $comments,
        ])->layout('platform::layouts.guest');
    }
}
