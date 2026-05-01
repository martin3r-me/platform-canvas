<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Events\WorkshopNoteChanged;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\WorkshopNote;
use Platform\Canvas\Services\AnalysisService;
use Platform\Canvas\Services\CommentService;
use Platform\Core\Services\ContextFileService;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntityLink;

class Show extends Component
{
    use WithFileUploads;

    public Canvas $canvas;

    public string $viewMode = 'list'; // 'list' | 'workshop'

    public string $commentContent = '';
    public ?int $commentBlockId = null;
    public ?int $replyToId = null;
    public ?int $filterBlockId = null;

    public $workshopFile;

    public function mount(Canvas $canvas): void
    {
        abort_unless($canvas->team_id === Auth::user()->currentTeam->id, 403);
        abort_unless($canvas->isVisibleTo(Auth::user()), 403);
        $canvas->loadMissing('canvasType');
        $this->canvas = $canvas;
    }

    public function getListeners(): array
    {
        $listeners = [];

        try {
            if (auth()->check() && $this->canvas) {
                $canvasId = $this->canvas->id;
                $listeners["echo-private:canvas.workshop.{$canvasId},.note.changed"] = 'onNoteChanged';
            }
        } catch (\Throwable $e) {
            // Fail silently
        }

        return $listeners;
    }

    public function onNoteChanged($payload): void
    {
        if (($payload['senderId'] ?? null) === Auth::id()) {
            return;
        }

        $this->dispatch('workshop-note-changed', $payload);
    }

    private function broadcastNoteChange(string $action, int $noteId, array $data = []): void
    {
        try {
            WorkshopNoteChanged::dispatch(
                $this->canvas->id,
                Auth::id(),
                $action,
                $noteId,
                $data,
            );
        } catch (\Throwable $e) {
            // Broadcasting failure should not break the CRUD operation
        }
    }

    public function toggleVisibility(): void
    {
        $this->canvas->update([
            'visibility' => $this->canvas->visibility === Canvas::VISIBILITY_PRIVATE
                ? Canvas::VISIBILITY_TEAM
                : Canvas::VISIBILITY_PRIVATE,
        ]);
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => 'Canvas',
            'modelId' => $this->canvas->id,
            'subject' => $this->canvas->name,
            'description' => $this->canvas->canvasType?->name ?? 'Canvas',
            'url' => route('canvas.canvases.show', $this->canvas),
            'source' => 'canvas.canvases.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show'],
        ]);
    }

    public function createPublicLink(): void
    {
        $this->canvas->generatePublicToken();
    }

    public function togglePublicLink(): void
    {
        $this->canvas->update(['is_public' => ! $this->canvas->is_public]);
    }

    public function addComment(): void
    {
        $this->validate([
            'commentContent' => 'required|string|min:1|max:5000',
            'commentBlockId' => 'nullable|integer|exists:canvas_building_blocks,id',
            'replyToId' => 'nullable|integer|exists:canvas_comments,id',
        ]);

        try {
            (new CommentService())->addComment($this->canvas, [
                'content' => $this->commentContent,
                'building_block_id' => $this->replyToId ? null : $this->commentBlockId,
                'parent_id' => $this->replyToId,
            ]);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        $this->reset('commentContent', 'commentBlockId', 'replyToId');
    }

    public function setReplyTo(int $commentId): void
    {
        $this->replyToId = $commentId;
        $this->commentBlockId = null;
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
    }

    public function deleteComment(int $commentId): void
    {
        (new CommentService())->deleteComment($this->canvas, $commentId);
    }

    public function filterByBlock(?int $blockId): void
    {
        $this->filterBlockId = $blockId;
    }

    // ─── File Upload (Workshop) ────────────────────────────

    public function updatedWorkshopFile(): void
    {
        $this->validate([
            'workshopFile' => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mov,avi',
        ]);

        $service = new ContextFileService();
        $result = $service->uploadForContext(
            $this->workshopFile,
            Canvas::class,
            $this->canvas->id,
            [
                'generate_variants' => false,
                'user_id' => Auth::id(),
                'team_id' => Auth::user()->currentTeam->id,
            ]
        );

        $this->dispatch('workshop-file-uploaded', [
            'contextFileId' => $result['id'],
            'url' => $result['url'],
            'mimeType' => $result['mime_type'],
            'width' => $result['width'],
            'height' => $result['height'],
            'originalName' => $result['original_name'],
        ]);

        $this->workshopFile = null;
    }

    public function refreshFileUrl(int $contextFileId): array
    {
        $contextFile = \Platform\Core\Models\ContextFile::find($contextFileId);
        abort_unless($contextFile && $contextFile->team_id === Auth::user()->currentTeam->id, 403);

        $url = ContextFileService::generateUrl(
            $contextFile->disk,
            $contextFile->path,
            $contextFile->token,
            'core.context-files.show',
            60
        );

        return ['url' => $url];
    }

    // ─── View Mode ──────────────────────────────────────────

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'list' ? 'workshop' : 'list';
    }

    // ─── Workshop CRUD (WorkshopNote-based) ────────────────

    public function addWorkshopNote(array $position = [], string $type = 'note'): void
    {
        if (!in_array($type, WorkshopNote::allowedTypes())) {
            $type = 'note';
        }

        $existingCount = $this->canvas->workshopNotes()->count();
        $offset = $existingCount * 25; // stagger stacked notes

        // Use viewport-center position from JS, or default to grid center
        $cols = (int) ($this->canvas->canvasType?->layout['columns'] ?? 3);
        $rows = (int) ($this->canvas->canvasType?->layout['rows'] ?? 3);
        $defaultX = (5000 - max(1200, $cols * 300)) / 2 + 100;
        $defaultY = (3000 - max(800, $rows * 300)) / 2 + 100;

        // Type-specific defaults
        $defaults = match ($type) {
            'text' => ['width' => 300, 'height' => 40, 'color' => 'yellow', 'metadata' => null],
            'section' => ['width' => 500, 'height' => 400, 'color' => 'yellow', 'metadata' => null],
            'shape' => ['width' => 120, 'height' => 120, 'color' => 'blue', 'metadata' => ['shape' => 'rect']],
            'connector' => ['width' => 0, 'height' => 0, 'color' => 'blue', 'metadata' => null],
            'kanban' => ['width' => 600, 'height' => 400, 'color' => 'blue', 'metadata' => [
                'columns' => [
                    ['id' => 'col_' . base_convert(time(), 10, 36) . 'a', 'title' => 'To Do', 'wipLimit' => 0, 'cards' => []],
                    ['id' => 'col_' . base_convert(time(), 10, 36) . 'b', 'title' => 'In Progress', 'wipLimit' => 3, 'cards' => []],
                    ['id' => 'col_' . base_convert(time(), 10, 36) . 'c', 'title' => 'Done', 'wipLimit' => 0, 'cards' => []],
                ],
            ]],
            'image' => ['width' => 300, 'height' => 300, 'color' => 'yellow', 'metadata' => null],
            'image_grid' => ['width' => 500, 'height' => 400, 'color' => 'yellow', 'metadata' => ['images' => [], 'columns' => 2, 'gap' => 4]],
            'video' => ['width' => 480, 'height' => 300, 'color' => 'blue', 'metadata' => null],
            default => ['width' => 200, 'height' => 150, 'color' => 'yellow', 'metadata' => null],
        };

        $note = WorkshopNote::create([
            'canvas_id' => $this->canvas->id,
            'title' => '',
            'content' => '',
            'color' => $defaults['color'],
            'type' => $type,
            'position_x' => ($position['x'] ?? $defaultX) + $offset,
            'position_y' => ($position['y'] ?? $defaultY) + $offset,
            'width' => $defaults['width'],
            'height' => $defaults['height'],
            'metadata' => $defaults['metadata'],
            'created_by_user_id' => Auth::id(),
        ]);

        $this->broadcastNoteChange('created', $note->id, [
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content ?? '',
            'color' => $note->color,
            'type' => $note->type ?? 'note',
            'x' => $note->position_x,
            'y' => $note->position_y,
            'width' => $note->width,
            'height' => $note->height,
            'metadata' => $note->metadata,
        ]);
    }

    public function updateNotePosition(int $noteId, array $pos): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        $blockId = isset($pos['blockId']) ? (int) $pos['blockId'] : null;

        // Verify the block belongs to this canvas if provided
        if ($blockId) {
            $block = \Platform\Canvas\Models\BuildingBlock::find($blockId);
            if (!$block || $block->canvas_id !== $this->canvas->id) {
                $blockId = null;
            }
        }

        $note->update([
            'position_x' => $pos['x'] ?? $note->position_x,
            'position_y' => $pos['y'] ?? $note->position_y,
            'width' => isset($pos['width']) ? (int) $pos['width'] : $note->width,
            'height' => isset($pos['height']) ? (int) $pos['height'] : $note->height,
            'building_block_id' => $blockId,
        ]);

        $this->broadcastNoteChange('moved', $noteId, [
            'x' => $note->position_x,
            'y' => $note->position_y,
            'width' => $note->width,
            'height' => $note->height,
        ]);
    }

    public function updateNoteText(int $noteId, string $title, string $content): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        $note->update([
            'title' => $title,
            'content' => $content,
        ]);

        $this->broadcastNoteChange('text_updated', $noteId, [
            'title' => $title,
            'content' => $content,
        ]);
    }

    public function updateNoteColor(int $noteId, string $color): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        if (!in_array($color, WorkshopNote::allowedColors())) return;

        $note->update(['color' => $color]);

        $this->broadcastNoteChange('color_updated', $noteId, [
            'color' => $color,
        ]);
    }

    public function addConnector(int $fromNoteId, int $toNoteId): void
    {
        $fromNote = WorkshopNote::find($fromNoteId);
        $toNote = WorkshopNote::find($toNoteId);
        abort_unless($fromNote && $fromNote->canvas_id === $this->canvas->id, 403);
        abort_unless($toNote && $toNote->canvas_id === $this->canvas->id, 403);

        // Position at midpoint between the two elements
        $midX = ($fromNote->position_x + $toNote->position_x) / 2;
        $midY = ($fromNote->position_y + $toNote->position_y) / 2;

        $connector = WorkshopNote::create([
            'canvas_id' => $this->canvas->id,
            'title' => '',
            'content' => '',
            'color' => 'blue',
            'type' => 'connector',
            'position_x' => $midX,
            'position_y' => $midY,
            'width' => 0,
            'height' => 0,
            'metadata' => [
                'fromNoteId' => $fromNoteId,
                'toNoteId' => $toNoteId,
                'style' => 'solid',
                'arrowHead' => 'end',
            ],
            'created_by_user_id' => Auth::id(),
        ]);

        $this->broadcastNoteChange('created', $connector->id, [
            'id' => $connector->id,
            'title' => '',
            'content' => '',
            'color' => 'blue',
            'type' => 'connector',
            'x' => $connector->position_x,
            'y' => $connector->position_y,
            'width' => 0,
            'height' => 0,
            'metadata' => $connector->metadata,
        ]);
    }

    public function deleteWorkshopNote(int $noteId): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        // Cascade: delete connectors referencing this note
        if ($note->type !== 'connector') {
            $this->canvas->workshopNotes()
                ->where('type', 'connector')
                ->get()
                ->filter(function (WorkshopNote $c) use ($noteId) {
                    $meta = $c->metadata ?? [];
                    return ($meta['fromNoteId'] ?? null) === $noteId
                        || ($meta['toNoteId'] ?? null) === $noteId;
                })
                ->each(function (WorkshopNote $c) {
                    $this->broadcastNoteChange('deleted', $c->id);
                    $c->delete();
                });
        }

        $note->delete();

        $this->broadcastNoteChange('deleted', $noteId);
    }

    public function getWorkshopNotes(): array
    {
        return $this->canvas->workshopNotes()
            ->orderBy('created_at')
            ->get()
            ->map(fn (WorkshopNote $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content ?? '',
                'color' => $n->color,
                'type' => $n->type ?? 'note',
                'x' => $n->position_x,
                'y' => $n->position_y,
                'width' => $n->width,
                'height' => $n->height,
                'metadata' => $n->metadata,
            ])
            ->values()
            ->toArray();
    }

    public function updateNoteMetadata(int $noteId, array $meta): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        $note->update([
            'metadata' => array_merge($note->metadata ?? [], $meta),
        ]);

        $this->broadcastNoteChange('metadata_updated', $noteId, [
            'metadata' => $note->fresh()->metadata,
        ]);
    }

    public function updateWorkshopSettings(array $settings): void
    {
        $allowed = ['gridWidth', 'gridHeight'];
        $current = $this->canvas->workshop_settings ?? [];
        $merged = array_merge($current, array_intersect_key($settings, array_flip($allowed)));

        $this->canvas->update(['workshop_settings' => $merged]);
    }

    public function adoptNote(int $noteId, int $blockId): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        $block = \Platform\Canvas\Models\BuildingBlock::find($blockId);
        abort_unless($block && $block->canvas_id === $this->canvas->id, 403);

        $existingCount = $block->entries()->count();

        \Platform\Canvas\Models\Entry::create([
            'building_block_id' => $block->id,
            'title' => $note->title,
            'content' => $note->content ?? '',
            'entry_type' => 'text',
            'position' => $existingCount + 1,
            'created_by_user_id' => $note->created_by_user_id,
        ]);

        $note->delete();

        $this->broadcastNoteChange('deleted', $noteId);
    }

    #[Computed]
    public function activities()
    {
        if (!$this->canvas) {
            return collect();
        }

        return $this->canvas->activities()
            ->with('user')
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'title' => $this->formatActivityTitle($activity),
                    'time' => $activity->created_at->diffForHumans(),
                    'user' => $activity->user?->name ?? 'System',
                    'type' => $activity->activity_type,
                    'name' => $activity->name,
                ];
            });
    }

    private function formatActivityTitle($activity): string
    {
        $userName = $activity->user?->name ?? 'System';
        $activityName = $activity->name;

        $translations = [
            'created' => 'erstellt',
            'updated' => 'aktualisiert',
            'deleted' => 'geloescht',
            'manual' => 'hat eine Nachricht hinzugefuegt',
        ];

        $translatedName = $translations[$activityName] ?? $activityName;

        if ($activity->message) {
            return "{$userName}: {$activity->message}";
        }

        if ($activity->properties && !empty($activity->properties)) {
            $props = $activity->properties;
            $changedFields = [];

            if (isset($props['old']) || isset($props['new'])) {
                $changedFields = array_keys($props['new'] ?? $props['old'] ?? []);
            } else {
                $changedFields = array_keys($props);
            }

            if (!empty($changedFields)) {
                $fieldNames = array_map(function ($field) {
                    $map = [
                        'name' => 'Name',
                        'description' => 'Beschreibung',
                        'status' => 'Status',
                        'is_public' => 'Oeffentlich',
                        'public_token' => 'Public Token',
                    ];
                    return $map[$field] ?? $field;
                }, $changedFields);

                $fields = implode(', ', $fieldNames);
                return "{$userName} hat {$fields} {$translatedName}";
            }
        }

        return "{$userName} hat das Canvas {$translatedName}";
    }

    protected function loadEntityLinks(): array
    {
        $links = [];
        $morphTypes = ['canvas', Canvas::class];

        try {
            $contexts = OrganizationContext::query()
                ->whereIn('contextable_type', $morphTypes)
                ->where('contextable_id', $this->canvas->id)
                ->where('is_active', true)
                ->with(['organizationEntity.type'])
                ->get();

            foreach ($contexts as $ctx) {
                if ($ctx->organizationEntity) {
                    $links[] = [
                        'name' => $ctx->organizationEntity->name,
                        'type' => $ctx->organizationEntity->type?->name ?? 'Entity',
                        'icon' => $ctx->organizationEntity->type?->icon,
                    ];
                }
            }

            $entityLinks = OrganizationEntityLink::query()
                ->whereIn('linkable_type', $morphTypes)
                ->where('linkable_id', $this->canvas->id)
                ->with(['entity.type'])
                ->get();

            foreach ($entityLinks as $link) {
                if ($link->entity) {
                    $links[] = [
                        'name' => $link->entity->name,
                        'type' => $link->entity->type?->name ?? 'Entity',
                        'icon' => $link->entity->type?->icon,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Organization module not available
        }

        return collect($links)->unique('name')->values()->toArray();
    }

    public function render()
    {
        $this->canvas->load(['canvasType', 'buildingBlocks.entries', 'createdByUser', 'snapshots', 'tags', 'contextColors']);

        $canvasData = $this->canvas->toCanvasArray();
        $analysisData = (new AnalysisService())->analyze($this->canvas);
        $rawLayout = $this->canvas->canvasType?->layout ?? [];
        // Pass layout values through — the view handles both string and array formats for 'areas'
        $layout = [
            'type' => $rawLayout['type'] ?? 'grid',
            'columns' => (int) ($rawLayout['columns'] ?? 2),
            'rows' => (int) ($rawLayout['rows'] ?? 2),
            'areas' => $rawLayout['areas'] ?? '',
            'area_map' => is_array($rawLayout['area_map'] ?? null) ? $rawLayout['area_map'] : [],
        ];
        $blockDefs = $this->canvas->canvasType?->block_definitions ?? [];

        $commentService = new CommentService();
        $comments = $commentService->getCommentsForCanvas($this->canvas, $this->filterBlockId);
        $allComments = $this->canvas->comments()->get();

        // Entity-Links laden
        $entityLinks = $this->loadEntityLinks();

        // Workshop: load notes for the notes layer
        $workshopNotes = [];
        if ($this->viewMode === 'workshop') {
            $workshopNotes = $this->canvas->workshopNotes()
                ->orderBy('created_at')
                ->get()
                ->map(fn (WorkshopNote $n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'content' => $n->content ?? '',
                    'color' => $n->color,
                    'type' => $n->type ?? 'note',
                    'x' => $n->position_x,
                    'y' => $n->position_y,
                    'width' => $n->width,
                    'height' => $n->height,
                    'metadata' => $n->metadata,
                ])
                ->values()
                ->toArray();
        }

        return view('canvas::livewire.canvas.show', [
            'canvasData' => $canvasData,
            'analysisData' => $analysisData,
            'layout' => $layout,
            'blockDefs' => $blockDefs,
            'comments' => $comments,
            'allComments' => $allComments,
            'entityLinks' => $entityLinks,
            'activities' => $this->activities,
            'workshopNotes' => $workshopNotes,
        ])->layout('platform::layouts.app');
    }
}
