<?php

namespace Platform\Canvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Canvas\Models\BuildingBlock;
use Platform\Canvas\Services\EntryService;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;

class ReorderEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.entries.reorder.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /canvas/entries/reorder - Sortiert Entries innerhalb eines Building Blocks neu. ERFORDERLICH: building_block_id, entry_ids (geordnetes Array von Entry-IDs).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'building_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Building Blocks (ERFORDERLICH).',
                ],
                'entry_ids' => [
                    'type' => 'array',
                    'description' => 'Geordnetes Array von Entry-IDs (ERFORDERLICH). Erste ID bekommt Position 1, etc.',
                    'items' => ['type' => 'integer'],
                ],
            ],
            'required' => ['building_block_id', 'entry_ids'],
        ]);
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

            $entryIds = $arguments['entry_ids'] ?? [];
            if (!is_array($entryIds) || empty($entryIds)) {
                return ToolResult::error('VALIDATION_ERROR', 'entry_ids Array ist erforderlich und darf nicht leer sein.');
            }

            $entryService = new EntryService();
            $entryService->reorderEntries($block, $entryIds);

            return ToolResult::success([
                'building_block_id' => $blockId,
                'reordered_count' => count($entryIds),
                'message' => count($entryIds) . ' Entries neu sortiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Neusortieren der Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['canvas', 'entries', 'reorder'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
