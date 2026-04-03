<x-ui-page>
    {{-- Navbar --}}
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Canvas', 'href' => route('canvas.dashboard'), 'icon' => 'squares-2x2'],
            ['label' => 'Canvases'],
        ]">
            <x-slot name="below">
                {{-- Typ-Filter Chips --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button
                        wire:click="setTypeFilter('')"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $typeFilter === '' ? 'bg-[rgb(var(--ui-primary-rgb))] text-white shadow-sm' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]/80' }}"
                    >
                        Alle
                        <span class="ml-1 opacity-70">({{ $totalCount }})</span>
                    </button>
                    @foreach($canvasTypes as $type)
                        @php $count = $typeCounts[$type->key] ?? 0; @endphp
                        @if($count > 0)
                        <button
                            wire:click="setTypeFilter('{{ $type->key }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $typeFilter === $type->key ? 'bg-[rgb(var(--ui-primary-rgb))] text-white shadow-sm' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]/80' }}"
                        >
                            {{ $type->name }}
                            <span class="ml-1 opacity-70">({{ $count }})</span>
                        </button>
                        @endif
                    @endforeach
                </div>
            </x-slot>
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Tab-Switcher --}}
            <div class="flex items-center gap-1 border-b border-[var(--ui-border)]">
                <button
                    wire:click="setView('active')"
                    class="px-4 py-2.5 text-sm font-medium transition-colors relative {{ $view === 'active' ? 'text-[var(--ui-primary)]' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Aktiv
                    @if($activeCount > 0)
                    <span class="ml-1.5 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-semibold rounded-full {{ $view === 'active' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)]' : 'bg-[var(--ui-muted)]/10 text-[var(--ui-muted)]' }}">{{ $activeCount }}</span>
                    @endif
                    @if($view === 'active')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--ui-primary)] rounded-t"></span>
                    @endif
                </button>
                <button
                    wire:click="setView('done')"
                    class="px-4 py-2.5 text-sm font-medium transition-colors relative {{ $view === 'done' ? 'text-[var(--ui-primary)]' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Erledigt
                    @if($doneCount > 0)
                    <span class="ml-1.5 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-semibold rounded-full {{ $view === 'done' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)]' : 'bg-[var(--ui-muted)]/10 text-[var(--ui-muted)]' }}">{{ $doneCount }}</span>
                    @endif
                    @if($view === 'done')
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[var(--ui-primary)] rounded-t"></span>
                    @endif
                </button>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-{{ count($visibleStatuses) + 1 }} gap-3">
                <x-ui-dashboard-tile
                    title="Gesamt"
                    :count="$stats['total']"
                    subtitle="Canvases"
                    icon="squares-2x2"
                    variant="secondary"
                    size="lg"
                />
                @foreach($visibleStatuses as $status)
                <x-ui-dashboard-tile
                    :title="\Platform\Canvas\Models\Canvas::STATUS_LABELS[$status]"
                    :count="$stats[$status]"
                    :icon="str_replace('heroicon-o-', '', \Platform\Canvas\Models\Canvas::STATUS_ICONS[$status])"
                    :variant="\Platform\Canvas\Models\Canvas::STATUS_VARIANTS[$status]"
                    size="lg"
                />
                @endforeach
            </div>

            {{-- Status Sections --}}
            @php $hasAnyCanvas = false; @endphp
            @foreach($visibleStatuses as $status)
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
                                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                                    <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                                    <x-ui-table-header-cell compact="true">Aktualisiert</x-ui-table-header-cell>
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
                                            @php $allowed = $canvas->allowedTransitions(); @endphp
                                            <div x-on:click.stop x-on:mousedown.stop wire:ignore>
                                                <select
                                                    x-data="{ status: '{{ $canvas->status }}' }"
                                                    x-model="status"
                                                    x-on:change="$wire.updateStatus({{ $canvas->id }}, status)"
                                                    class="text-xs font-medium rounded-lg border border-[var(--ui-border)] bg-[var(--ui-bg)] text-[var(--ui-secondary)] pl-2.5 pr-7 py-1.5 appearance-none cursor-pointer shadow-sm transition-all hover:border-[var(--ui-primary)]/40 focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                                                    style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22%236b7280%22><path fill-rule=%22evenodd%22 d=%22M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z%22 clip-rule=%22evenodd%22/></svg>'); background-position: right 0.4rem center; background-repeat: no-repeat; background-size: 1rem;"
                                                >
                                                    <option value="{{ $canvas->status }}">{{ \Platform\Canvas\Models\Canvas::STATUS_LABELS[$canvas->status] }}</option>
                                                    @foreach($allowed as $s)
                                                    <option value="{{ $s }}">{{ \Platform\Canvas\Models\Canvas::STATUS_LABELS[$s] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </x-ui-table-cell>
                                        <x-ui-table-cell compact="true">
                                            <span class="text-sm text-[var(--ui-muted)]">{{ $canvas->createdByUser?->name ?? '-' }}</span>
                                        </x-ui-table-cell>
                                        <x-ui-table-cell compact="true">
                                            <span class="text-sm text-[var(--ui-muted)]">{{ $canvas->updated_at?->diffForHumans() }}</span>
                                        </x-ui-table-cell>
                                    </x-ui-table-row>
                                    @endforeach
                                </x-ui-table-body>
                            </x-ui-table>
                        </x-ui-panel>
                    </div>
                @endif
            @endforeach

            {{-- Empty State --}}
            @if(! $hasAnyCanvas)
            <x-ui-panel>
                <div class="p-12 text-center">
                    @if($view === 'active')
                        @svg('heroicon-o-squares-2x2', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine aktiven Canvases</h3>
                        <p class="text-[var(--ui-muted)]">Erstelle dein erstes Canvas per Chat.</p>
                    @else
                        @svg('heroicon-o-check-badge', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Keine erledigten Canvases</h3>
                        <p class="text-[var(--ui-muted)]">Validierte und archivierte Canvases erscheinen hier.</p>
                    @endif
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Search --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Suche</h3>
                    <x-ui-input-text
                        wire:model.live.debounce.300ms="search"
                        placeholder="Canvas suchen..."
                        size="sm"
                    />
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
