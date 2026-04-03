<?php

namespace Platform\Canvas\Tools\Snapshot;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\CanvasService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class CreateSnapshotTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.snapshots.POST'; }

    public function getDescription(): string
    {
        return 'POST /canvas/snapshots - Erstellt einen Snapshot des aktuellen Canvas-Zustands. ERFORDERLICH: canvas_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'canvas_id' => ['type' => 'integer', 'description' => 'ID des Canvas (ERFORDERLICH).'],
            ],
            'required' => ['canvas_id'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if ($error = $this->requireUser($context)) return $error;

        $canvasId = (int)($arguments['canvas_id'] ?? 0);
        if ($canvasId <= 0) return ToolResult::error('VALIDATION_ERROR', 'canvas_id ist erforderlich.');

        $canvas = Canvas::query()->where('team_id', $teamId)->find($canvasId);
        if (!$canvas) return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden.');

        $snapshot = (new CanvasService())->createSnapshot($canvas, $context->user->id);

        return ToolResult::success([
            'id' => $snapshot->id, 'uuid' => $snapshot->uuid, 'canvas_id' => $snapshot->canvas_id,
            'version' => $snapshot->version, 'created_at' => $snapshot->created_at?->toISOString(),
            'message' => 'Snapshot Version ' . $snapshot->version . ' erstellt.',
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Erstellen des Snapshots: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'snapshots', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
