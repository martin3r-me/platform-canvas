<?php

namespace Platform\Canvas\Tools\Entry;

use Platform\Canvas\Models\BuildingBlock;
use Platform\Canvas\Services\EntryService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class CreateEntryTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.entries.POST'; }

    public function getDescription(): string
    {
        return 'POST /canvas/entries - Erstellt einen neuen Entry in einem Building Block. ERFORDERLICH: building_block_id, title.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'building_block_id' => ['type' => 'integer', 'description' => 'ID des Building Blocks (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Titel des Entries (ERFORDERLICH).'],
                'content' => ['type' => 'string', 'description' => 'Optional: Inhalt.'],
                'entry_type' => ['type' => 'string', 'enum' => ['text', 'date', 'person', 'amount'], 'description' => 'Optional: Entry-Typ. Default: text.'],
                'position' => ['type' => 'integer', 'description' => 'Optional: Position.'],
                'metadata' => ['type' => 'object', 'description' => 'Optional: Metadaten.'],
            ],
            'required' => ['building_block_id', 'title'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if ($error = $this->requireUser($context)) return $error;

        $blockId = (int)($arguments['building_block_id'] ?? 0);
        if ($blockId <= 0) return ToolResult::error('VALIDATION_ERROR', 'building_block_id ist erforderlich.');

        $block = BuildingBlock::with('canvas.canvasType')->find($blockId);
        if (!$block || $block->canvas?->team_id !== $teamId) return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden.');

        $title = trim((string)($arguments['title'] ?? ''));
        if ($title === '') return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');

        $entryType = $arguments['entry_type'] ?? 'text';
        $allowedTypes = $block->canvas->canvasType->entry_types ?? ['text'];
        if (!in_array($entryType, $allowedTypes)) {
            return ToolResult::error('VALIDATION_ERROR', "entry_type '{$entryType}' nicht erlaubt. Erlaubt: " . implode(', ', $allowedTypes));
        }

        $entry = (new EntryService())->createEntry($block, [
            'title' => $title, 'content' => $arguments['content'] ?? null, 'entry_type' => $entryType,
            'position' => $arguments['position'] ?? null, 'metadata' => $arguments['metadata'] ?? null,
            'created_by_user_id' => $context->user->id,
        ]);

        return ToolResult::success([
            'id' => $entry->id, 'uuid' => $entry->uuid, 'title' => $entry->title, 'entry_type' => $entry->entry_type,
            'position' => $entry->position, 'building_block_id' => $block->id, 'canvas_id' => $block->canvas_id,
            'message' => 'Entry erstellt.',
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Erstellen des Entries: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'entries', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
