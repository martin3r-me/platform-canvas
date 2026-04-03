<?php

namespace Platform\Canvas\Tools\Canvas;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;

class ListCanvasesTool extends AbstractCanvasTool
{
    use HasStandardGetOperations;

    public function getName(): string { return 'canvas.canvases.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/canvases - Listet Canvases. Parameter: team_id (optional), type_key (optional), status (optional), filters/search/sort/limit/offset.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.'],
                    'status' => ['type' => 'string', 'enum' => Canvas::STATUSES, 'description' => 'Optional: Filter nach Status.'],
                    'type_key' => ['type' => 'string', 'description' => 'Optional: Filter nach Canvas-Typ Key.'],
                ],
            ]
        );
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $query = Canvas::query()
            ->with('canvasType')
            ->withCount('buildingBlocks', 'snapshots')
            ->forTeam($teamId);

        if (isset($arguments['status'])) {
            $query->byStatus($arguments['status']);
        }
        if (isset($arguments['type_key'])) {
            $query->ofType($arguments['type_key']);
        }

        $this->applyStandardFilters($query, $arguments, ['name', 'status', 'created_at', 'updated_at']);
        $this->applyStandardSearch($query, $arguments, ['name', 'description']);
        $this->applyStandardSort($query, $arguments, ['name', 'status', 'created_at', 'updated_at'], 'created_at', 'desc');

        $result = $this->applyStandardPaginationResult($query, $arguments);

        $data = collect($result['data'])->map(fn (Canvas $canvas) => [
            'id' => $canvas->id, 'uuid' => $canvas->uuid, 'name' => $canvas->name,
            'description' => $canvas->description, 'status' => $canvas->status,
            'canvas_type_key' => $canvas->canvasType?->key, 'canvas_type_name' => $canvas->canvasType?->name,
            'building_blocks_count' => $canvas->building_blocks_count, 'snapshots_count' => $canvas->snapshots_count,
            'team_id' => $canvas->team_id,
            'created_at' => $canvas->created_at?->toISOString(), 'updated_at' => $canvas->updated_at?->toISOString(),
        ])->values()->toArray();

        return ToolResult::success(['data' => $data, 'pagination' => $result['pagination'] ?? null, 'team_id' => $teamId]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Laden der Canvases: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'canvases', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
