<?php

namespace Platform\Canvas\Tools;

use Platform\Canvas\Models\CanvasType;
use Platform\Canvas\Services\CanvasTypeService;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class UpdateTypeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.types.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /canvas/types/{id} - Aktualisiert einen Custom Canvas-Typ. System-Typen (origin=system) koennen nicht veraendert werden. Parameter: type_id (required). Optional: name, description, purpose, methodology, icon, block_definitions, layout, entry_types, analysis_config, is_active.';
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
                    'description' => 'ID des Canvas-Typs (ERFORDERLICH). Nutze "canvas.types.GET" um IDs zu finden.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'purpose' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Zweck/Ziel.',
                ],
                'methodology' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Methodik.',
                ],
                'icon' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Icon.',
                ],
                'block_definitions' => [
                    'type' => 'array',
                    'description' => 'Optional: Neue Block-Definitionen.',
                ],
                'layout' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Layout-Konfiguration.',
                ],
                'entry_types' => [
                    'type' => 'array',
                    'description' => 'Optional: Neue Entry-Typen.',
                ],
                'analysis_config' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Analyse-Konfiguration.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv/Inaktiv setzen.',
                ],
            ],
            'required' => ['type_id'],
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
                return ToolResult::error('FORBIDDEN', 'System-Typen (origin=system) koennen nicht veraendert werden.');
            }

            if ($type->team_id && (int) $type->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Canvas-Typ.');
            }

            $updateData = [];
            foreach (['name', 'description', 'purpose', 'methodology', 'icon'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            foreach (['block_definitions', 'layout', 'entry_types', 'analysis_config'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (array_key_exists('is_active', $arguments)) {
                $updateData['is_active'] = (bool) $arguments['is_active'];
            }

            if (empty($updateData)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Felder zum Aktualisieren angegeben.');
            }

            $service = new CanvasTypeService();
            $type = $service->update($type, $updateData);

            return ToolResult::success([
                'id' => $type->id,
                'uuid' => $type->uuid,
                'key' => $type->key,
                'name' => $type->name,
                'origin' => $type->origin,
                'is_active' => $type->is_active,
                'block_count' => is_array($type->block_definitions) ? count($type->block_definitions) : 0,
                'team_id' => $type->team_id,
                'message' => 'Canvas-Typ erfolgreich aktualisiert.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return ToolResult::error('VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Canvas-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['canvas', 'types', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
