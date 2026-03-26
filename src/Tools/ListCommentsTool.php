<?php

namespace Platform\Canvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasComment;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;

class ListCommentsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.comments.GET';
    }

    public function getDescription(): string
    {
        return 'GET /canvas/comments - Listet Kommentare eines Canvas (threaded, mit Block-Zuordnung). Parameter: canvas_id (required), team_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'canvas_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Canvas (ERFORDERLICH).',
                ],
            ],
            'required' => ['canvas_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $canvasId = (int)($arguments['canvas_id'] ?? 0);
            if ($canvasId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'canvas_id ist erforderlich.');
            }

            $canvas = Canvas::query()
                ->where('team_id', $teamId)
                ->find($canvasId);

            if (!$canvas) {
                return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden (oder kein Zugriff).');
            }

            $comments = CanvasComment::query()
                ->where('canvas_id', $canvasId)
                ->rootComments()
                ->with(['replies', 'buildingBlock'])
                ->orderBy('created_at')
                ->get();

            $totalComments = CanvasComment::query()
                ->where('canvas_id', $canvasId)
                ->count();

            $data = $comments->map(function (CanvasComment $comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'block_key' => $comment->buildingBlock?->block_key,
                    'block_label' => $comment->buildingBlock?->label,
                    'created_at' => $comment->created_at?->toISOString(),
                    'replies' => $comment->replies->map(function (CanvasComment $reply) {
                        return [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'created_at' => $reply->created_at?->toISOString(),
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'canvas_id' => $canvasId,
                'canvas_name' => $canvas->name,
                'total_comments' => $totalComments,
                'comments' => $data,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Kommentare: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['canvas', 'comments', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
