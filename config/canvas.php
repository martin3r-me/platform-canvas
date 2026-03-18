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
];
