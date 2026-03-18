<?php

namespace Platform\Canvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;

class ListCanvasesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.canvases.GET';
    }

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
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                    ],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['draft', 'active', 'archived'],
                        'description' => 'Optional: Filter nach Status.',
                    ],
                    'type_key' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Canvas-Typ Key (z.B. bmc, swot, project-canvas, lean-canvas).',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

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

            $this->applyStandardFilters($query, $arguments, [
                'name', 'status', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'status', 'created_at', 'updated_at',
            ], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (Canvas $canvas) {
                return [
                    'id' => $canvas->id,
                    'uuid' => $canvas->uuid,
                    'name' => $canvas->name,
                    'description' => $canvas->description,
                    'status' => $canvas->status,
                    'canvas_type_key' => $canvas->canvasType?->key,
                    'canvas_type_name' => $canvas->canvasType?->name,
                    'building_blocks_count' => $canvas->building_blocks_count,
                    'snapshots_count' => $canvas->snapshots_count,
                    'team_id' => $canvas->team_id,
                    'created_at' => $canvas->created_at?->toISOString(),
                    'updated_at' => $canvas->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Canvases: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['canvas', 'canvases', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
