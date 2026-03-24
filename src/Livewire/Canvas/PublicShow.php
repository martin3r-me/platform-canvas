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

        // Ensure block belongs to this canvas
        if ($this->commentBlockId) {
            $blockBelongsToCanvas = $this->canvas->buildingBlocks()
                ->where('id', $this->commentBlockId)
                ->exists();

            if (! $blockBelongsToCanvas) {
                return;
            }
        }

        // Ensure reply target belongs to this canvas and is a root comment
        if ($this->replyToId) {
            $parentComment = $this->canvas->comments()
                ->whereNull('parent_id')
                ->where('id', $this->replyToId)
                ->first();

            if (! $parentComment) {
                return;
            }
        }

        $this->canvas->comments()->create([
            'content' => $this->commentContent,
            'building_block_id' => $this->replyToId
                ? $this->canvas->comments()->find($this->replyToId)?->building_block_id
                : $this->commentBlockId,
            'parent_id' => $this->replyToId,
        ]);

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
        $comment = $this->canvas->comments()->where('id', $commentId)->first();

        if (! $comment) {
            return;
        }

        // Delete replies first, then the comment itself
        $comment->replies()->delete();
        $comment->delete();
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

        $commentsQuery = $this->canvas->comments()
            ->rootComments()
            ->with(['replies.buildingBlock', 'buildingBlock'])
            ->orderBy('created_at', 'desc');

        if ($this->filterBlockId) {
            $commentsQuery->where('building_block_id', $this->filterBlockId);
        }

        $comments = $commentsQuery->get();

        // Count all comments (root + replies) for badge counts
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
