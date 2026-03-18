<?php

namespace Platform\Canvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Canvas\Models\BuildingBlock;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\Entry;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;

class ListEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.entries.GET';
    }

    public function getDescription(): string
    {
        return 'GET /canvas/entries - Listet Entries fuer einen Building Block. ERFORDERLICH: building_block_id.';
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
                    'building_block_id' => [
                        'type' => 'integer',
                        'description' => 'ID des Building Blocks (ERFORDERLICH).',
                    ],
                ],
                'required' => ['building_block_id'],
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

            $blockId = (int)($arguments['building_block_id'] ?? 0);
            if ($blockId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'building_block_id ist erforderlich.');
            }

            $block = BuildingBlock::query()
                ->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))
                ->find($blockId);

            if (!$block) {
                return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden (oder kein Zugriff).');
            }

            $query = Entry::query()
                ->where('building_block_id', $blockId)
                ->with('buildingBlock:id,block_key,label');

            $this->applyStandardSort($query, $arguments, [
                'position',
                'created_at',
                'updated_at',
            ], 'position', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (Entry $entry) {
                return [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,
                    'title' => $entry->title,
                    'content' => $entry->content,
                    'entry_type' => $entry->entry_type,
                    'position' => $entry->position,
                    'metadata' => $entry->metadata,
                    'created_by' => $entry->created_by_user_id,
                    'block_key' => $entry->buildingBlock?->block_key,
                    'block_label' => $entry->buildingBlock?->label,
                    'building_block_id' => $entry->building_block_id,
                    'created_at' => $entry->created_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['canvas', 'entries', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
