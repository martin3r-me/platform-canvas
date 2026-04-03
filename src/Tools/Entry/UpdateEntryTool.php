<?php

namespace Platform\Canvas\Tools\Entry;

use Platform\Canvas\Models\Entry;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class UpdateEntryTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.entries.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /canvas/entries/{id} - Aktualisiert einen Canvas Entry. Parameter: entry_id (required). Optional: title, content, entry_type, position, metadata.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'entry_id' => ['type' => 'integer', 'description' => 'ID des Entries (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Optional: Neuer Titel.'],
                'content' => ['type' => 'string', 'description' => 'Optional: Neuer Inhalt.'],
                'entry_type' => ['type' => 'string', 'enum' => ['text', 'date', 'person', 'amount'], 'description' => 'Optional: Neuer Typ.'],
                'position' => ['type' => 'integer', 'description' => 'Optional: Neue Position.'],
                'metadata' => ['type' => 'object', 'description' => 'Optional: Neue Metadaten.'],
            ],
            'required' => ['entry_id'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $found = $this->validateAndFindModel($arguments, $context, 'entry_id', Entry::class, 'NOT_FOUND', 'Entry nicht gefunden.');
        if ($found['error']) return $found['error'];

        /** @var Entry $entry */
        $entry = $found['model'];
        $entry->load('buildingBlock.canvas');

        if ((int)$entry->buildingBlock->canvas->team_id !== $teamId) {
            return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf diesen Entry.');
        }

        if (array_key_exists('entry_type', $arguments)) {
            $entry->load('buildingBlock.canvas.canvasType');
            $allowedTypes = $entry->buildingBlock->canvas->canvasType->entry_types ?? ['text'];
            if (!in_array($arguments['entry_type'], $allowedTypes)) {
                return ToolResult::error('VALIDATION_ERROR', "entry_type '{$arguments['entry_type']}' nicht erlaubt.");
            }
        }

        foreach (['title', 'content', 'entry_type', 'position'] as $field) {
            if (array_key_exists($field, $arguments)) $entry->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
        }
        if (array_key_exists('metadata', $arguments)) $entry->metadata = $arguments['metadata'];

        $entry->save();

        return ToolResult::success([
            'id' => $entry->id, 'uuid' => $entry->uuid, 'title' => $entry->title, 'entry_type' => $entry->entry_type,
            'position' => $entry->position, 'building_block_id' => $entry->building_block_id,
            'message' => 'Entry erfolgreich aktualisiert.',
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Aktualisieren des Entries: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'entries', 'update'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
