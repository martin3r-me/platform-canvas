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
        <div class="px-4 bg-[var(--ui-surface)]/90 border-b border-[var(--ui-border)]/40 backdrop-blur">
            <div class="flex items-center justify-between gap-4 h-10">
                {{-- Links: Typ-Filter Chips --}}
                <div class="flex items-center gap-1.5 min-w-0 overflow-x-auto">
                    @if($canvasTypes->count() > 1)
                    <button
                        wire:click="setTypeFilter('')"
                        class="px-2.5 py-1 rounded-md text-xs font-medium whitespace-nowrap transition-all {{ $typeFilter === '' ? 'bg-[rgb(var(--ui-primary-rgb))] text-white' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                    >
                        Alle ({{ $totalCount }})
                    </button>
                    @foreach($canvasTypes as $type)
                        @php $count = $typeCounts[$type->key] ?? 0; @endphp
                        @if($count > 0)
                        <button
                            wire:click="setTypeFilter('{{ $type->key }}')"
                            class="px-2.5 py-1 rounded-md text-xs font-medium whitespace-nowrap transition-all {{ $typeFilter === $type->key ? 'bg-[rgb(var(--ui-primary-rgb))] text-white' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                        >
                            {{ $type->name }} ({{ $count }})
                        </button>
                        @endif
                    @endforeach
                    @endif
                </div>

                {{-- Rechts: Suche + Aktiv / Erledigt Tabs --}}
                <div class="flex items-center gap-3 flex-shrink-0">
                    <x-ui-input-text
                        wire:model.live.debounce.300ms="search"
                        placeholder="Suchen..."
                        size="sm"
                        class="w-44"
                    />
                    <div class="flex items-center gap-1">
                        <button
                            wire:click="setView('active')"
                            class="px-3 py-1 rounded-md text-xs font-medium transition-all {{ $view === 'active' ? 'bg-[var(--ui-muted-5)] text-[var(--ui-secondary)]' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                        >
                            Aktiv
                            @if($activeCount > 0)
                            <span class="ml-1 opacity-60">{{ $activeCount }}</span>
                            @endif
                        </button>
                        <button
                            wire:click="setView('done')"
                            class="px-3 py-1 rounded-md text-xs font-medium transition-all {{ $view === 'done' ? 'bg-[var(--ui-muted-5)] text-[var(--ui-secondary)]' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
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
                <x-ui-dashboard-tile
                    title="Gesamt"
                    :count="$stats['total']"
                    subtitle="Canvases"
                    icon="squares-2x2"
                    variant="secondary"
                    size="lg"
                />
                @if($view === 'done')
                    @foreach(\Platform\Canvas\Models\Canvas::DONE_STATUSES as $status)
                    <x-ui-dashboard-tile
                        :title="\Platform\Canvas\Models\Canvas::STATUS_LABELS[$status]"
                        :count="$stats[$status] ?? 0"
                        :icon="str_replace('heroicon-o-', '', \Platform\Canvas\Models\Canvas::STATUS_ICONS[$status])"
                        :variant="\Platform\Canvas\Models\Canvas::STATUS_VARIANTS[$status]"
                        size="lg"
                    />
                    @endforeach
                @endif
            </div>

            @if($view === 'active')
                {{-- Aktiv-Tab: Flat list sorted by updated_at --}}
                @if($activeCanvases->isNotEmpty())
                    <x-ui-panel>
                        <x-ui-table compact="true">
                            <x-ui-table-header>
                                <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true">Typ</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true">Tags</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true">Aktualisiert</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true"></x-ui-table-header-cell>
                            </x-ui-table-header>
                            <x-ui-table-body>
                                @foreach($activeCanvases as $canvas)
                                @php $canvasColor = $canvas->color; @endphp
                                <x-ui-table-row wire:key="canvas-{{ $canvas->id }}" compact="true" clickable="true" :href="route('canvas.canvases.show', $canvas)" wire:navigate>
                                    <x-ui-table-cell compact="true">
                                        <div class="flex items-center gap-2">
                                            @if($canvasColor)
                                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $canvasColor }}"></span>
                                            @endif
                                            @if($canvas->visibility === 'private')
                                            @svg('heroicon-o-lock-closed', 'w-3.5 h-3.5 text-yellow-500 flex-shrink-0')
                                            @endif
                                            <div>
                                                <div class="font-medium text-[var(--ui-secondary)]">{{ $canvas->name }}</div>
                                                @if($canvas->description)
                                                <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($canvas->description, 60) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </x-ui-table-cell>
                                    <x-ui-table-cell compact="true">
                                        <x-ui-badge variant="secondary" size="sm">{{ $canvas->canvasType?->name ?? '-' }}</x-ui-badge>
                                    </x-ui-table-cell>
                                    <x-ui-table-cell compact="true">
                                        @if($canvas->tags->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($canvas->tags->take(3) as $tag)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-[var(--ui-muted-5)] text-[var(--ui-muted)]">{{ $tag->name }}</span>
                                            @endforeach
                                            @if($canvas->tags->count() > 3)
                                            <span class="text-[10px] text-[var(--ui-muted)]">+{{ $canvas->tags->count() - 3 }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </x-ui-table-cell>
                                    <x-ui-table-cell compact="true">
                                        <span class="text-sm text-[var(--ui-muted)]">{{ $canvas->createdByUser?->name ?? '-' }}</span>
                                    </x-ui-table-cell>
                                    <x-ui-table-cell compact="true">
                                        <span class="text-sm text-[var(--ui-muted)]">{{ $canvas->updated_at?->diffForHumans() }}</span>
                                    </x-ui-table-cell>
                                    <x-ui-table-cell compact="true">
                                        <div x-data="{ open: false }" x-on:click.stop x-on:mousedown.stop class="relative">
                                            <button x-on:click="open = !open" class="px-2.5 py-1 rounded-md text-xs font-medium text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-all">
                                                Erledigen
                                                @svg('heroicon-o-chevron-down', 'w-3 h-3 inline ml-0.5')
                                            </button>
                                            <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 top-full mt-1 w-40 rounded-lg border border-[var(--ui-border)] bg-[var(--ui-surface)] shadow-lg z-50">
                                                <button wire:click="updateStatus({{ $canvas->id }}, 'completed')" x-on:click="open = false" class="flex items-center gap-2 w-full px-3 py-2 text-xs text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors rounded-t-lg">
                                                    @svg('heroicon-o-check-circle', 'w-4 h-4 text-green-500')
                                                    Abgeschlossen
                                                </button>
                                                <button wire:click="updateStatus({{ $canvas->id }}, 'discarded')" x-on:click="open = false" class="flex items-center gap-2 w-full px-3 py-2 text-xs text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors rounded-b-lg">
                                                    @svg('heroicon-o-x-circle', 'w-4 h-4 text-[var(--ui-muted)]')
                                                    Verworfen
                                                </button>
                                            </div>
                                        </div>
                                    </x-ui-table-cell>
                                </x-ui-table-row>
                                @endforeach
                            </x-ui-table-body>
                        </x-ui-table>
                    </x-ui-panel>
                @else
                    <x-ui-panel>
                        <div class="p-12 text-center">
                            @svg('heroicon-o-squares-2x2', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine aktiven Canvases</h3>
                            <p class="text-[var(--ui-muted)]">Erstelle dein erstes Canvas per Chat.</p>
                        </div>
                    </x-ui-panel>
                @endif
            @else
                {{-- Erledigt-Tab: Grouped by completed/discarded --}}
                @php $hasAnyCanvas = false; @endphp
                @foreach(\Platform\Canvas\Models\Canvas::DONE_STATUSES as $status)
                    @if($grouped[$status]->isNotEmpty())
                        @php $hasAnyCanvas = true; @endphp
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                @svg(\Platform\Canvas\Models\Canvas::STATUS_ICONS[$status], 'w-5 h-5 text-[var(--ui-muted)]')
                                <h2 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider">
                                    {{ \Platform\Canvas\Models\Canvas::STATUS_LABELS[$status] }}
                                </h2>
                                <span class="text-xs text-[var(--ui-muted)]">({{ $grouped[$status]->count() }})</span>
                            </div>

                            <x-ui-panel>
                                <x-ui-table compact="true">
                                    <x-ui-table-header>
                                        <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                                        <x-ui-table-header-cell compact="true">Typ</x-ui-table-header-cell>
                                        <x-ui-table-header-cell compact="true">Tags</x-ui-table-header-cell>
                                        <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                                        <x-ui-table-header-cell compact="true">Aktualisiert</x-ui-table-header-cell>
                                        <x-ui-table-header-cell compact="true"></x-ui-table-header-cell>
                                    </x-ui-table-header>
                                    <x-ui-table-body>
                                        @foreach($grouped[$status] as $canvas)
                                        @php $canvasColor = $canvas->color; @endphp
                                        <x-ui-table-row wire:key="canvas-{{ $canvas->id }}" compact="true" clickable="true" :href="route('canvas.canvases.show', $canvas)" wire:navigate>
                                            <x-ui-table-cell compact="true">
                                                <div class="flex items-center gap-2">
                                                    @if($canvasColor)
                                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $canvasColor }}"></span>
                                                    @endif
                                                    @if($canvas->visibility === 'private')
                                                    @svg('heroicon-o-lock-closed', 'w-3.5 h-3.5 text-yellow-500 flex-shrink-0')
                                                    @endif
                                                    <div>
                                                        <div class="font-medium text-[var(--ui-secondary)]">{{ $canvas->name }}</div>
                                                        @if($canvas->description)
                                                        <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($canvas->description, 60) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </x-ui-table-cell>
                                            <x-ui-table-cell compact="true">
                                                <x-ui-badge variant="secondary" size="sm">{{ $canvas->canvasType?->name ?? '-' }}</x-ui-badge>
                                            </x-ui-table-cell>
                                            <x-ui-table-cell compact="true">
                                                @if($canvas->tags->isNotEmpty())
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($canvas->tags->take(3) as $tag)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-[var(--ui-muted-5)] text-[var(--ui-muted)]">{{ $tag->name }}</span>
                                                    @endforeach
                                                    @if($canvas->tags->count() > 3)
                                                    <span class="text-[10px] text-[var(--ui-muted)]">+{{ $canvas->tags->count() - 3 }}</span>
                                                    @endif
                                                </div>
                                                @endif
                                            </x-ui-table-cell>
                                            <x-ui-table-cell compact="true">
                                                <span class="text-sm text-[var(--ui-muted)]">{{ $canvas->createdByUser?->name ?? '-' }}</span>
                                            </x-ui-table-cell>
                                            <x-ui-table-cell compact="true">
                                                <span class="text-sm text-[var(--ui-muted)]">{{ $canvas->updated_at?->diffForHumans() }}</span>
                                            </x-ui-table-cell>
                                            <x-ui-table-cell compact="true">
                                                <div x-on:click.stop x-on:mousedown.stop>
                                                    <button wire:click="updateStatus({{ $canvas->id }}, 'open')" class="px-2.5 py-1 rounded-md text-xs font-medium text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-all">
                                                        @svg('heroicon-o-arrow-path', 'w-3.5 h-3.5 inline mr-0.5')
                                                        Wieder oeffnen
                                                    </button>
                                                </div>
                                            </x-ui-table-cell>
                                        </x-ui-table-row>
                                        @endforeach
                                    </x-ui-table-body>
                                </x-ui-table>
                            </x-ui-panel>
                        </div>
                    @endif
                @endforeach

                @if(! $hasAnyCanvas)
                <x-ui-panel>
                    <div class="p-12 text-center">
                        @svg('heroicon-o-check-circle', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Keine erledigten Canvases</h3>
                        <p class="text-[var(--ui-muted)]">Abgeschlossene und verworfene Canvases erscheinen hier.</p>
                    </div>
                </x-ui-panel>
                @endif
            @endif
        </div>
    </x-ui-page-container>

</x-ui-page>
