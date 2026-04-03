<?php

namespace Platform\Canvas\Services;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Models\CanvasSnapshot;

class SnapshotService
{
    public function createSnapshot(Canvas $canvas, int $userId): CanvasSnapshot
    {
        $canvasData = $canvas->toCanvasArray();

        $latestVersion = $canvas->snapshots()->max('version') ?? 0;

        return $canvas->snapshots()->create([
            'version' => $latestVersion + 1,
            'snapshot_data' => $canvasData,
            'created_by_user_id' => $userId,
        ]);
    }

    public function compareSnapshots(CanvasSnapshot $snapshotA, CanvasSnapshot $snapshotB): array
    {
        $dataA = $snapshotA->snapshot_data;
        $dataB = $snapshotB->snapshot_data;

        $diff = [];
        $allBlockKeys = array_unique(array_merge(
            array_keys($dataA['blocks'] ?? []),
            array_keys($dataB['blocks'] ?? [])
        ));

        foreach ($allBlockKeys as $blockKey) {
            $entriesA = collect($dataA['blocks'][$blockKey]['entries'] ?? []);
            $entriesB = collect($dataB['blocks'][$blockKey]['entries'] ?? []);

            $idsA = $entriesA->pluck('uuid')->toArray();
            $idsB = $entriesB->pluck('uuid')->toArray();

            $added = $entriesB->filter(fn ($e) => !in_array($e['uuid'], $idsA))->values()->toArray();
            $removed = $entriesA->filter(fn ($e) => !in_array($e['uuid'], $idsB))->values()->toArray();

            $modified = [];
            foreach ($entriesB as $entryB) {
                $entryA = $entriesA->firstWhere('uuid', $entryB['uuid']);
                if ($entryA && ($entryA['title'] !== $entryB['title'] || $entryA['content'] !== $entryB['content'])) {
                    $modified[] = [
                        'uuid' => $entryB['uuid'],
                        'before' => ['title' => $entryA['title'], 'content' => $entryA['content']],
                        'after' => ['title' => $entryB['title'], 'content' => $entryB['content']],
                    ];
                }
            }

            if (!empty($added) || !empty($removed) || !empty($modified)) {
                $diff[$blockKey] = [
                    'added' => $added,
                    'removed' => $removed,
                    'modified' => $modified,
                ];
            }
        }

        return [
            'snapshot_a' => ['version' => $snapshotA->version, 'created_at' => $snapshotA->created_at?->toISOString()],
            'snapshot_b' => ['version' => $snapshotB->version, 'created_at' => $snapshotB->created_at?->toISOString()],
            'changes' => $diff,
            'has_changes' => !empty($diff),
        ];
    }
}
