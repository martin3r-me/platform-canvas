<?php

namespace Platform\Canvas\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Canvas\Models\Canvas;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;

class CanvasEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['canvas'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'canvas' => ['label' => 'Canvas', 'singular' => 'Canvas', 'icon' => 'squares-2x2', 'route' => null],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        $query->withCount('buildingBlocks');
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [
            'status' => $model->status ?? null,
            'block_count' => (int) ($model->building_blocks_count ?? 0),
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'canvas' => [
                ['field' => 'status', 'format' => 'badge'],
                ['field' => 'block_count', 'format' => 'count', 'suffix' => 'Blocks'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        if ($morphAlias !== 'canvas') {
            return [];
        }

        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        $canvases = Canvas::whereIn('id', $allIds)
            ->withCount('buildingBlocks')
            ->select('id', 'status')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = 0;
            $completed = 0;
            $open = 0;
            $blocksTotal = 0;

            foreach ($ids as $id) {
                $canvas = $canvases[$id] ?? null;
                if (!$canvas) {
                    continue;
                }
                $total++;
                if (in_array($canvas->status, Canvas::DONE_STATUSES)) {
                    $completed++;
                } elseif ($canvas->status === Canvas::STATUS_OPEN) {
                    $open++;
                }
                $blocksTotal += (int) ($canvas->building_blocks_count ?? 0);
            }

            $result[$entityId] = [
                'canvas_total' => $total,
                'canvas_completed' => $completed,
                'canvas_open' => $open,
                'canvas_blocks_total' => $blocksTotal,
            ];
        }

        return $result;
    }

    public function metricDefinitions(): array
    {
        return [
            'canvas_total'        => ['label' => 'Canvas (gesamt)', 'group' => 'canvas', 'direction' => 'neutral', 'unit' => 'count'],
            'canvas_completed'    => ['label' => 'Canvas (abgeschlossen)', 'group' => 'canvas', 'direction' => 'up', 'unit' => 'count', 'pair' => 'canvas_total'],
            'canvas_open'         => ['label' => 'Canvas (offen)', 'group' => 'canvas', 'direction' => 'neutral', 'unit' => 'count'],
            'canvas_blocks_total' => ['label' => 'Bausteine', 'group' => 'canvas', 'direction' => 'neutral', 'unit' => 'count'],
        ];
    }
}
