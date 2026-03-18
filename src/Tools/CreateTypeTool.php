<?php

namespace Platform\Canvas\Tools;

use Platform\Canvas\Services\CanvasTypeService;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class CreateTypeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.types.POST';
    }

    public function getDescription(): string
    {
        return 'POST /canvas/types - Erstellt einen neuen Custom Canvas-Typ. ERFORDERLICH: key, name, block_definitions (array), layout (object). Optional: description, purpose, methodology, icon, entry_types, analysis_config, team_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Eindeutiger Key des Canvas-Typs (ERFORDERLICH). z.B. "lean_canvas", "value_proposition".',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Canvas-Typs (ERFORDERLICH).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Canvas-Typs.',
                ],
                'purpose' => [
                    'type' => 'string',
                    'description' => 'Optional: Zweck/Ziel des Canvas-Typs.',
                ],
                'methodology' => [
                    'type' => 'string',
                    'description' => 'Optional: Zugrunde liegende Methodik.',
                ],
                'icon' => [
                    'type' => 'string',
                    'description' => 'Optional: Icon-Name oder -Klasse.',
                ],
                'block_definitions' => [
                    'type' => 'array',
                    'description' => 'ERFORDERLICH: Array von Block-Definitionen. Jede Definition: {key, label, description?, guiding_questions?[], color?, icon?}.',
                ],
                'layout' => [
                    'type' => 'object',
                    'description' => 'ERFORDERLICH: Layout-Konfiguration. Format: {type: "grid", columns: int, rows: int, areas: [...]}.',
                ],
                'entry_types' => [
                    'type' => 'array',
                    'description' => 'Optional: Definierte Entry-Typen fuer diesen Canvas.',
                ],
                'analysis_config' => [
                    'type' => 'object',
                    'description' => 'Optional: Konfiguration fuer Analyse-Features.',
                ],
            ],
            'required' => ['key', 'name', 'block_definitions', 'layout'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int) $resolved['team_id'];

            $key = trim((string) ($arguments['key'] ?? ''));
            if ($key === '') {
                return ToolResult::error('VALIDATION_ERROR', 'key ist erforderlich.');
            }

            $name = trim((string) ($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            if (empty($arguments['block_definitions']) || !is_array($arguments['block_definitions'])) {
                return ToolResult::error('VALIDATION_ERROR', 'block_definitions ist erforderlich und muss ein Array sein.');
            }

            if (empty($arguments['layout']) || !is_array($arguments['layout'])) {
                return ToolResult::error('VALIDATION_ERROR', 'layout ist erforderlich und muss ein Objekt sein.');
            }

            $service = new CanvasTypeService();
            $type = $service->create([
                'key' => $key,
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'purpose' => $arguments['purpose'] ?? null,
                'methodology' => $arguments['methodology'] ?? null,
                'icon' => $arguments['icon'] ?? null,
                'block_definitions' => $arguments['block_definitions'],
                'layout' => $arguments['layout'],
                'entry_types' => $arguments['entry_types'] ?? null,
                'analysis_config' => $arguments['analysis_config'] ?? null,
                'origin' => 'custom',
                'is_active' => true,
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $type->id,
                'uuid' => $type->uuid,
                'key' => $type->key,
                'name' => $type->name,
                'origin' => $type->origin,
                'block_count' => is_array($type->block_definitions) ? count($type->block_definitions) : 0,
                'team_id' => $type->team_id,
                'message' => 'Canvas-Typ erfolgreich erstellt.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return ToolResult::error('VALIDATION_ERROR', $e->getMessage());
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Canvas-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['canvas', 'types', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
