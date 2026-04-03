<?php

namespace Platform\Canvas;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CanvasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::morphMap([
            'canvas' => \Platform\Canvas\Models\Canvas::class,
        ]);

        // EntityLinkProvider registrieren (loose Kopplung mit Organization-Modul)
        try {
            resolve(\Platform\Organization\Services\EntityLinkRegistry::class)
                ->register(new \Platform\Canvas\Organization\CanvasEntityLinkProvider());
        } catch (\Throwable $e) {
            // Organization-Modul nicht geladen
        }

        // Step 1: Load config
        $this->mergeConfigFrom(__DIR__ . '/../config/canvas.php', 'canvas');

        // Step 2: Register module
        if (
            config()->has('canvas.routing') &&
            config()->has('canvas.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key' => 'canvas',
                'title' => 'Canvas',
                'group' => 'content',
                'routing' => config('canvas.routing'),
                'guard' => config('canvas.guard'),
                'navigation' => config('canvas.navigation'),
                'sidebar' => config('canvas.sidebar'),
            ]);
        }

        // Step 3: Routes (if module registered)
        if (PlatformCore::getModule('canvas')) {
            ModuleRouter::group('canvas', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });

            // Public routes (without auth)
            ModuleRouter::group('canvas', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/public.php');
            }, requireAuth: false);
        }

        // Step 4: Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Step 5: Publish config
        $this->publishes([
            __DIR__ . '/../config/canvas.php' => config_path('canvas.php'),
        ], 'config');

        // Step 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'canvas');
        $this->registerLivewireComponents();

        // Step 7: Tools
        $this->registerTools();
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Canvas\Tools\CanvasOverviewTool());

            // Canvas-Type CRUD
            $registry->register(new \Platform\Canvas\Tools\ListTypesTool());
            $registry->register(new \Platform\Canvas\Tools\GetTypeTool());
            $registry->register(new \Platform\Canvas\Tools\CreateTypeTool());
            $registry->register(new \Platform\Canvas\Tools\UpdateTypeTool());
            $registry->register(new \Platform\Canvas\Tools\DeleteTypeTool());

            // Canvas CRUD
            $registry->register(new \Platform\Canvas\Tools\ListCanvasesTool());
            $registry->register(new \Platform\Canvas\Tools\GetCanvasTool());
            $registry->register(new \Platform\Canvas\Tools\CreateCanvasTool());
            $registry->register(new \Platform\Canvas\Tools\UpdateCanvasTool());
            $registry->register(new \Platform\Canvas\Tools\DeleteCanvasTool());

            // Entry CRUD
            $registry->register(new \Platform\Canvas\Tools\ListEntriesTool());
            $registry->register(new \Platform\Canvas\Tools\CreateEntryTool());
            $registry->register(new \Platform\Canvas\Tools\UpdateEntryTool());
            $registry->register(new \Platform\Canvas\Tools\DeleteEntryTool());
            $registry->register(new \Platform\Canvas\Tools\BulkCreateEntriesTool());
            $registry->register(new \Platform\Canvas\Tools\ReorderEntriesTool());

            // Snapshots
            $registry->register(new \Platform\Canvas\Tools\CreateSnapshotTool());
            $registry->register(new \Platform\Canvas\Tools\ListSnapshotsTool());
            $registry->register(new \Platform\Canvas\Tools\GetSnapshotTool());
            $registry->register(new \Platform\Canvas\Tools\CompareSnapshotsTool());

            // Comments
            $registry->register(new \Platform\Canvas\Tools\ListCommentsTool());

            // Utilities
            $registry->register(new \Platform\Canvas\Tools\AnalyzeTool());
            $registry->register(new \Platform\Canvas\Tools\ExportCanvasTool());
        } catch (\Throwable $e) {
            \Log::warning('Canvas: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Canvas\\Livewire';
        $prefix = 'canvas';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
