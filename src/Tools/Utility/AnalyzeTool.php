<?php

namespace Platform\Canvas\Tools\Utility;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\AnalysisService;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;

class AnalyzeTool extends AbstractCanvasTool
{
    public function getName(): string { return 'canvas.analyze.GET'; }

    public function getDescription(): string
    {
        return 'GET /canvas/analyze - Analysiert einen Canvas und liefert strategie-spezifische Ergebnisse. ERFORDERLICH: canvas_id.';
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

        return ToolResult::success((new AnalysisService())->analyze($canvas));
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Analysieren des Canvas: '; }

    public function getMetadata(): array
    {
        return ['read_only' => true, 'category' => 'read', 'tags' => ['canvas', 'analyze', 'metrics'], 'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
