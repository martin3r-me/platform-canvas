<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\AnalysisService;

class Show extends Component
{
    public Canvas $canvas;

    public string $commentContent = '';
    public ?int $commentBlockId = null;
    public ?int $replyToId = null;
    public ?int $filterBlockId = null;

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

    public function addComment(): void
    {
        $this->validate([
            'commentContent' => 'required|string|min:1|max:5000',
            'commentBlockId' => 'nullable|integer|exists:canvas_building_blocks,id',
            'replyToId' => 'nullable|integer|exists:canvas_comments,id',
        ]);

        if ($this->commentBlockId) {
            $blockBelongsToCanvas = $this->canvas->buildingBlocks()
                ->where('id', $this->commentBlockId)
                ->exists();

            if (! $blockBelongsToCanvas) {
                return;
            }
        }

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

        $comment->replies()->delete();
        $comment->delete();
    }

    public function filterByBlock(?int $blockId): void
    {
        $this->filterBlockId = $blockId;
    }

    public function render()
    {
        $this->canvas->load(['canvasType', 'buildingBlocks.entries', 'createdByUser', 'snapshots']);

        $canvasData = $this->canvas->toCanvasArray();
        $analysisData = (new AnalysisService())->analyze($this->canvas);
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
        $allComments = $this->canvas->comments()->get();

        return view('canvas::livewire.canvas.show', [
            'canvasData' => $canvasData,
            'analysisData' => $analysisData,
            'layout' => $layout,
            'blockDefs' => $blockDefs,
            'comments' => $comments,
            'allComments' => $allComments,
        ])->layout('platform::layouts.app');
    }
}
