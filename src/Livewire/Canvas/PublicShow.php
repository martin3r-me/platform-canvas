<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Component;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\CommentService;

class PublicShow extends Component
{
    public Canvas $canvas;

    public string $commentContent = '';
    public ?int $commentBlockId = null;
    public ?int $replyToId = null;
    public ?int $filterBlockId = null;

    public function mount(string $token): void
    {
        $this->canvas = Canvas::where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();
        $this->canvas->loadMissing('canvasType');
    }

    public function addComment(): void
    {
        $this->validate([
            'commentContent' => 'required|string|min:1|max:5000',
            'commentBlockId' => 'nullable|integer|exists:canvas_building_blocks,id',
            'replyToId' => 'nullable|integer|exists:canvas_comments,id',
        ]);

        try {
            (new CommentService())->addComment($this->canvas, [
                'content' => $this->commentContent,
                'building_block_id' => $this->replyToId ? null : $this->commentBlockId,
                'parent_id' => $this->replyToId,
            ]);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        $this->reset('commentContent', 'commentBlockId', 'replyToId');
    }

    public function setReplyTo(int $commentId): void
    {
        $this->replyToId = $commentId;
        $this->commentBlockId = null;
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
    }

    public function deleteComment(int $commentId): void
    {
        (new CommentService())->deleteComment($this->canvas, $commentId);
    }

    public function filterByBlock(?int $blockId): void
    {
        $this->filterBlockId = $blockId;
    }

    public function render()
    {
        $this->canvas->load(['canvasType', 'buildingBlocks.entries']);

        $canvasData = $this->canvas->toCanvasArray();
        $layout = $this->canvas->canvasType?->layout ?? [];
        $blockDefs = $this->canvas->canvasType?->block_definitions ?? [];

        $commentService = new CommentService();
        $comments = $commentService->getCommentsForCanvas($this->canvas, $this->filterBlockId);
        $allComments = $this->canvas->comments()->get();

        return view('canvas::livewire.canvas.public-show', [
            'canvasData' => $canvasData,
            'layout' => $layout,
            'blockDefs' => $blockDefs,
            'comments' => $comments,
            'allComments' => $allComments,
        ])->layout('platform::layouts.guest');
    }
}
