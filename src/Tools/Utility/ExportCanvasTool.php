<?php

namespace Platform\Canvas\Tools\Utility;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\CanvasService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;

class ExportCanvasTool extends AbstractCanvasTool
{
    public function getName(): string { return 'canvas.export.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/export - Exportiert einen Canvas als strukturierte Daten. ERFORDERLICH: canvas_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'canvas_id' => ['type' => 'integer', 'description' => 'ID des Canvas (ERFORDERLICH).'],
            ],
            'required' => ['canvas_id'],
        ];
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $canvasId = (int)($arguments['canvas_id'] ?? 0);
        if ($canvasId <= 0) return ToolResult::error('VALIDATION_ERROR', 'canvas_id ist erforderlich.');

        $canvas = Canvas::query()->where('team_id', $teamId)->find($canvasId);
        if (!$canvas) return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden.');

        return ToolResult::success((new CanvasService())->exportCanvas($canvas));
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Exportieren des Canvas: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'export'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
