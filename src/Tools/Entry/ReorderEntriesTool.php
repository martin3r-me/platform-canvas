<?php

namespace Platform\Canvas\Tools\Entry;

use Platform\Canvas\Models\BuildingBlock;
use Platform\Canvas\Services\EntryService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class ReorderEntriesTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.entries.reorder.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /canvas/entries/reorder - Sortiert Entries innerhalb eines Building Blocks neu. ERFORDERLICH: building_block_id, entry_ids.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'building_block_id' => ['type' => 'integer', 'description' => 'ID des Building Blocks (ERFORDERLICH).'],
                'entry_ids' => ['type' => 'array', 'description' => 'Geordnetes Array von Entry-IDs (ERFORDERLICH).', 'items' => ['type' => 'integer']],
            ],
            'required' => ['building_block_id', 'entry_ids'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $blockId = (int)($arguments['building_block_id'] ?? 0);
        if ($blockId <= 0) return ToolResult::error('VALIDATION_ERROR', 'building_block_id ist erforderlich.');

        $block = BuildingBlock::query()->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))->find($blockId);
        if (!$block) return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden.');

        $entryIds = $arguments['entry_ids'] ?? [];
        if (!is_array($entryIds) || empty($entryIds)) {
            return ToolResult::error('VALIDATION_ERROR', 'entry_ids Array ist erforderlich.');
        }

        (new EntryService())->reorderEntries($block, $entryIds);

        return ToolResult::success([
            'building_block_id' => $blockId, 'reordered_count' => count($entryIds),
            'message' => count($entryIds) . ' Entries neu sortiert.',
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Neusortieren der Entries: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'entries', 'reorder'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
