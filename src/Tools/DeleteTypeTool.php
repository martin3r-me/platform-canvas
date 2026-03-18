<?php

namespace Platform\Canvas\Tools;

use Platform\Canvas\Models\CanvasType;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class DeleteTypeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.types.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /canvas/types/{id} - Soft-loescht einen Custom Canvas-Typ. System-Typen (origin=system) koennen nicht geloescht werden. Parameter: type_id (required), confirm (required=true).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'type_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Canvas-Typs (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um wirklich zu loeschen.',
                ],
            ],
            'required' => ['type_id', 'confirm'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestaetige mit confirm: true.');
            }

            $found = $this->validateAndFindModel(
                $arguments,
                $context,
                'type_id',
                CanvasType::class,
                'NOT_FOUND',
                'Canvas-Typ nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var CanvasType $type */
            $type = $found['model'];

            if ($type->isSystem()) {
                return ToolResult::error('FORBIDDEN', 'System-Typen (origin=system) koennen nicht geloescht werden.');
            }

            if ($type->team_id && (int) $type->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Canvas-Typ.');
            }

            $typeId = (int) $type->id;
            $typeName = (string) $type->name;
            $typeKey = (string) $type->key;

            $type->delete();

            return ToolResult::success([
                'type_id' => $typeId,
                'key' => $typeKey,
                'name' => $typeName,
                'message' => 'Canvas-Typ soft-geloescht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen des Canvas-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'delete',
            'tags' => ['canvas', 'types', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
