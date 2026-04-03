<?php

namespace Platform\Canvas\Tools\Snapshot;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasSnapshot;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;

class ListSnapshotsTool extends AbstractCanvasTool
{
    use HasStandardGetOperations;

    public function getName(): string { return 'canvas.snapshots.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/snapshots - Listet Snapshots eines Canvas. Parameter: canvas_id (required), limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas($this->getStandardGetSchema(), [
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'canvas_id' => ['type' => 'integer', 'description' => 'ID des Canvas (ERFORDERLICH).'],
            ],
            'required' => ['canvas_id'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $canvasId = (int)($arguments['canvas_id'] ?? 0);
        if ($canvasId <= 0) return ToolResult::error('VALIDATION_ERROR', 'canvas_id ist erforderlich.');

        $canvas = Canvas::query()->where('team_id', $teamId)->find($canvasId);
        if (!$canvas) return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden.');

        $query = CanvasSnapshot::query()->where('canvas_id', $canvasId)->orderBy('version', 'desc');
        $result = $this->applyStandardPaginationResult($query, $arguments);

        $data = collect($result['data'])->map(fn (CanvasSnapshot $s) => [
            'id' => $s->id, 'uuid' => $s->uuid, 'version' => $s->version,
            'created_by_user_id' => $s->created_by_user_id, 'created_at' => $s->created_at?->toISOString(),
        ])->values()->toArray();

        return ToolResult::success(['data' => $data, 'pagination' => $result['pagination'] ?? null, 'canvas_id' => $canvasId, 'team_id' => $teamId]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Laden der Snapshots: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'snapshots', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
