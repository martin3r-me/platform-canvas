<?php

namespace Platform\Canvas\Tools\Type;

use Platform\Canvas\Models\CanvasType;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class DeleteTypeTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.types.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /canvas/types/{id} - Soft-loescht einen Custom Canvas-Typ. System-Typen koennen nicht geloescht werden.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'type_id' => ['type' => 'integer', 'description' => 'ID des Canvas-Typs (ERFORDERLICH).'],
                'confirm' => ['type' => 'boolean', 'description' => 'ERFORDERLICH: Setze confirm=true.'],
            ],
            'required' => ['type_id', 'confirm'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if (!($arguments['confirm'] ?? false)) return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestaetige mit confirm: true.');

        $found = $this->validateAndFindModel($arguments, $context, 'type_id', CanvasType::class, 'NOT_FOUND', 'Canvas-Typ nicht gefunden.');
        if ($found['error']) return $found['error'];

        /** @var CanvasType $type */
        $type = $found['model'];

        if ($type->isSystem()) return ToolResult::error('FORBIDDEN', 'System-Typen koennen nicht geloescht werden.');
        if ($type->team_id && (int)$type->team_id !== $teamId) return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff.');

        $typeId = (int)$type->id;
        $typeName = (string)$type->name;
        $typeKey = (string)$type->key;
        $type->delete();

        return ToolResult::success(['type_id' => $typeId, 'key' => $typeKey, 'name' => $typeName, 'message' => 'Canvas-Typ soft-geloescht.']);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Loeschen des Canvas-Typs: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'delete', 'tags' => ['canvas', 'types', 'delete'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
