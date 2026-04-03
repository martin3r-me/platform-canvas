<?php

namespace Platform\Canvas\Tools\Snapshot;

use Platform\Canvas\Models\CanvasSnapshot;
use Platform\Canvas\Services\CanvasService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;

class CompareSnapshotsTool extends AbstractCanvasTool
{
    public function getName(): string { return 'canvas.snapshots.compare.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/snapshots/compare - Vergleicht zwei Snapshots. ERFORDERLICH: snapshot_a_id, snapshot_b_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'snapshot_a_id' => ['type' => 'integer', 'description' => 'ID des ersten Snapshots (ERFORDERLICH).'],
                'snapshot_b_id' => ['type' => 'integer', 'description' => 'ID des zweiten Snapshots (ERFORDERLICH).'],
            ],
            'required' => ['snapshot_a_id', 'snapshot_b_id'],
        ];
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $aId = (int)($arguments['snapshot_a_id'] ?? 0);
        $bId = (int)($arguments['snapshot_b_id'] ?? 0);
        if ($aId <= 0 || $bId <= 0) return ToolResult::error('VALIDATION_ERROR', 'Beide Snapshot-IDs sind erforderlich.');

        $snapshotA = CanvasSnapshot::query()->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))->find($aId);
        if (!$snapshotA) return ToolResult::error('NOT_FOUND', 'Snapshot A nicht gefunden.');

        $snapshotB = CanvasSnapshot::query()->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))->find($bId);
        if (!$snapshotB) return ToolResult::error('NOT_FOUND', 'Snapshot B nicht gefunden.');

        if ($snapshotA->canvas_id !== $snapshotB->canvas_id) {
            return ToolResult::error('VALIDATION_ERROR', 'Snapshots gehoeren zu unterschiedlichen Canvases.');
        }

        return ToolResult::success((new CanvasService())->compareSnapshots($snapshotA, $snapshotB));
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Vergleichen der Snapshots: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'snapshots', 'compare'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
