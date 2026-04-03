<?php

namespace Platform\Canvas\Services\Analysis;

use Platform\Canvas\Models\Canvas;

interface AnalysisStrategyInterface
{
    public function analyze(Canvas $canvas, array $config = []): array;
}
