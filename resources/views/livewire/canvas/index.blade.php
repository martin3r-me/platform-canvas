<x-ui-page>
    {{-- Navbar --}}
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Canvas', 'href' => route('canvas.dashboard'), 'icon' => 'squares-2x2'],
            ['label' => 'Canvases'],
        ]" />

        {{-- Typ-Filter + Tabs --}}
        <div class="px-4 bg-white/90 border-b border-gray-200/40 backdrop-blur">
            <div class="flex items-center justify-between gap-4 h-10">
                {{-- Links: Typ-Filter Chips --}}
                <div class="flex items-center gap-1.5 min-w-0 overflow-x-auto">
                    @if($canvasTypes->count() > 1)
                    <button
                        wire:click="setTypeFilter('')"
                        class="px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-all {{ $typeFilter === '' ? 'bg-[#f2ca52] text-[#1a1a2e] shadow-sm' : 'text-gray-400 hover:text-[#1a1a2e]' }}"
                    >
                        Alle ({{ $totalCount }})
                    </button>
                    @foreach($canvasTypes as $type)
                        @php $count = $typeCounts[$type->key] ?? 0; @endphp
                        @if($count > 0)
                        <button
                            wire:click="setTypeFilter('{{ $type->key }}')"
                            class="px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-all {{ $typeFilter === $type->key ? 'bg-[#f2ca52] text-[#1a1a2e] shadow-sm' : 'text-gray-400 hover:text-[#1a1a2e]' }}"
                        >
                            {{ $type->name }} ({{ $count }})
                        </button>
                        @endif
                    @endforeach
                    @endif
                </div>

                {{-- Rechts: Suche + Aktiv / Erledigt Tabs --}}
                <div class="flex items-center gap-3 flex-shrink-0">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Suchen..."
                        class="w-44 px-3 py-1.5 text-[13px] rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#f2ca52]/30 focus:border-[#f2ca52] transition-colors"
                    />
                    <div class="flex items-center gap-1">
                        <button
                            wire:click="setView('active')"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-all {{ $view === 'active' ? 'bg-[#f2ca52]/20 text-[#1a1a2e] font-semibold' : 'text-gray-400 hover:text-[#1a1a2e]' }}"
                        >
                            Aktiv
                            @if($activeCount > 0)
                            <span class="ml-1 opacity-60">{{ $activeCount }}</span>
                            @endif
                        </button>
                        <button
                            wire:click="setView('done')"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-all {{ $view === 'done' ? 'bg-[#f2ca52]/20 text-[#1a1a2e] font-semibold' : 'text-gray-400 hover:text-[#1a1a2e]' }}"
                        >
                            Erledigt
                            @if($doneCount > 0)
                            <span class="ml-1 opacity-60">{{ $doneCount }}</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ count($stats) }} gap-3">
                {{-- Gesamt --}}
                <div class="p-5 rounded-2xl border border-gray-200 bg-white hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">Gesamt</span>
                        <div class="w-9 h-9 rounded-xl bg-yellow-50 flex items-center justify-center">
                            @svg('heroicon-o-squares-2x2', 'w-5 h-5 text-[#f2ca52]')
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-[#1a1a2e]">{{ $stats['total'] }}</div>
                    <div class="text-xs text-gray-400 mt-1">Canvases</div>
                </div>
                @if($view === 'done')
                    @foreach(\Platform\Canvas\Models\Canvas::DONE_STATUSES as $status)
                    @php
                        $statusColors = [
                            'completed' => ['bg' => 'bg-green-50', 'text' => 'text-green-600'],
                            'discarded' => ['bg' => 'bg-gray-50', 'text' => 'text-gray-400'],
                        ];
                        $sc = $statusColors[$status] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-400'];
                    @endphp
                    <div class="p-5 rounded-2xl border border-gray-200 bg-white hover:shadow-md hover:-translate-y-0.5 transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">{{ \Platform\Canvas\Models\Canvas::STATUS_LABELS[$status] }}</span>
                            <div class="w-9 h-9 rounded-xl {{ $sc['bg'] }} flex items-center justify-center">
                                @svg(str_replace('heroicon-o-', '', \Platform\Canvas\Models\Canvas::STATUS_ICONS[$status]) ? \Platform\Canvas\Models\Canvas::STATUS_ICONS[$status] : 'heroicon-o-check-circle', 'w-5 h-5 ' . $sc['text'])
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-[#1a1a2e]">{{ $stats[$status] ?? 0 }}</div>
                    </div>
                    @endforeach
                @endif
            </div>

            @if($view === 'active')
                {{-- Aktiv-Tab: Flat list sorted by updated_at --}}
                @if($activeCanvases->isNotEmpty())
                    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Name</th>
                                    <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Typ</th>
                                    <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Tags</th>
                                    <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Elemente</th>
                                    <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Erstellt von</th>
                                    <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Aktualisiert</th>
                                    <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($activeCanvases as $canvas)
                                @php $canvasColor = $canvas->color; @endphp
                                <tr wire:key="canvas-{{ $canvas->id }}" class="hover:bg-yellow-50/50 transition-colors cursor-pointer" onclick="window.Livewire.navigate('{{ route('canvas.canvases.show', $canvas) }}')">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            @if($canvasColor)
                                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $canvasColor }}"></span>
                                            @endif
                                            @if($canvas->visibility === 'private')
                                            @svg('heroicon-o-lock-closed', 'w-3.5 h-3.5 text-yellow-500 flex-shrink-0')
                                            @endif
                                            <div>
                                                <div class="text-[13px] font-medium text-[#1a1a2e]">{{ $canvas->name }}</div>
                                                @if($canvas->description)
                                                <div class="text-xs text-gray-400 truncate max-w-xs mt-0.5">{{ Str::limit($canvas->description, 60) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600">{{ $canvas->canvasType?->name ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if($canvas->tags->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($canvas->tags->take(3) as $tag)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">{{ $tag->name }}</span>
                                            @endforeach
                                            @if($canvas->tags->count() > 3)
                                            <span class="text-[10px] text-gray-400">+{{ $canvas->tags->count() - 3 }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @include('canvas::livewire.canvas._workshop-icons', ['canvas' => $canvas])
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="text-[13px] text-gray-400">{{ $canvas->createdByUser?->name ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="text-[13px] text-gray-400">{{ $canvas->updated_at?->diffForHumans() }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div x-data="{ open: false }" x-on:click.stop x-on:mousedown.stop class="relative">
                                            <button x-on:click="open = !open" class="px-2.5 py-1 rounded-full text-xs font-medium text-gray-400 hover:text-[#1a1a2e] hover:bg-yellow-50 transition-all">
                                                Erledigen
                                                @svg('heroicon-o-chevron-down', 'w-3 h-3 inline ml-0.5')
                                            </button>
                                            <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 top-full mt-1 w-40 rounded-xl border border-gray-200 bg-white shadow-lg z-50">
                                                <button wire:click="updateStatus({{ $canvas->id }}, 'completed')" x-on:click="open = false" class="flex items-center gap-2 w-full px-3 py-2 text-xs text-[#1a1a2e] hover:bg-yellow-50 transition-colors rounded-t-xl">
                                                    @svg('heroicon-o-check-circle', 'w-4 h-4 text-green-500')
                                                    Abgeschlossen
                                                </button>
                                                <button wire:click="updateStatus({{ $canvas->id }}, 'discarded')" x-on:click="open = false" class="flex items-center gap-2 w-full px-3 py-2 text-xs text-[#1a1a2e] hover:bg-yellow-50 transition-colors rounded-b-xl">
                                                    @svg('heroicon-o-x-circle', 'w-4 h-4 text-gray-400')
                                                    Verworfen
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @else
                    <section class="bg-white rounded-2xl border border-gray-200 shadow-sm">
                        <div class="p-12 text-center">
                            @svg('heroicon-o-squares-2x2', 'w-16 h-16 text-gray-300 mx-auto mb-4')
                            <h3 class="text-lg font-semibold text-[#1a1a2e] mb-2">Noch keine aktiven Canvases</h3>
                            <p class="text-gray-400">Erstelle dein erstes Canvas per Chat.</p>
                        </div>
                    </section>
                @endif
            @else
                {{-- Erledigt-Tab: Grouped by completed/discarded --}}
                @php $hasAnyCanvas = false; @endphp
                @foreach(\Platform\Canvas\Models\Canvas::DONE_STATUSES as $status)
                    @if($grouped[$status]->isNotEmpty())
                        @php $hasAnyCanvas = true; @endphp
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                @svg(\Platform\Canvas\Models\Canvas::STATUS_ICONS[$status], 'w-5 h-5 text-gray-400')
                                <h2 class="text-sm font-bold text-[#1a1a2e] uppercase tracking-wider">
                                    {{ \Platform\Canvas\Models\Canvas::STATUS_LABELS[$status] }}
                                </h2>
                                <span class="text-xs text-gray-400">({{ $grouped[$status]->count() }})</span>
                            </div>

                            <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-gray-100">
                                            <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Name</th>
                                            <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Typ</th>
                                            <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Tags</th>
                                            <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Elemente</th>
                                            <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Erstellt von</th>
                                            <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide">Aktualisiert</th>
                                            <th class="px-4 py-2.5 text-[11px] font-medium text-gray-400 uppercase tracking-wide"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($grouped[$status] as $canvas)
                                        @php $canvasColor = $canvas->color; @endphp
                                        <tr wire:key="canvas-{{ $canvas->id }}" class="hover:bg-yellow-50/50 transition-colors cursor-pointer" onclick="window.Livewire.navigate('{{ route('canvas.canvases.show', $canvas) }}')">
                                            <td class="px-4 py-2.5">
                                                <div class="flex items-center gap-2">
                                                    @if($canvasColor)
                                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $canvasColor }}"></span>
                                                    @endif
                                                    @if($canvas->visibility === 'private')
                                                    @svg('heroicon-o-lock-closed', 'w-3.5 h-3.5 text-yellow-500 flex-shrink-0')
                                                    @endif
                                                    <div>
                                                        <div class="text-[13px] font-medium text-[#1a1a2e]">{{ $canvas->name }}</div>
                                                        @if($canvas->description)
                                                        <div class="text-xs text-gray-400 truncate max-w-xs mt-0.5">{{ Str::limit($canvas->description, 60) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600">{{ $canvas->canvasType?->name ?? '-' }}</span>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @if($canvas->tags->isNotEmpty())
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($canvas->tags->take(3) as $tag)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-500">{{ $tag->name }}</span>
                                                    @endforeach
                                                    @if($canvas->tags->count() > 3)
                                                    <span class="text-[10px] text-gray-400">+{{ $canvas->tags->count() - 3 }}</span>
                                                    @endif
                                                </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @include('canvas::livewire.canvas._workshop-icons', ['canvas' => $canvas])
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="text-[13px] text-gray-400">{{ $canvas->createdByUser?->name ?? '-' }}</span>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="text-[13px] text-gray-400">{{ $canvas->updated_at?->diffForHumans() }}</span>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <div x-on:click.stop x-on:mousedown.stop>
                                                    <button wire:click="updateStatus({{ $canvas->id }}, 'open')" class="px-2.5 py-1 rounded-full text-xs font-medium text-gray-400 hover:text-[#1a1a2e] hover:bg-yellow-50 transition-all">
                                                        @svg('heroicon-o-arrow-path', 'w-3.5 h-3.5 inline mr-0.5')
                                                        Wieder oeffnen
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </section>
                        </div>
                    @endif
                @endforeach

                @if(! $hasAnyCanvas)
                <section class="bg-white rounded-2xl border border-gray-200 shadow-sm">
                    <div class="p-12 text-center">
                        @svg('heroicon-o-check-circle', 'w-16 h-16 text-gray-300 mx-auto mb-4')
                        <h3 class="text-lg font-semibold text-[#1a1a2e] mb-2">Keine erledigten Canvases</h3>
                        <p class="text-gray-400">Abgeschlossene und verworfene Canvases erscheinen hier.</p>
                    </div>
                </section>
                @endif
            @endif
        </div>
    </x-ui-page-container>

</x-ui-page>
