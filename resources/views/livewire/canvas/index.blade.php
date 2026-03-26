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
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <x-ui-dashboard-tile
                    title="Gesamt"
                    :count="$stats['total']"
                    subtitle="Canvases"
                    icon="squares-2x2"
                    variant="secondary"
                    size="lg"
                />
                @foreach(\Platform\Canvas\Models\Canvas::STATUSES as $status)
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
            @foreach(\Platform\Canvas\Models\Canvas::STATUSES as $status)
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
                                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                                    <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                                    <x-ui-table-header-cell compact="true">Aktualisiert</x-ui-table-header-cell>
                                </x-ui-table-header>
                                <x-ui-table-body>
                                    @foreach($grouped[$status] as $canvas)
                                    <x-ui-table-row wire:key="canvas-{{ $canvas->id }}" compact="true" clickable="true" :href="route('canvas.canvases.show', $canvas)" wire:navigate>
                                        <x-ui-table-cell compact="true">
                                            <div class="font-medium text-[var(--ui-secondary)]">{{ $canvas->name }}</div>
                                            @if($canvas->description)
                                            <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($canvas->description, 60) }}</div>
                                            @endif
                                        </x-ui-table-cell>
                                        <x-ui-table-cell compact="true">
                                            <x-ui-badge variant="secondary" size="sm">{{ $canvas->canvasType?->name ?? '-' }}</x-ui-badge>
                                        </x-ui-table-cell>
                                        <x-ui-table-cell compact="true">
                                            <div x-on:click.stop x-on:mousedown.stop wire:ignore>
                                                <select
                                                    x-data="{ status: '{{ $canvas->status }}' }"
                                                    x-model="status"
                                                    x-on:change="$wire.updateStatus({{ $canvas->id }}, status)"
                                                    class="text-xs font-medium rounded-lg border border-[var(--ui-border)] bg-[var(--ui-bg)] text-[var(--ui-secondary)] pl-2.5 pr-7 py-1.5 appearance-none cursor-pointer shadow-sm transition-all hover:border-[var(--ui-primary)]/40 focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                                                    style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22%236b7280%22><path fill-rule=%22evenodd%22 d=%22M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z%22 clip-rule=%22evenodd%22/></svg>'); background-position: right 0.4rem center; background-repeat: no-repeat; background-size: 1rem;"
                                                >
                                                    @foreach(\Platform\Canvas\Models\Canvas::STATUSES as $s)
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
                    @svg('heroicon-o-squares-2x2', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Canvases</h3>
                    <p class="text-[var(--ui-muted)]">Erstelle dein erstes Canvas per Chat.</p>
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

                {{-- Type Filter --}}
                @if($canvasTypes->count() > 1)
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Canvas-Typ</h3>
                    <div class="space-y-1">
                        <button wire:click="setTypeFilter('')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $typeFilter === '' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span>Alle Typen</span>
                        </button>
                        @foreach($canvasTypes as $type)
                        <button wire:click="setTypeFilter('{{ $type->key }}')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $typeFilter === $type->key ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span>{{ $type->name }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
