<?php

namespace Platform\Canvas\Tools\Canvas;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\CanvasService;
use Platform\Canvas\Services\CanvasTypeService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class CreateCanvasTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.canvases.POST'; }

    public function getDescription(): string
    {
        return 'POST /canvas/canvases - Erstellt einen neuen Canvas. ERFORDERLICH: name, type_key (oder canvas_type_id). Optional: description, status, visibility (team|private), contextable_type, contextable_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'name' => ['type' => 'string', 'description' => 'Name des Canvas (ERFORDERLICH).'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'status' => ['type' => 'string', 'enum' => Canvas::STATUSES, 'description' => 'Optional: Status. Default: backlog.'],
                'type_key' => ['type' => 'string', 'description' => 'Canvas-Typ Key. ERFORDERLICH wenn canvas_type_id nicht angegeben.'],
                'canvas_type_id' => ['type' => 'integer', 'description' => 'Canvas-Typ ID. ERFORDERLICH wenn type_key nicht angegeben.'],
                'visibility' => ['type' => 'string', 'enum' => [Canvas::VISIBILITY_TEAM, Canvas::VISIBILITY_PRIVATE], 'description' => 'Optional: Sichtbarkeit. Default: "team". "private" = nur Ersteller sieht es.'],
                'contextable_type' => ['type' => 'string', 'description' => 'Optional: Polymorphic type.'],
                'contextable_id' => ['type' => 'integer', 'description' => 'Optional: Polymorphic ID.'],
            ],
            'required' => ['name'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if ($error = $this->requireUser($context)) return $error;

        $name = trim((string)($arguments['name'] ?? ''));
        if ($name === '') {
            return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
        }

        $canvasTypeId = null;
        if (isset($arguments['canvas_type_id'])) {
            $canvasTypeId = (int)$arguments['canvas_type_id'];
        } elseif (isset($arguments['type_key'])) {
            $canvasType = (new CanvasTypeService())->resolveByKey($arguments['type_key'], $teamId);
            if (!$canvasType) {
                return ToolResult::error('VALIDATION_ERROR', "Canvas-Typ '{$arguments['type_key']}' nicht gefunden.");
            }
            $canvasTypeId = $canvasType->id;
        } else {
            return ToolResult::error('VALIDATION_ERROR', 'type_key oder canvas_type_id ist erforderlich.');
        }

        $visibility = $arguments['visibility'] ?? Canvas::VISIBILITY_TEAM;
        if (!in_array($visibility, [Canvas::VISIBILITY_TEAM, Canvas::VISIBILITY_PRIVATE])) {
            return ToolResult::error('VALIDATION_ERROR', 'Ungueltige Sichtbarkeit. Erlaubt: team, private.');
        }

        $canvas = (new CanvasService())->createCanvas([
            'name' => $name, 'description' => $arguments['description'] ?? null,
            'status' => $arguments['status'] ?? Canvas::STATUS_BACKLOG, 'visibility' => $visibility,
            'canvas_type_id' => $canvasTypeId,
            'contextable_type' => $arguments['contextable_type'] ?? null, 'contextable_id' => $arguments['contextable_id'] ?? null,
            'team_id' => $teamId, 'created_by_user_id' => $context->user->id,
        ]);

        $canvas->load('canvasType');

        return ToolResult::success([
            'id' => $canvas->id, 'uuid' => $canvas->uuid, 'name' => $canvas->name, 'status' => $canvas->status,
            'visibility' => $canvas->visibility,
            'canvas_type_key' => $canvas->canvasType?->key, 'canvas_type_name' => $canvas->canvasType?->name,
            'building_blocks_count' => $canvas->buildingBlocks->count(), 'team_id' => $canvas->team_id,
            'message' => "Canvas erstellt mit {$canvas->buildingBlocks->count()} Building Blocks.",
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Erstellen des Canvas: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'canvases', 'create'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
