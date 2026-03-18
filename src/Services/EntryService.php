<?php

namespace Platform\Canvas\Services;

use Platform\Canvas\Models\BuildingBlock;
use Platform\Canvas\Models\Entry;

class EntryService
{
    public function createEntry(BuildingBlock $block, array $data): Entry
    {
        if (!isset($data['position'])) {
            $data['position'] = ($block->entries()->max('position') ?? 0) + 1;
        }

        return $block->entries()->create($data);
    }

    /**
     * @return array<Entry>
     */
    public function bulkCreateEntries(BuildingBlock $block, array $entriesData, int $userId): array
    {
        $maxPosition = $block->entries()->max('position') ?? 0;
        $created = [];

        foreach ($entriesData as $data) {
            $maxPosition++;
            $created[] = $block->entries()->create([
                'title' => $data['title'],
                'content' => $data['content'] ?? null,
                'entry_type' => $data['entry_type'] ?? 'text',
                'position' => $data['position'] ?? $maxPosition,
                'metadata' => $data['metadata'] ?? null,
                'created_by_user_id' => $userId,
            ]);
        }

        return $created;
    }

    public function reorderEntries(BuildingBlock $block, array $entryIds): void
    {
        foreach ($entryIds as $position => $entryId) {
            $block->entries()
                ->where('id', $entryId)
                ->update(['position' => $position + 1]);
        }
    }
}
