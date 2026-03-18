<?php

namespace Platform\Canvas\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Models\Canvas;

class CanvasPdfController extends Controller
{
    public function __invoke(Canvas $canvas)
    {
        abort_unless(
            Auth::check() && $canvas->team_id === Auth::user()->currentTeam?->id,
            403,
            'Zugriff verweigert'
        );

        $canvas->load(['canvasType', 'buildingBlocks.entries', 'createdByUser']);

        $canvasData = $canvas->toCanvasArray();
        $layout = $canvas->canvasType?->layout ?? [];
        $blockDefs = $canvas->canvasType?->block_definitions ?? [];
        $fontScale = $this->calculateFontScale($canvasData);

        $html = view('canvas::pdf.canvas', [
            'canvas' => $canvas,
            'canvasData' => $canvasData,
            'layout' => $layout,
            'blockDefs' => $blockDefs,
            'fontScale' => $fontScale,
        ])->render();

        $typeName = $canvas->canvasType?->name ?? 'canvas';
        $filename = str($canvas->name ?: $typeName)
            ->slug('-')
            ->append('.pdf')
            ->toString();

        return Pdf::loadHTML($html)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function calculateFontScale(array $canvasData): string
    {
        $totalChars = 0;
        $totalEntries = 0;

        foreach ($canvasData['blocks'] ?? [] as $block) {
            foreach ($block['entries'] ?? [] as $entry) {
                $totalEntries++;
                $totalChars += mb_strlen($entry['title'] ?? '');
                $totalChars += mb_strlen($entry['content'] ?? '');
            }
        }

        if ($totalChars < 800 && $totalEntries <= 18) {
            return 'lg';
        }
        if ($totalChars < 1800 && $totalEntries <= 36) {
            return 'md';
        }
        if ($totalChars < 3500 && $totalEntries <= 60) {
            return 'sm';
        }

        return 'xs';
    }
}
