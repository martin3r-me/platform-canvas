<?php

namespace Platform\Canvas\Tools\Type;

use Platform\Canvas\Services\CanvasTypeService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class CreateTypeTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.types.POST'; }

    public function getDescription(): string
    {
        return 'POST /canvas/types - Erstellt einen neuen Custom Canvas-Typ. ERFORDERLICH: key, name, block_definitions, layout.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'key' => ['type' => 'string', 'description' => 'Eindeutiger Key (ERFORDERLICH).'],
                'name' => ['type' => 'string', 'description' => 'Name (ERFORDERLICH).'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'purpose' => ['type' => 'string', 'description' => 'Optional: Zweck.'],
                'methodology' => ['type' => 'string', 'description' => 'Optional: Methodik.'],
                'icon' => ['type' => 'string', 'description' => 'Optional: Icon.'],
                'block_definitions' => ['type' => 'array', 'description' => 'ERFORDERLICH: Block-Definitionen.'],
                'layout' => ['type' => 'object', 'description' => 'ERFORDERLICH: Layout-Konfiguration.'],
                'entry_types' => ['type' => 'array', 'description' => 'Optional: Entry-Typen.'],
                'analysis_config' => ['type' => 'object', 'description' => 'Optional: Analyse-Konfiguration.'],
            ],
            'required' => ['key', 'name', 'block_definitions', 'layout'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if ($error = $this->requireUser($context)) return $error;

        $key = trim((string)($arguments['key'] ?? ''));
        if ($key === '') return ToolResult::error('VALIDATION_ERROR', 'key ist erforderlich.');

        $name = trim((string)($arguments['name'] ?? ''));
        if ($name === '') return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');

        if (empty($arguments['block_definitions']) || !is_array($arguments['block_definitions'])) {
            return ToolResult::error('VALIDATION_ERROR', 'block_definitions ist erforderlich und muss ein Array sein.');
        }
        if (empty($arguments['layout']) || !is_array($arguments['layout'])) {
            return ToolResult::error('VALIDATION_ERROR', 'layout ist erforderlich und muss ein Objekt sein.');
        }

        try {
            $type = (new CanvasTypeService())->create([
                'key' => $key, 'name' => $name, 'description' => $arguments['description'] ?? null,
                'purpose' => $arguments['purpose'] ?? null, 'methodology' => $arguments['methodology'] ?? null,
                'icon' => $arguments['icon'] ?? null, 'block_definitions' => $arguments['block_definitions'],
                'layout' => $arguments['layout'], 'entry_types' => $arguments['entry_types'] ?? null,
                'analysis_config' => $arguments['analysis_config'] ?? null,
                'origin' => 'custom', 'is_active' => true, 'team_id' => $teamId, 'created_by_user_id' => $context->user->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            return ToolResult::error('VALIDATION_ERROR', $e->getMessage());
        }

        return ToolResult::success([
            'id' => $type->id, 'uuid' => $type->uuid, 'key' => $type->key, 'name' => $type->name,
            'origin' => $type->origin, 'block_count' => is_array($type->block_definitions) ? count($type->block_definitions) : 0,
            'team_id' => $type->team_id, 'message' => 'Canvas-Typ erfolgreich erstellt.',
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Erstellen des Canvas-Typs: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'types', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
