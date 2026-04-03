<?php

namespace Platform\Canvas\Tools\Utility;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasComment;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;

class ListCommentsTool extends AbstractCanvasTool
{
    public function getName(): string { return 'canvas.comments.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/comments - Listet Kommentare eines Canvas (threaded). Parameter: canvas_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'canvas_id' => ['type' => 'integer', 'description' => 'ID des Canvas (ERFORDERLICH).'],
            ],
            'required' => ['canvas_id'],
        ];
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $canvasId = (int)($arguments['canvas_id'] ?? 0);
        if ($canvasId <= 0) return ToolResult::error('VALIDATION_ERROR', 'canvas_id ist erforderlich.');

        $canvas = Canvas::query()->where('team_id', $teamId)->find($canvasId);
        if (!$canvas) return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden.');

        $comments = CanvasComment::query()
            ->where('canvas_id', $canvasId)->rootComments()
            ->with(['replies', 'buildingBlock'])->orderBy('created_at')->get();

        $totalComments = CanvasComment::query()->where('canvas_id', $canvasId)->count();

        $data = $comments->map(fn (CanvasComment $c) => [
            'id' => $c->id, 'content' => $c->content,
            'block_key' => $c->buildingBlock?->block_key, 'block_label' => $c->buildingBlock?->label,
            'created_at' => $c->created_at?->toISOString(),
            'replies' => $c->replies->map(fn (CanvasComment $r) => [
                'id' => $r->id, 'content' => $r->content, 'created_at' => $r->created_at?->toISOString(),
            ])->values()->toArray(),
        ])->values()->toArray();

        return ToolResult::success(['canvas_id' => $canvasId, 'canvas_name' => $canvas->name, 'total_comments' => $totalComments, 'comments' => $data]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Laden der Kommentare: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'comments', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
