<?php

namespace Platform\Canvas\Services;

use Illuminate\Support\Collection;
use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasType;
use Platform\Core\Models\User;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Services\EntityDimensionBridge;

class CanvasSidebarService
{
    /**
     * @return array{entityTypeGroups: Collection, unlinkedCanvases: Collection, hasMoreCanvases: bool}
     */
    public function getCanvasesForUser(User $user, bool $showAll): array
    {
        $teamId = $user->currentTeam->id;

        $myCanvases = Canvas::withStale()
            ->with(['canvasType', 'contextColors'])
            ->where('team_id', $teamId)
            ->where('created_by_user_id', $user->id)
            ->orderBy('name')
            ->get();

        $allCanvases = Canvas::withStale()
            ->with(['canvasType', 'contextColors'])
            ->where('team_id', $teamId)
            ->visibleTo($user)
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

        // DimensionLink entity dimension
        $entityLinks = EntityDimensionBridge::linksForLinkables($contextMorphTypes, $canvasIds);

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

        // Aufwärts-Traversierung: Ancestors ins Entity-Set aufnehmen
        $directEntityIds = array_keys($entityCanvasMap);
        if (!empty($directEntityIds)) {
            $directEntities = OrganizationEntity::with(['allParents.type'])
                ->whereIn('id', $directEntityIds)
                ->get()
                ->keyBy('id');

            foreach ($directEntities as $entityId => $entity) {
                $ancestor = $entity->allParents;
                while ($ancestor) {
                    if (!isset($entityCanvasMap[$ancestor->id])) {
                        $entityCanvasMap[$ancestor->id] = [];
                    }
                    $ancestor = $ancestor->allParents;
                }
            }
        }

        // Gruppieren: EntityType → Entity-Baum → Canvases
        $entityTypeGroups = collect();

        $entityIds = array_keys($entityCanvasMap);
        if (!empty($entityIds)) {
            $entities = OrganizationEntity::with('type')
                ->whereIn('id', $entityIds)
                ->get()
                ->keyBy('id');

            $entityChildrenMap = [];
            $rootEntityIds = [];

            foreach ($entities as $entity) {
                $parentId = $entity->parent_entity_id;
                if ($parentId && $entities->has($parentId)) {
                    $entityChildrenMap[$parentId][] = $entity->id;
                } else {
                    $rootEntityIds[] = $entity->id;
                }
            }

            $buildTree = function (int $entityId) use (&$buildTree, $entities, $entityChildrenMap, $entityCanvasMap, $canvasesToShow): ?array {
                $entity = $entities->get($entityId);
                if (!$entity) {
                    return null;
                }

                $childIds = $entityChildrenMap[$entityId] ?? [];
                $childNodes = collect($childIds)
                    ->map(fn ($childId) => $buildTree($childId))
                    ->filter();

                $childrenByType = $childNodes
                    ->groupBy(fn ($child) => $child['type_id'])
                    ->map(function ($group) use ($entities) {
                        $firstChild = $group->first();
                        $typeEntity = $entities->get($firstChild['entity_id']);
                        $type = $typeEntity?->type;

                        return [
                            'type_id' => $firstChild['type_id'],
                            'type_name' => $type?->name ?? 'Sonstige',
                            'type_icon' => $type?->icon ?? null,
                            'sort_order' => $type?->sort_order ?? 999,
                            'children' => $group->sortBy('entity_name')->values(),
                        ];
                    })
                    ->sortBy('sort_order')
                    ->values();

                $items = collect($entityCanvasMap[$entityId] ?? [])
                    ->map(fn ($cid) => $canvasesToShow->firstWhere('id', $cid))
                    ->filter()
                    ->values();

                $totalItems = $items->count();
                foreach ($childNodes as $child) {
                    $totalItems += $child['total_items'];
                }

                if ($totalItems === 0) {
                    return null;
                }

                return [
                    'entity_id' => $entityId,
                    'entity_name' => $entity->name,
                    'type_id' => $entity->type?->id,
                    'items' => $items,
                    'children_by_type' => $childrenByType,
                    'total_items' => $totalItems,
                ];
            };

            $groupedByType = [];
            foreach ($rootEntityIds as $entityId) {
                $entity = $entities->get($entityId);
                if (!$entity || !$entity->type) {
                    continue;
                }

                $tree = $buildTree($entityId);
                if (!$tree) {
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
                $groupedByType[$typeId]['entities'][] = $tree;
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
        $canvas->status = Canvas::STATUS_OPEN;
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
