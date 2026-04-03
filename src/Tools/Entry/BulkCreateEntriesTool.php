<?php

namespace Platform\Canvas\Tools\Entry;

use Platform\Canvas\Models\BuildingBlock;
use Platform\Canvas\Services\EntryService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class BulkCreateEntriesTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.entries.bulk.POST'; }

    public function getDescription(): string
    {
        return 'POST /canvas/entries/bulk - Bulk-Erstellung von Entries. ERFORDERLICH: building_block_id, entries (Array mit {title, content?, entry_type?, metadata?}).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'building_block_id' => ['type' => 'integer', 'description' => 'ID des Building Blocks (ERFORDERLICH).'],
                'entries' => [
                    'type' => 'array', 'description' => 'Array von Entry-Objekten (ERFORDERLICH).',
                    'items' => ['type' => 'object', 'properties' => [
                        'title' => ['type' => 'string'], 'content' => ['type' => 'string'],
                        'entry_type' => ['type' => 'string'], 'metadata' => ['type' => 'object'],
                    ], 'required' => ['title']],
                ],
            ],
            'required' => ['building_block_id', 'entries'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if ($error = $this->requireUser($context)) return $error;

        $blockId = (int)($arguments['building_block_id'] ?? 0);
        if ($blockId <= 0) return ToolResult::error('VALIDATION_ERROR', 'building_block_id ist erforderlich.');

        $block = BuildingBlock::query()->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))->find($blockId);
        if (!$block) return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden.');

        $entriesData = $arguments['entries'] ?? [];
        if (!is_array($entriesData) || empty($entriesData)) {
            return ToolResult::error('VALIDATION_ERROR', 'entries Array ist erforderlich.');
        }

        $created = (new EntryService())->bulkCreateEntries($block, $entriesData, $context->user->id);

        return ToolResult::success([
            'building_block_id' => $blockId, 'created_count' => count($created),
            'entries' => array_map(fn($e) => ['id' => $e->id, 'uuid' => $e->uuid, 'title' => $e->title, 'entry_type' => $e->entry_type, 'position' => $e->position], $created),
            'message' => count($created) . ' Entries erfolgreich erstellt.',
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Bulk-Erstellen der Entries: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'entries', 'bulk', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
