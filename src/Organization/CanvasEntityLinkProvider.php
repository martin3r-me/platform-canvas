<?php

namespace Platform\Canvas\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;

class CanvasEntityLinkProvider implements EntityLinkProvider
{
    public function morphAliases(): array
    {
        return ['canvas'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'canvas' => ['label' => 'Canvas', 'icon' => 'squares-2x2', 'route' => null],
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
}
