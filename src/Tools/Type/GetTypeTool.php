<?php

namespace Platform\Canvas\Tools\Type;

use Platform\Canvas\Models\CanvasType;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;

class GetTypeTool extends AbstractCanvasTool
{
    public function getName(): string { return 'canvas.type.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/types/{id} - Ruft einen einzelnen Canvas-Typ ab. Parameter: type_id oder type_key (eins erforderlich), team_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type_id' => ['type' => 'integer', 'description' => 'ID des Canvas-Typs.'],
                'type_key' => ['type' => 'string', 'description' => 'Key des Canvas-Typs.'],
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            ],
            'required' => [],
        ];
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $typeId = $arguments['type_id'] ?? null;
        $typeKey = $arguments['type_key'] ?? null;

        if (!$typeId && !$typeKey) {
            return ToolResult::error('VALIDATION_ERROR', 'Entweder type_id oder type_key ist erforderlich.');
        }

        $type = null;
        if ($typeId) {
            $type = CanvasType::query()->availableForTeam($teamId)->find((int) $typeId);
        } elseif ($typeKey) {
            $type = CanvasType::query()->availableForTeam($teamId)->byKey($typeKey)->first();
        }

        if (!$type) {
            return ToolResult::error('NOT_FOUND', 'Canvas-Typ nicht gefunden (oder kein Zugriff).');
        }

        return ToolResult::success(['type' => [
            'id' => $type->id, 'uuid' => $type->uuid, 'key' => $type->key, 'name' => $type->name,
            'description' => $type->description, 'purpose' => $type->purpose, 'methodology' => $type->methodology,
            'icon' => $type->icon, 'origin' => $type->origin, 'is_active' => $type->is_active,
            'block_definitions' => $type->block_definitions, 'layout' => $type->layout,
            'entry_types' => $type->entry_types, 'analysis_config' => $type->analysis_config,
            'team_id' => $type->team_id, 'created_by_user_id' => $type->created_by_user_id,
            'created_at' => $type->created_at?->toISOString(), 'updated_at' => $type->updated_at?->toISOString(),
        ]]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Laden des Canvas-Typs: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'type', 'get'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
