<?php

namespace Platform\Canvas\Tools\Canvas;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Tools\AbstractCanvasTool;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;

class UpdateCanvasTool extends AbstractCanvasTool
{
    use HasStandardizedWriteOperations;

    public function getName(): string { return 'canvas.canvases.PUT'; }

    public function getDescription(): string
    {
        return 'PUT /canvas/canvases/{id} - Aktualisiert einen Canvas. Parameter: canvas_id (required). Optional: name, description, status, visibility (team|private).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'canvas_id' => ['type' => 'integer', 'description' => 'ID des Canvas (ERFORDERLICH).'],
                'name' => ['type' => 'string', 'description' => 'Optional: Neuer Name.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Neue Beschreibung.'],
                'status' => ['type' => 'string', 'enum' => Canvas::STATUSES, 'description' => 'Optional: Neuer Status.'],
                'visibility' => ['type' => 'string', 'enum' => [Canvas::VISIBILITY_TEAM, Canvas::VISIBILITY_PRIVATE], 'description' => 'Optional: Sichtbarkeit. "team" = alle Team-Mitglieder, "private" = nur Ersteller.'],
            ],
            'required' => ['canvas_id'],
        ]);
    }

    protected function doExecute(array $arguments, ToolContext $context, int $teamId): ToolResult
    {
        $found = $this->validateAndFindModel($arguments, $context, 'canvas_id', Canvas::class, 'NOT_FOUND', 'Canvas nicht gefunden.');
        if ($found['error']) return $found['error'];

        /** @var Canvas $canvas */
        $canvas = $found['model'];

        if ((int)$canvas->team_id !== $teamId) {
            return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Canvas.');
        }

        if (!$canvas->isVisibleTo($context->user)) {
            return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Canvas.');
        }

        foreach (['name', 'description'] as $field) {
            if (array_key_exists($field, $arguments)) {
                $canvas->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
            }
        }

        if (array_key_exists('status', $arguments)) {
            $newStatus = $arguments['status'];
            if (!in_array($newStatus, Canvas::STATUSES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungueltiger Status.');
            }
            $canvas->status = $newStatus;
        }

        if (array_key_exists('visibility', $arguments)) {
            $newVisibility = $arguments['visibility'];
            if (!in_array($newVisibility, [Canvas::VISIBILITY_TEAM, Canvas::VISIBILITY_PRIVATE])) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungueltige Sichtbarkeit. Erlaubt: team, private.');
            }
            $canvas->visibility = $newVisibility;
        }

        $canvas->save();
        $canvas->load('canvasType');

        return ToolResult::success([
            'id' => $canvas->id, 'uuid' => $canvas->uuid, 'name' => $canvas->name, 'status' => $canvas->status,
            'visibility' => $canvas->visibility,
            'canvas_type_key' => $canvas->canvasType?->key, 'canvas_type_name' => $canvas->canvasType?->name,
            'team_id' => $canvas->team_id, 'message' => 'Canvas erfolgreich aktualisiert.',
        ]);
    }

    protected function getErrorPrefix(): string { return 'Fehler beim Aktualisieren des Canvas: '; }

    public function getMetadata(): array
    {
        return ['read_only' => false, 'category' => 'action', 'tags' => ['canvas', 'canvases', 'update'], 'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true];
    }
}
