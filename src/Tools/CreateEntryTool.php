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

class CreateEntryTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesCanvasTeam;

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
                'content' => ['type' => 'string', 'description' => 'Optional: Inhalt/Beschreibung.'],
                'entry_type' => ['type' => 'string', 'enum' => ['text', 'date', 'person', 'amount'], 'description' => 'Optional: Entry-Typ. Default: text.'],
                'position' => ['type' => 'integer', 'description' => 'Optional: Position.'],
                'metadata' => ['type' => 'object', 'description' => 'Optional: Zusaetzliche Metadaten (JSON).'],
            ],
            'required' => ['building_block_id', 'title'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = (int)$resolved['team_id'];

            $blockId = (int)($arguments['building_block_id'] ?? 0);
            if ($blockId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'building_block_id ist erforderlich.');
            }

            $block = BuildingBlock::with('canvas.canvasType')->find($blockId);
            if (!$block || $block->canvas?->team_id !== $teamId) {
                return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden (oder kein Zugriff).');
            }

            $title = trim((string)($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            // Validate entry_type against canvas type
            $entryType = $arguments['entry_type'] ?? 'text';
            $allowedTypes = $block->canvas->canvasType->entry_types ?? ['text'];
            if (!in_array($entryType, $allowedTypes)) {
                return ToolResult::error('VALIDATION_ERROR', "entry_type '{$entryType}' ist fuer diesen Canvas-Typ nicht erlaubt. Erlaubt: " . implode(', ', $allowedTypes));
            }

            $entryService = new EntryService();
            $entry = $entryService->createEntry($block, [
                'title' => $title,
                'content' => $arguments['content'] ?? null,
                'entry_type' => $entryType,
                'position' => $arguments['position'] ?? null,
                'metadata' => $arguments['metadata'] ?? null,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'title' => $entry->title,
                'entry_type' => $entry->entry_type,
                'position' => $entry->position,
                'building_block_id' => $block->id,
                'canvas_id' => $block->canvas_id,
                'message' => 'Entry erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['canvas', 'entries', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
