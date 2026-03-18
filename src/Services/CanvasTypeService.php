<?php

namespace Platform\Canvas\Services;

use Illuminate\Support\Collection;
use Platform\Canvas\Models\CanvasType;

class CanvasTypeService
{
    public function create(array $data): CanvasType
    {
        $this->validateBlockDefinitions($data['block_definitions'] ?? []);
        $this->validateLayout($data['layout'] ?? []);

        return CanvasType::create($data);
    }

    public function update(CanvasType $type, array $data): CanvasType
    {
        if (isset($data['block_definitions'])) {
            $this->validateBlockDefinitions($data['block_definitions']);
        }
        if (isset($data['layout'])) {
            $this->validateLayout($data['layout']);
        }

        $type->update($data);

        return $type->fresh();
    }

    public function availableForTeam(int $teamId): Collection
    {
        return CanvasType::availableForTeam($teamId)
            ->orderBy('origin', 'desc') // system first
            ->orderBy('name')
            ->get();
    }

    public function resolveByKey(string $key, ?int $teamId): ?CanvasType
    {
        // Team-specific first
        if ($teamId) {
            $teamType = CanvasType::where('key', $key)
                ->where('team_id', $teamId)
                ->where('is_active', true)
                ->first();

            if ($teamType) {
                return $teamType;
            }
        }

        // Fall back to system type
        return CanvasType::where('key', $key)
            ->whereNull('team_id')
            ->where('is_active', true)
            ->first();
    }

    public function validateBlockDefinitions(array $defs): bool
    {
        if (empty($defs)) {
            throw new \InvalidArgumentException('block_definitions darf nicht leer sein.');
        }

        $keys = [];
        foreach ($defs as $i => $def) {
            if (empty($def['key'])) {
                throw new \InvalidArgumentException("block_definitions[{$i}].key ist erforderlich.");
            }
            if (empty($def['label'])) {
                throw new \InvalidArgumentException("block_definitions[{$i}].label ist erforderlich.");
            }
            if (in_array($def['key'], $keys, true)) {
                throw new \InvalidArgumentException("Doppelter Block-Key: {$def['key']}");
            }
            $keys[] = $def['key'];
        }

        return true;
    }

    public function validateLayout(array $layout): bool
    {
        if (empty($layout['type'])) {
            throw new \InvalidArgumentException('layout.type ist erforderlich.');
        }
        if (!isset($layout['columns']) || $layout['columns'] < 1) {
            throw new \InvalidArgumentException('layout.columns muss >= 1 sein.');
        }
        if (!isset($layout['rows']) || $layout['rows'] < 1) {
            throw new \InvalidArgumentException('layout.rows muss >= 1 sein.');
        }

        return true;
    }
}
