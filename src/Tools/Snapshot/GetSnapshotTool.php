<?php

namespace Platform\Canvas\Tools\Snapshot;

use Platform\Canvas\Models\CanvasSnapshot;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;

class GetSnapshotTool extends AbstractCanvasTool
{
    public function getName(): string { return 'canvas.snapshot.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/snapshots/{id} - Ruft einen einzelnen Snapshot mit vollstaendigen Daten ab. Parameter: snapshot_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'snapshot_id' => ['type' => 'integer', 'description' => 'ID des Snapshots (ERFORDERLICH).'],
            ],
            'required' => ['snapshot_id'],
        ];
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $snapshotId = (int)($arguments['snapshot_id'] ?? 0);
        if ($snapshotId <= 0) return ToolResult::error('VALIDATION_ERROR', 'snapshot_id ist erforderlich.');

        $snapshot = CanvasSnapshot::query()->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))->find($snapshotId);
        if (!$snapshot) return ToolResult::error('NOT_FOUND', 'Snapshot nicht gefunden.');

        return ToolResult::success([
            'id' => $snapshot->id, 'uuid' => $snapshot->uuid, 'canvas_id' => $snapshot->canvas_id,
            'version' => $snapshot->version, 'snapshot_data' => $snapshot->snapshot_data,
            'created_by_user_id' => $snapshot->created_by_user_id, 'created_at' => $snapshot->created_at?->toISOString(),
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Laden des Snapshots: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'snapshot', 'get'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
