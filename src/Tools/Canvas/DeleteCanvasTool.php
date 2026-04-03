<?php

namespace Platform\Canvas\Tools\Canvas;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class DeleteCanvasTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.canvases.DELETE'; }

    public function getDescription(): string
    {
        return 'DELETE /canvas/canvases/{id} - Soft-loescht einen Canvas. Parameter: canvas_id (required), confirm (required=true).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'canvas_id' => ['type' => 'integer', 'description' => 'ID des Canvas (ERFORDERLICH).'],
                'confirm' => ['type' => 'boolean', 'description' => 'ERFORDERLICH: Setze confirm=true.'],
            ],
            'required' => ['canvas_id', 'confirm'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        if (!($arguments['confirm'] ?? false)) {
            return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestaetige mit confirm: true.');
        }

        $found = $this->validateAndFindModel($arguments, $context, 'canvas_id', Canvas::class, 'NOT_FOUND', 'Canvas nicht gefunden.');
        if ($found['error']) return $found['error'];

        /** @var Canvas $canvas */
        $canvas = $found['model'];

        if ((int)$canvas->team_id !== $teamId) {
            return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Canvas.');
        }

        $canvasId = (int)$canvas->id;
        $canvasName = (string)$canvas->name;
        $canvas->delete();

        return ToolResult::success(['canvas_id' => $canvasId, 'name' => $canvasName, 'message' => 'Canvas soft-geloescht.']);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Loeschen des Canvas: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'delete', 'tags' => ['canvas', 'canvases', 'delete'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false];
    }
}
