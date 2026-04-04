{{-- resources/views/livewire/sidebar.blade.php --}}
<div
    x-data="{
        init() {
            const savedState = localStorage.getItem('canvas.showAllCanvases');
            if (savedState !== null) {
                @this.set('showAllCanvases', savedState === 'true');
            }
        }
    }"
>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Canvas
    </div>

    {{-- Abschnitt: Allgemein --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('canvas.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('canvas.canvases.index')">
            @svg('heroicon-o-squares-2x2', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Canvases</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Neues Canvas --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createCanvas">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Neues Canvas</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only fuer Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('canvas.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('canvas.canvases.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-squares-2x2', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createCanvas" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Canvases (Entity-basierte Gruppierung) --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Entity Type Gruppen (Baum-Darstellung) --}}
            @foreach($entityTypeGroups as $typeGroup)
                <x-ui-sidebar-list wire:key="type-group-{{ $typeGroup['type_id'] }}" :label="$typeGroup['type_name']">
                    @foreach($typeGroup['entities'] as $entityNode)
                        @include('canvas::livewire.partials.sidebar-entity-node', [
                            'node' => $entityNode,
                            'typeIcon' => $typeGroup['type_icon'] ?? null,
                        ])
                    @endforeach
                </x-ui-sidebar-list>
            @endforeach

            {{-- Unverknuepfte Canvases --}}
            @if($unlinkedCanvases->isNotEmpty())
                <x-ui-sidebar-list label="Unverknuepft">
                    @foreach($unlinkedCanvases as $canvas)
                        <a wire:key="unlinked-canvas-{{ $canvas->id }}"
                           href="{{ route('canvas.canvases.show', ['canvas' => $canvas]) }}"
                           wire:navigate
                           title="{{ $canvas->name }}"
                           class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition truncate">
                            @if($canvas->color)
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $canvas->color }}"></span>
                            @else
                                <span class="w-1 h-1 rounded-full flex-shrink-0 bg-[var(--ui-muted)] opacity-40"></span>
                            @endif
                            <span class="truncate text-[11px]">{{ $canvas->name }}</span>
                            @if($canvas->canvasType)
                                <span class="text-[9px] px-1 py-px rounded bg-[var(--ui-muted-5)] text-[var(--ui-muted)] flex-shrink-0 ml-auto">{{ $canvas->canvasType->name }}</span>
                            @endif
                        </a>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            {{-- Toggle: Alle/Meine Canvases --}}
            @if($hasMoreCanvases)
                <div class="px-3 py-2">
                    <button
                        type="button"
                        wire:click="toggleShowAllCanvases"
                        x-on:click="localStorage.setItem('canvas.showAllCanvases', (!$wire.showAllCanvases).toString())"
                        class="flex items-center gap-2 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                    >
                        @if($showAllCanvases)
                            @svg('heroicon-o-eye-slash', 'w-4 h-4')
                            <span>Nur meine Canvases</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span>Alle Canvases anzeigen</span>
                        @endif
                    </button>
                </div>
            @endif

            {{-- Leer-State --}}
            @if($entityTypeGroups->isEmpty() && $unlinkedCanvases->isEmpty())
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    @if($showAllCanvases)
                        Keine Canvases
                    @else
                        Keine eigenen Canvases
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
