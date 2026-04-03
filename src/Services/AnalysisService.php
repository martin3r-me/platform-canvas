<?php

namespace Platform\Canvas\Services;

use Platform\Canvas\Models\Canvas;
use Platform\Canvas\Services\Analysis\AnalysisStrategyInterface;
use Platform\Canvas\Services\Analysis\BasicAnalyzer;
use Platform\Canvas\Services\Analysis\CompletenessAnalyzer;
use Platform\Canvas\Services\Analysis\TrafficLightAnalyzer;

class AnalysisService
{
    public function analyze(Canvas $canvas): array
    {
        $canvas->loadMissing(['canvasType', 'buildingBlocks.entries']);

        $analysisConfig = $canvas->canvasType?->analysis_config ?? [];
        $strategy = $analysisConfig['strategy'] ?? null;

        $analyzer = $this->resolveAnalyzer($strategy);

        return $analyzer->analyze($canvas, $analysisConfig);
    }

    protected function resolveAnalyzer(?string $strategy): AnalysisStrategyInterface
    {
        return match ($strategy) {
            'completeness' => new CompletenessAnalyzer(),
            'traffic_light' => new TrafficLightAnalyzer(),
            default => new BasicAnalyzer(),
        };
    }
}
