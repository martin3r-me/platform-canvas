<?php

namespace Platform\Canvas\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasType;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntityLink;
use Platform\Organization\Models\OrganizationEntity;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllCanvases = false;

    public function mount()
    {
        $this->showAllCanvases = false;
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {

    }

    public function toggleShowAllCanvases()
    {
        $this->showAllCanvases = !$this->showAllCanvases;
    }

    public function createCanvas()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $canvasType = CanvasType::availableForTeam($teamId)->first();

        $canvas = new Canvas();
        $canvas->name = 'Neues Canvas';
        $canvas->team_id = $teamId;
        $canvas->created_by_user_id = $user->id;
        $canvas->status = Canvas::STATUS_BACKLOG;
        if ($canvasType) {
            $canvas->canvas_type_id = $canvasType->id;
        }
        $canvas->save();

        if ($canvasType) {
            $canvas->initializeBlocks();
        }

        return redirect()->route('canvas.canvases.show', ['canvas' => $canvas]);
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('canvas::livewire.sidebar', [
                'entityTypeGroups' => collect(),
                'unlinkedCanvases' => collect(),
                'hasMoreCanvases' => false,
            ]);
        }

        // 1. Canvases laden
        $myCanvases = Canvas::query()
            ->with('canvasType')
            ->where('team_id', $teamId)
            ->where('created_by_user_id', $user->id)
            ->orderBy('name')
            ->get();

        $allCanvases = Canvas::query()
            ->with('canvasType')
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get();

        $canvasesToShow = $this->showAllCanvases
            ? $allCanvases
            : $myCanvases;

        $hasMoreCanvases = $allCanvases->count() > $myCanvases->count();

        // 2. Entity-Verknüpfungen laden aus beiden Quellen
        $canvasIds = $canvasesToShow->pluck('id')->toArray();

        $entityCanvasMap = [];
        $linkedCanvasIds = [];

        // Morph-Varianten für Canvas
        $contextMorphTypes = ['canvas', Canvas::class];

        // a) OrganizationContext
        $contexts = OrganizationContext::query()
            ->whereIn('contextable_type', $contextMorphTypes)
            ->whereIn('contextable_id', $canvasIds)
            ->where('is_active', true)
            ->with(['organizationEntity.type'])
            ->get();

        foreach ($contexts as $ctx) {
            $entityId = $ctx->organization_entity_id;
            $canvasId = $ctx->contextable_id;
            if ($entityId) {
                $entityCanvasMap[$entityId][] = $canvasId;
                $linkedCanvasIds[] = $canvasId;
            }
        }

        // b) OrganizationEntityLink
        $entityLinks = OrganizationEntityLink::query()
            ->whereIn('linkable_type', $contextMorphTypes)
            ->whereIn('linkable_id', $canvasIds)
            ->with(['entity.type'])
            ->get();

        foreach ($entityLinks as $link) {
            $entityId = $link->entity_id;
            $canvasId = $link->linkable_id;
            $entityCanvasMap[$entityId][] = $canvasId;
            $linkedCanvasIds[] = $canvasId;
        }

        // Deduplizieren
        foreach ($entityCanvasMap as $entityId => $cids) {
            $entityCanvasMap[$entityId] = array_unique($cids);
        }
        $linkedCanvasIds = array_unique($linkedCanvasIds);

        // 3. Gruppieren: EntityType → Entity → Canvases
        $entityTypeGroups = collect();

        $entityIds = array_keys($entityCanvasMap);
        if (!empty($entityIds)) {
            $entities = OrganizationEntity::with('type')
                ->whereIn('id', $entityIds)
                ->get()
                ->keyBy('id');

            $groupedByType = [];
            foreach ($entityCanvasMap as $entityId => $canvasIdsList) {
                $entity = $entities->get($entityId);
                if (!$entity || !$entity->type) {
                    continue;
                }
                $typeId = $entity->type->id;
                if (!isset($groupedByType[$typeId])) {
                    $groupedByType[$typeId] = [
                        'type_id' => $typeId,
                        'type_name' => $entity->type->name,
                        'type_icon' => $entity->type->icon,
                        'sort_order' => $entity->type->sort_order ?? 999,
                        'entities' => [],
                    ];
                }
                if (!isset($groupedByType[$typeId]['entities'][$entityId])) {
                    $groupedByType[$typeId]['entities'][$entityId] = [
                        'entity_id' => $entityId,
                        'entity_name' => $entity->name,
                        'canvases' => collect(),
                    ];
                }
                foreach ($canvasIdsList as $cid) {
                    $canvas = $canvasesToShow->firstWhere('id', $cid);
                    if ($canvas) {
                        $groupedByType[$typeId]['entities'][$entityId]['canvases']->push($canvas);
                    }
                }
            }

            $entityTypeGroups = collect($groupedByType)
                ->sortBy('sort_order')
                ->map(function ($group) {
                    $group['entities'] = collect($group['entities'])
                        ->sortBy('entity_name')
                        ->values();
                    return $group;
                })
                ->values();
        }

        // 4. Unverknüpfte Canvases
        $unlinkedCanvases = $canvasesToShow->filter(function ($canvas) use ($linkedCanvasIds) {
            return !in_array($canvas->id, $linkedCanvasIds);
        })->values();

        return view('canvas::livewire.sidebar', [
            'entityTypeGroups' => $entityTypeGroups,
            'unlinkedCanvases' => $unlinkedCanvases,
            'hasMoreCanvases' => $hasMoreCanvases,
        ]);
    }
}
