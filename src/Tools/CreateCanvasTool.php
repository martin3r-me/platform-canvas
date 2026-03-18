<?php

namespace Platform\Canvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Canvas\Services\CanvasService;
use Platform\Canvas\Services\CanvasTypeService;
use Platform\Canvas\Tools\Concerns\ResolvesCanvasTeam;

class CreateCanvasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesCanvasTeam;

    public function getName(): string
    {
        return 'canvas.canvases.POST';
    }

    public function getDescription(): string
    {
        return 'POST /canvas/canvases - Erstellt einen neuen Canvas (initialisiert automatisch Building Blocks basierend auf dem Canvas-Typ). ERFORDERLICH: name, type_key (oder canvas_type_id). Optional: description, status (default: draft), contextable_type, contextable_id.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Canvas (ERFORDERLICH).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'active', 'archived'],
                    'description' => 'Optional: Status (draft, active, archived). Default: draft.',
                ],
                'type_key' => [
                    'type' => 'string',
                    'description' => 'Canvas-Typ Key (z.B. bmc, swot, project-canvas, lean-canvas). ERFORDERLICH wenn canvas_type_id nicht angegeben.',
                ],
                'canvas_type_id' => [
                    'type' => 'integer',
                    'description' => 'Canvas-Typ ID. ERFORDERLICH wenn type_key nicht angegeben.',
                ],
                'contextable_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Polymorphic type (z.B. "Project").',
                ],
                'contextable_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Polymorphic ID.',
                ],
            ],
            'required' => ['name'],
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
            $teamId = (int)$resolved['team_id'];

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            // Resolve canvas type
            $canvasTypeId = null;

            if (isset($arguments['canvas_type_id'])) {
                $canvasTypeId = (int)$arguments['canvas_type_id'];
            } elseif (isset($arguments['type_key'])) {
                $canvasTypeService = new CanvasTypeService();
                $canvasType = $canvasTypeService->resolveByKey($arguments['type_key'], $teamId);

                if (!$canvasType) {
                    return ToolResult::error('VALIDATION_ERROR', "Canvas-Typ '{$arguments['type_key']}' nicht gefunden.");
                }
                $canvasTypeId = $canvasType->id;
            } else {
                return ToolResult::error('VALIDATION_ERROR', 'type_key oder canvas_type_id ist erforderlich.');
            }

            $canvasService = new CanvasService();
            $canvas = $canvasService->createCanvas([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'status' => $arguments['status'] ?? 'draft',
                'canvas_type_id' => $canvasTypeId,
                'contextable_type' => $arguments['contextable_type'] ?? null,
                'contextable_id' => $arguments['contextable_id'] ?? null,
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
            ]);

            $canvas->load('canvasType');
            $blockCount = $canvas->buildingBlocks->count();

            return ToolResult::success([
                'id' => $canvas->id,
                'uuid' => $canvas->uuid,
                'name' => $canvas->name,
                'status' => $canvas->status,
                'canvas_type_key' => $canvas->canvasType?->key,
                'canvas_type_name' => $canvas->canvasType?->name,
                'building_blocks_count' => $blockCount,
                'team_id' => $canvas->team_id,
                'message' => "Canvas erstellt mit {$blockCount} Building Blocks.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Canvas: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['canvas', 'canvases', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
