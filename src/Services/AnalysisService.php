<?php

namespace Platform\Canvas\Services;

use Platform\Canvas\Models\Canvas;

class AnalysisService
{
    public function analyze(Canvas $canvas): array
    {
        $canvas->loadMissing(['canvasType', 'buildingBlocks.entries']);

        $analysisConfig = $canvas->canvasType?->analysis_config;
        $strategy = $analysisConfig['strategy'] ?? null;

        return match ($strategy) {
            'completeness' => $this->analyzeCompleteness($canvas, $analysisConfig),
            'traffic_light' => $this->analyzeTrafficLight($canvas, $analysisConfig),
            default => $this->analyzeBasic($canvas),
        };
    }

    private function analyzeCompleteness(Canvas $canvas, array $config): array
    {
        $blockDefs = $canvas->canvasType->block_definitions ?? [];
        $totalBlocks = count($blockDefs);
        $filledBlocks = 0;
        $totalEntries = 0;
        $blockStats = [];

        foreach ($canvas->buildingBlocks as $block) {
            $entryCount = $block->entries->count();
            $totalEntries += $entryCount;

            if ($entryCount > 0) {
                $filledBlocks++;
            }

            $blockStats[$block->block_key] = [
                'label' => $block->label,
                'entry_count' => $entryCount,
                'is_filled' => $entryCount > 0,
                'guiding_questions' => $block->getGuidingQuestions(),
                'guiding_questions_count' => count($block->getGuidingQuestions()),
            ];
        }

        $completeness = $totalBlocks > 0 ? round(($filledBlocks / $totalBlocks) * 100, 1) : 0;

        $thresholds = $config['thresholds'] ?? ['good' => 80, 'partial' => 50, 'minimal' => 1];
        $health = match (true) {
            $completeness >= $thresholds['good'] => 'good',
            $completeness >= $thresholds['partial'] => 'partial',
            $completeness > 0 => 'minimal',
            default => 'empty',
        };

        // Missing blocks
        $missingBlocks = [];
        foreach ($blockDefs as $def) {
            $key = $def['key'];
            if (!isset($blockStats[$key]) || !$blockStats[$key]['is_filled']) {
                $missingBlocks[] = [
                    'block_key' => $key,
                    'label' => $def['label'],
                    'guiding_questions' => $def['guiding_questions'] ?? [],
                ];
            }
        }

        $recommendations = $this->generateCompletenessRecommendations($blockStats, $missingBlocks);

        return [
            'strategy' => 'completeness',
            'canvas_id' => $canvas->id,
            'canvas_name' => $canvas->name,
            'completeness_percent' => $completeness,
            'health' => $health,
            'filled_blocks' => $filledBlocks,
            'total_blocks' => $totalBlocks,
            'total_entries' => $totalEntries,
            'block_stats' => $blockStats,
            'missing_blocks' => $missingBlocks,
            'recommendations' => $recommendations,
        ];
    }

    private function analyzeTrafficLight(Canvas $canvas, array $config): array
    {
        $blockDefs = $canvas->canvasType->block_definitions ?? [];
        $totalBlocks = count($blockDefs);
        $filledBlocks = 0;
        $totalEntries = 0;
        $blockStats = [];
        $warnings = [];

        $riskCount = 0;
        $overdueCount = 0;
        $riskBlock = $config['risk_block'] ?? 'risks';
        $milestoneBlock = $config['milestone_block'] ?? 'milestones';
        $criticalBlocks = $config['critical_blocks'] ?? [];

        foreach ($canvas->buildingBlocks as $block) {
            $entryCount = $block->entries->count();
            $totalEntries += $entryCount;

            if ($entryCount > 0) {
                $filledBlocks++;
            }

            $blockStats[$block->block_key] = [
                'label' => $block->label,
                'entry_count' => $entryCount,
                'is_filled' => $entryCount > 0,
            ];

            // Count risks
            if ($block->block_key === $riskBlock) {
                $riskCount = $entryCount;
            }

            // Check milestones for overdue
            if ($block->block_key === $milestoneBlock) {
                foreach ($block->entries as $entry) {
                    $meta = $entry->metadata ?? [];
                    if (isset($meta['due_date'])) {
                        try {
                            $dueDate = \Carbon\Carbon::parse($meta['due_date']);
                            if ($dueDate->isPast() && empty($meta['completed'])) {
                                $overdueCount++;
                            }
                        } catch (\Throwable $e) {
                            // Skip invalid dates
                        }
                    }
                }
            }
        }

        $completeness = $totalBlocks > 0 ? round(($filledBlocks / $totalBlocks) * 100, 1) : 0;

        // Build warnings
        if ($riskCount > 5) {
            $warnings[] = "Hohe Anzahl an Risiken ({$riskCount}). Risikominimierung pruefen.";
        }
        if ($overdueCount > 0) {
            $warnings[] = "{$overdueCount} ueberfaellige Meilenstein(e). Zeitplan pruefen.";
        }
        if ($completeness < 50) {
            $warnings[] = 'Canvas ist weniger als 50% ausgefuellt. Weitere Planung erforderlich.';
        }

        // Missing critical blocks
        foreach ($criticalBlocks as $criticalKey) {
            if (!isset($blockStats[$criticalKey]) || !$blockStats[$criticalKey]['is_filled']) {
                $label = $criticalKey;
                foreach ($blockDefs as $def) {
                    if ($def['key'] === $criticalKey) {
                        $label = $def['label'];
                        break;
                    }
                }
                $warnings[] = "Kritischer Block '{$label}' ist leer.";
            }
        }

        $score = $this->calculateTrafficLightScore($completeness, $riskCount, $overdueCount, $blockStats, $criticalBlocks, $config);

        $thresholds = $config['thresholds'] ?? ['green' => 70, 'yellow' => 40];
        $color = match (true) {
            $score >= $thresholds['green'] => 'green',
            $score >= $thresholds['yellow'] => 'yellow',
            default => 'red',
        };

        return [
            'strategy' => 'traffic_light',
            'canvas_id' => $canvas->id,
            'canvas_name' => $canvas->name,
            'color' => $color,
            'score' => $score,
            'completeness_percent' => $completeness,
            'filled_blocks' => $filledBlocks,
            'total_blocks' => $totalBlocks,
            'total_entries' => $totalEntries,
            'risk_count' => $riskCount,
            'overdue_milestones' => $overdueCount,
            'warnings' => $warnings,
            'block_stats' => $blockStats,
        ];
    }

    private function analyzeBasic(Canvas $canvas): array
    {
        $totalBlocks = $canvas->buildingBlocks->count();
        $filledBlocks = 0;
        $totalEntries = 0;

        foreach ($canvas->buildingBlocks as $block) {
            $entryCount = $block->entries->count();
            $totalEntries += $entryCount;
            if ($entryCount > 0) {
                $filledBlocks++;
            }
        }

        $completeness = $totalBlocks > 0 ? round(($filledBlocks / $totalBlocks) * 100, 1) : 0;

        return [
            'strategy' => 'basic',
            'canvas_id' => $canvas->id,
            'canvas_name' => $canvas->name,
            'filled_blocks' => $filledBlocks,
            'total_blocks' => $totalBlocks,
            'total_entries' => $totalEntries,
            'completeness_percent' => $completeness,
        ];
    }

    private function calculateTrafficLightScore(
        float $completeness,
        int $riskCount,
        int $overdueCount,
        array $blockStats,
        array $criticalBlocks,
        array $config
    ): int {
        $weights = $config['weights'] ?? [
            'completeness' => 40,
            'critical_blocks' => 30,
            'risk_assessment' => 15,
            'milestone_health' => 15,
        ];

        $score = 0;

        // Completeness
        $score += (int) ($completeness / 100 * $weights['completeness']);

        // Critical blocks
        $filledCritical = 0;
        foreach ($criticalBlocks as $type) {
            if (isset($blockStats[$type]) && $blockStats[$type]['is_filled']) {
                $filledCritical++;
            }
        }
        $criticalCount = count($criticalBlocks);
        if ($criticalCount > 0) {
            $score += (int) (($filledCritical / $criticalCount) * $weights['critical_blocks']);
        }

        // Risk assessment
        $score += match (true) {
            $riskCount === 0 => (int) ($weights['risk_assessment'] * 0.67),
            $riskCount <= 3 => $weights['risk_assessment'],
            $riskCount <= 5 => (int) ($weights['risk_assessment'] * 0.67),
            default => (int) ($weights['risk_assessment'] * 0.33),
        };

        // Milestone health
        $score += match (true) {
            $overdueCount === 0 => $weights['milestone_health'],
            $overdueCount <= 2 => (int) ($weights['milestone_health'] * 0.53),
            default => 0,
        };

        return min(100, max(0, $score));
    }

    private function generateCompletenessRecommendations(array $blockStats, array $missingBlocks): array
    {
        $recommendations = [];

        if (!empty($missingBlocks)) {
            $labels = array_column($missingBlocks, 'label');
            $recommendations[] = 'Noch nicht ausgefuellt: ' . implode(', ', $labels) . '.';
        }

        foreach ($blockStats as $type => $stats) {
            if ($stats['entry_count'] === 1) {
                $recommendations[] = "'{$stats['label']}' hat nur 1 Eintrag - mehr Details wuerden das Canvas verbessern.";
            }
        }

        return $recommendations;
    }
}
