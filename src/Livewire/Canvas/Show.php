<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\WorkshopNote;
use Platform\Canvas\Services\AnalysisService;
use Platform\Canvas\Services\CommentService;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntityLink;

class Show extends Component
{
    public Canvas $canvas;

    public string $viewMode = 'list'; // 'list' | 'workshop'

    public string $commentContent = '';
    public ?int $commentBlockId = null;
    public ?int $replyToId = null;
    public ?int $filterBlockId = null;

    public function mount(Canvas $canvas): void
    {
        abort_unless($canvas->team_id === Auth::user()->currentTeam->id, 403);
        abort_unless($canvas->isVisibleTo(Auth::user()), 403);
        $canvas->loadMissing('canvasType');
        $this->canvas = $canvas;
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

    // ─── View Mode ──────────────────────────────────────────

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'list' ? 'workshop' : 'list';
    }

    // ─── Workshop CRUD (WorkshopNote-based) ────────────────

    public function addWorkshopNote(array $position = []): void
    {
        $existingCount = $this->canvas->workshopNotes()->count();
        $offset = $existingCount * 25; // stagger stacked notes

        // Use viewport-center position from JS, or default to grid center
        $cols = (int) ($this->canvas->canvasType?->layout['columns'] ?? 3);
        $rows = (int) ($this->canvas->canvasType?->layout['rows'] ?? 3);
        $defaultX = (5000 - max(1200, $cols * 300)) / 2 + 100;
        $defaultY = (3000 - max(800, $rows * 300)) / 2 + 100;

        WorkshopNote::create([
            'canvas_id' => $this->canvas->id,
            'title' => '',
            'content' => '',
            'color' => 'yellow',
            'position_x' => ($position['x'] ?? $defaultX) + $offset,
            'position_y' => ($position['y'] ?? $defaultY) + $offset,
            'width' => 200,
            'height' => 150,
            'created_by_user_id' => Auth::id(),
        ]);
    }

    public function updateNotePosition(int $noteId, array $pos): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        $note->update([
            'position_x' => $pos['x'] ?? $note->position_x,
            'position_y' => $pos['y'] ?? $note->position_y,
            'width' => isset($pos['width']) ? (int) $pos['width'] : $note->width,
            'height' => isset($pos['height']) ? (int) $pos['height'] : $note->height,
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
    }

    public function updateNoteColor(int $noteId, string $color): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        if (!in_array($color, WorkshopNote::allowedColors())) return;

        $note->update(['color' => $color]);
    }

    public function deleteWorkshopNote(int $noteId): void
    {
        $note = WorkshopNote::find($noteId);
        abort_unless($note && $note->canvas_id === $this->canvas->id, 403);

        $note->delete();
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
                'x' => $n->position_x,
                'y' => $n->position_y,
                'width' => $n->width,
                'height' => $n->height,
            ])
            ->values()
            ->toArray();
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
                    'x' => $n->position_x,
                    'y' => $n->position_y,
                    'width' => $n->width,
                    'height' => $n->height,
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
