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
    <div x-show="!collapsed" class="p-3 text-sm italic text-[#1a1a2e] uppercase border-b border-gray-200 mb-2">
        Canvas
    </div>

    {{-- Abschnitt: Allgemein --}}
    <div x-show="!collapsed">
        <div class="px-3 pt-3 pb-1">
            <span class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Allgemein</span>
        </div>
        <div class="px-2 space-y-0.5">
            <a href="{{ route('canvas.dashboard') }}" wire:navigate
               class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-[13px] text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition-colors">
                @svg('heroicon-o-home', 'w-4 h-4')
                <span>Dashboard</span>
            </a>
            <a href="{{ route('canvas.canvases.index') }}" wire:navigate
               class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-[13px] text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition-colors">
                @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                <span>Canvases</span>
            </a>
        </div>
    </div>

    {{-- Neues Canvas --}}
    <div x-show="!collapsed" class="px-2 mt-1">
        <button type="button" wire:click="createCanvas"
                class="flex items-center gap-2 w-full px-2 py-1.5 rounded-lg text-[13px] text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition-colors">
            @svg('heroicon-o-plus-circle', 'w-4 h-4')
            <span>Neues Canvas</span>
        </button>
    </div>

    {{-- Collapsed: Icons-only fuer Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-gray-200">
        <div class="flex flex-col gap-2">
            <a href="{{ route('canvas.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-lg text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition-colors">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('canvas.canvases.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-lg text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition-colors">
                @svg('heroicon-o-squares-2x2', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-gray-200">
        <button type="button" wire:click="createCanvas" class="flex items-center justify-center p-2 rounded-lg text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition-colors">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Canvases (Entity-basierte Gruppierung) --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Entity Type Gruppen (Baum-Darstellung) --}}
            @foreach($entityTypeGroups as $typeGroup)
                <div wire:key="type-group-{{ $typeGroup['type_id'] }}">
                    <div class="px-3 pt-3 pb-1">
                        <span class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $typeGroup['type_name'] }}</span>
                    </div>
                    <div class="px-2 space-y-0.5">
                        @foreach($typeGroup['entities'] as $entityNode)
                            @include('canvas::livewire.partials.sidebar-entity-node', [
                                'node' => $entityNode,
                                'typeIcon' => $typeGroup['type_icon'] ?? null,
                            ])
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Unverknuepfte Canvases --}}
            @if($unlinkedCanvases->isNotEmpty())
                <div>
                    <div class="px-3 pt-3 pb-1">
                        <span class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Unverknuepft</span>
                    </div>
                    <div class="px-2 space-y-0.5">
                        @foreach($unlinkedCanvases as $canvas)
                            <a wire:key="unlinked-canvas-{{ $canvas->id }}"
                               href="{{ route('canvas.canvases.show', ['canvas' => $canvas]) }}"
                               wire:navigate
                               title="{{ $canvas->name }}"
                               class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 rounded-lg text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition-colors truncate">
                                @if($canvas->color)
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $canvas->color }}"></span>
                                @else
                                    <span class="w-1 h-1 rounded-full flex-shrink-0 bg-gray-400 opacity-40"></span>
                                @endif
                                <span class="truncate text-[11px]">{{ $canvas->name }}</span>
                                @if($canvas->canvasType)
                                    <span class="text-[9px] px-1 py-px rounded bg-gray-100 text-gray-400 flex-shrink-0 ml-auto">{{ $canvas->canvasType->name }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Toggle: Alle/Meine Canvases --}}
            @if($hasMoreCanvases)
                <div class="px-3 py-2">
                    <button
                        type="button"
                        wire:click="toggleShowAllCanvases"
                        x-on:click="localStorage.setItem('canvas.showAllCanvases', (!$wire.showAllCanvases).toString())"
                        class="flex items-center gap-2 text-xs text-gray-400 hover:text-[#1a1a2e] transition-colors"
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
                <div class="px-3 py-1 text-xs text-gray-400">
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
