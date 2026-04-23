<?php

return [
    'name' => 'Canvas',
    'description' => 'Generic Canvas Module',
    'version' => '1.0.0',

    'routing' => [
        'prefix' => 'canvas',
        'middleware' => ['web', 'auth'],
    ],

    'guard' => 'web',

    'navigation' => [
        'main' => [
            'canvas' => [
                'title' => 'Canvas',
                'icon' => 'heroicon-o-squares-2x2',
                'route' => 'canvas.dashboard',
            ],
        ],
    ],

    'sidebar' => [
        'canvas' => [
            'title' => 'Canvas',
            'icon' => 'heroicon-o-squares-2x2',
            'items' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'route' => 'canvas.dashboard',
                    'icon' => 'heroicon-o-home',
                ],
                'canvases' => [
                    'title' => 'Canvases',
                    'route' => 'canvas.canvases.index',
                    'icon' => 'heroicon-o-squares-2x2',
                ],
            ],
        ],
    ],
    'billables' => [
        [
            'model' => \Platform\Canvas\Models\Canvas::class,
            'type' => 'per_item',
            'label' => 'Canvas',
            'description' => 'Jedes erstellte Canvas verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.005, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
    ],
];
