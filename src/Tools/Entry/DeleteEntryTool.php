<?php

namespace Platform\Canvas\Tools\Entry;

use Platform\Canvas\Models\Entry;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class DeleteEntryTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.entries.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /canvas/entries/{id} - Soft-loescht einen Canvas Entry. Parameter: entry_id (required), confirm (required=true).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'entry_id' => ['type' => 'integer', 'description' => 'ID des Entries (ERFORDERLICH).'],
                'confirm' => ['type' => 'boolean', 'description' => 'ERFORDERLICH: Setze confirm=true.'],
            ],
            'required' => ['entry_id', 'confirm'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if (!($arguments['confirm'] ?? false)) return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestaetige mit confirm: true.');

        $found = $this->validateAndFindModel($arguments, $context, 'entry_id', Entry::class, 'NOT_FOUND', 'Entry nicht gefunden.');
        if ($found['error']) return $found['error'];

        /** @var Entry $entry */
        $entry = $found['model'];
        $entry->load('buildingBlock.canvas');

        if ((int)$entry->buildingBlock->canvas->team_id !== $teamId) {
            return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff.');
        }

        $entryId = (int)$entry->id;
        $entryTitle = (string)$entry->title;
        $entry->delete();

        return ToolResult::success(['entry_id' => $entryId, 'title' => $entryTitle, 'message' => 'Entry soft-geloescht.']);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Loeschen des Entries: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'delete', 'tags' => ['canvas', 'entries', 'delete'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
