{{-- Rekursiver Entity-Knoten für Canvas Sidebar-Baum --}}
@props(['node', 'typeIcon' => null])

<div wire:key="entity-{{ $node['entity_id'] }}"
     x-data="{ open: localStorage.getItem('canvas.entity.' + {{ $node['entity_id'] }}) === 'true' }"
     class="flex flex-col">
    {{-- Entity-Zeile --}}
    <button type="button"
            @click="open = !open; localStorage.setItem('canvas.entity.' + {{ $node['entity_id'] }}, open)"
            class="flex items-center gap-1 py-1 px-2 rounded-lg text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition w-full text-left group">
        <span class="w-3 h-3 flex-shrink-0 flex items-center justify-center transition-transform text-gray-400"
              :class="open ? 'rotate-90' : ''">
            @svg('heroicon-o-chevron-right', 'w-2.5 h-2.5')
        </span>
        <span class="truncate text-xs font-medium">{{ $node['entity_name'] }}</span>
        <span class="ml-auto text-[10px] tabular-nums text-gray-400 opacity-60">{{ $node['total_items'] }}</span>
    </button>

    {{-- Aufgeklappter Inhalt --}}
    <div x-show="open" x-collapse class="flex flex-col ml-3 border-l border-gray-200">
        {{-- 1. Eigene Canvases --}}
        @foreach($node['items'] as $canvas)
            <a wire:key="entity-{{ $node['entity_id'] }}-canvas-{{ $canvas->id }}"
               href="{{ route('canvas.canvases.show', ['canvas' => $canvas]) }}"
               wire:navigate
               title="{{ $canvas->name }}"
               class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 rounded-lg text-gray-600 hover:bg-yellow-50 hover:text-[#1a1a2e] transition truncate">
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

        {{-- 2. Kind-Entities nach Typ gruppiert --}}
        @foreach($node['children_by_type'] as $typeGroup)
            <div wire:key="entity-{{ $node['entity_id'] }}-type-{{ $typeGroup['type_id'] }}"
                 x-data="{ groupOpen: localStorage.getItem('canvas.entity.' + {{ $node['entity_id'] }} + '.type.' + {{ $typeGroup['type_id'] }}) !== 'false' }"
                 class="flex flex-col">
                @if($node['children_by_type']->count() > 1 || $node['items']->isNotEmpty())
                    <button type="button"
                            @click="groupOpen = !groupOpen; localStorage.setItem('canvas.entity.' + {{ $node['entity_id'] }} + '.type.' + {{ $typeGroup['type_id'] }}, groupOpen)"
                            class="flex items-center gap-1 mt-1 mb-0.5 pl-2.5 pr-2 w-full text-left group cursor-pointer">
                        <span class="w-2.5 h-2.5 flex-shrink-0 flex items-center justify-center transition-transform text-gray-400 opacity-50"
                              :class="groupOpen ? 'rotate-90' : ''">
                            @svg('heroicon-o-chevron-right', 'w-2 h-2')
                        </span>
                        <span class="text-[9px] uppercase tracking-wider text-gray-400 opacity-60 group-hover:opacity-100 transition-opacity">
                            {{ $typeGroup['type_name'] }}
                        </span>
                    </button>
                @endif
                <div x-show="groupOpen" x-collapse class="flex flex-col">
                    @foreach($typeGroup['children'] as $child)
                        @include('canvas::livewire.partials.sidebar-entity-node', [
                            'node' => $child,
                            'typeIcon' => $typeGroup['type_icon'] ?? $typeIcon,
                        ])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
