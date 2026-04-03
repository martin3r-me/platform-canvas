<?php

namespace Platform\Canvas\Services;

use Illuminate\Support\Collection;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasType;
use Platform\Core\Models\User;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Models\OrganizationEntityLink;

class CanvasSidebarService
{
    /**
     * @return array{entityTypeGroups: Collection, unlinkedCanvases: Collection, hasMoreCanvases: bool}
     */
    public function getCanvasesForUser(User $user, bool $showAll): array
    {
        $teamId = $user->currentTeam->id;

        $myCanvases = Canvas::query()
            ->with(['canvasType', 'contextColors'])
            ->where('team_id', $teamId)
            ->where('created_by_user_id', $user->id)
            ->orderBy('name')
            ->get();

        $allCanvases = Canvas::query()
            ->with(['canvasType', 'contextColors'])
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get();

        $canvasesToShow = $showAll ? $allCanvases : $myCanvases;
        $hasMoreCanvases = $allCanvases->count() > $myCanvases->count();

        $canvasIds = $canvasesToShow->pluck('id')->toArray();
        $contextMorphTypes = ['canvas', Canvas::class];

        $entityCanvasMap = [];
        $linkedCanvasIds = [];

        // OrganizationContext
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

        // OrganizationEntityLink
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

        // Gruppieren: EntityType -> Entity -> Canvases
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

        $unlinkedCanvases = $canvasesToShow->filter(function ($canvas) use ($linkedCanvasIds) {
            return !in_array($canvas->id, $linkedCanvasIds);
        })->values();

        return [
            'entityTypeGroups' => $entityTypeGroups,
            'unlinkedCanvases' => $unlinkedCanvases,
            'hasMoreCanvases' => $hasMoreCanvases,
        ];
    }

    public function createQuickCanvas(User $user): Canvas
    {
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

        return $canvas;
    }
}
