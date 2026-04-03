<?php

namespace Platform\Canvas\Services;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasSnapshot;

class CanvasService
{
    public function createCanvas(array $data): Canvas
    {
        $canvas = Canvas::create($data);
        $canvas->initializeBlocks();

        return $canvas->load('buildingBlocks');
    }

    public function createSnapshot(Canvas $canvas, int $userId): CanvasSnapshot
    {
        return (new SnapshotService())->createSnapshot($canvas, $userId);
    }

    public function compareSnapshots(CanvasSnapshot $snapshotA, CanvasSnapshot $snapshotB): array
    {
        return (new SnapshotService())->compareSnapshots($snapshotA, $snapshotB);
    }

    public function exportCanvas(Canvas $canvas): array
    {
        $canvas->loadMissing('canvasType');
        $canvasData = $canvas->toCanvasArray();
        $blockDefs = $canvas->canvasType->block_definitions ?? [];

        $sections = [];
        foreach ($blockDefs as $definition) {
            $key = $definition['key'];
            $block = $canvasData['blocks'][$key] ?? null;
            $sections[] = [
                'block_key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'] ?? '',
                'entries' => $block ? $block['entries'] : [],
                'entry_count' => $block ? count($block['entries']) : 0,
            ];
        }

        return [
            'canvas' => $canvasData['canvas'],
            'sections' => $sections,
            'summary' => [
                'total_entries' => array_sum(array_column($sections, 'entry_count')),
                'filled_blocks' => count(array_filter($sections, fn ($s) => $s['entry_count'] > 0)),
                'total_blocks' => count($sections),
            ],
        ];
    }
}
