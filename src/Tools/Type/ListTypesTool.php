<?php

namespace Platform\Canvas\Tools\Type;

use Platform\Canvas\Models\CanvasType;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;

class ListTypesTool extends AbstractCanvasTool
{
    use HasStandardGetOperations;

    public function getName(): string { return 'canvas.types.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/types - Listet verfuegbare Canvas-Typen (System + Custom). Parameter: team_id (optional), filters/search/sort/limit/offset.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas($this->getStandardGetSchema(), [
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            ],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $query = CanvasType::query()->withCount('canvases')->availableForTeam($teamId);

        $this->applyStandardFilters($query, $arguments, ['key', 'name', 'origin', 'is_active', 'created_at', 'updated_at']);
        $this->applyStandardSearch($query, $arguments, ['name', 'description', 'key']);
        $this->applyStandardSort($query, $arguments, ['name', 'key', 'origin', 'created_at', 'updated_at'], 'origin', 'desc');

        $result = $this->applyStandardPaginationResult($query, $arguments);

        $data = collect($result['data'])->map(fn (CanvasType $type) => [
            'id' => $type->id, 'uuid' => $type->uuid, 'key' => $type->key, 'name' => $type->name,
            'description' => $type->description, 'origin' => $type->origin,
            'block_count' => is_array($type->block_definitions) ? count($type->block_definitions) : 0,
            'is_active' => $type->is_active,
            'created_at' => $type->created_at?->toISOString(), 'updated_at' => $type->updated_at?->toISOString(),
        ])->values()->toArray();

        return ToolResult::success(['data' => $data, 'pagination' => $result['pagination'] ?? null, 'team_id' => $teamId]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Laden der Canvas-Typen: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'types', 'list'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
