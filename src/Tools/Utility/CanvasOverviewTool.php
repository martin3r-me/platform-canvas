<?php

namespace Platform\Canvas\Tools\Utility;

use Platform\Canvas\Models\CanvasType;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class CanvasOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string { return 'canvas.overview.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/overview - Zeigt Uebersicht ueber das Canvas Modul.';
    }

    public function getSchema(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass(), 'required' => []];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $systemTypes = CanvasType::system()->get();

            $canvasTypes = $systemTypes->map(fn (CanvasType $type) => [
                'key' => $type->key, 'name' => $type->name, 'description' => $type->description,
                'block_count' => is_array($type->block_definitions) ? count($type->block_definitions) : 0,
                'is_active' => $type->is_active,
            ])->values()->toArray();

            return ToolResult::success([
                'module' => 'canvas',
                'scope' => ['team_scoped' => true, 'team_id_source' => 'ToolContext.team bzw. team_id Parameter'],
                'canvas_types' => $canvasTypes,
                'concepts' => [
                    'canvas_types' => ['model' => 'Platform\\Canvas\\Models\\CanvasType', 'table' => 'canvas_types', 'key_fields' => ['id', 'uuid', 'key', 'name', 'description', 'origin', 'block_definitions', 'layout', 'is_active'], 'note' => 'Definiert einen Canvas-Typ.'],
                    'canvases' => ['model' => 'Platform\\Canvas\\Models\\Canvas', 'table' => 'canvases', 'key_fields' => ['id', 'uuid', 'name', 'description', 'status', 'canvas_type_id', 'team_id'], 'note' => 'Eine Canvas-Instanz.'],
                    'building_blocks' => ['model' => 'Platform\\Canvas\\Models\\BuildingBlock', 'table' => 'canvas_building_blocks', 'key_fields' => ['id', 'uuid', 'canvas_id', 'block_key', 'label', 'position'], 'note' => 'Bausteine eines Canvas.'],
                    'entries' => ['model' => 'Platform\\Canvas\\Models\\Entry', 'table' => 'canvas_entries', 'key_fields' => ['id', 'uuid', 'building_block_id', 'title', 'content', 'position', 'metadata'], 'note' => 'Eintraege in Building Blocks.'],
                    'canvas_snapshots' => ['model' => 'Platform\\Canvas\\Models\\CanvasSnapshot', 'table' => 'canvas_snapshots', 'key_fields' => ['id', 'uuid', 'canvas_id', 'version', 'snapshot_data'], 'note' => 'Versionierte Snapshots.'],
                ],
                'related_tools' => ['types' => ['overview' => 'canvas.overview.GET', 'list' => 'canvas.types.GET', 'get' => 'canvas.type.GET', 'create' => 'canvas.types.POST', 'update' => 'canvas.types.PUT', 'delete' => 'canvas.types.DELETE']],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return ['category' => 'overview', 'tags' => ['overview', 'help', 'canvas'], 'read_only' => true, 'requires_auth' => true, 'requires_team' => true, 'risk_level' => 'safe', 'idempotent' => true];
    }
}
