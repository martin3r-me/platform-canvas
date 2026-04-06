<?php

namespace Platform\Canvas\Livewire\Canvas;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\AnalysisService;
use Platform\Canvas\Services\CommentService;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntityLink;

class Show extends Component
{
    public Canvas $canvas;

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
        $layout = $this->canvas->canvasType?->layout ?? [];
        $blockDefs = $this->canvas->canvasType?->block_definitions ?? [];

        $commentService = new CommentService();
        $comments = $commentService->getCommentsForCanvas($this->canvas, $this->filterBlockId);
        $allComments = $this->canvas->comments()->get();

        // Entity-Links laden
        $entityLinks = $this->loadEntityLinks();

        return view('canvas::livewire.canvas.show', [
            'canvasData' => $canvasData,
            'analysisData' => $analysisData,
            'layout' => $layout,
            'blockDefs' => $blockDefs,
            'comments' => $comments,
            'allComments' => $allComments,
            'entityLinks' => $entityLinks,
            'activities' => $this->activities,
        ])->layout('platform::layouts.app');
    }
}
