<x-ui-page>
    {{-- Navbar --}}
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Canvas', 'href' => route('canvas.dashboard'), 'icon' => 'squares-2x2'],
            ['label' => 'Canvases', 'href' => route('canvas.canvases.index')],
            ['label' => $canvas->name],
        ]">
            <x-slot name="left">
                <a href="{{ route('canvas.canvases.pdf', $canvas) }}" target="_blank">
                    <x-ui-button variant="ghost" size="sm">
                        @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                        <span>PDF Export</span>
                    </x-ui-button>
                </a>

                @if($canvas->public_token)
                    <div class="d-flex items-center gap-1" x-data="{ copied: false }">
                        <x-ui-button
                            variant="{{ $canvas->is_public ? 'primary' : 'ghost' }}"
                            size="sm"
                            wire:click="togglePublicLink"
                        >
                            @svg('heroicon-o-globe-alt', 'w-4 h-4')
                            <span>{{ $canvas->is_public ? 'Public Link aktiv' : 'Public Link inaktiv' }}</span>
                        </x-ui-button>
                        @if($canvas->is_public)
                            <x-ui-button
                                variant="ghost"
                                size="sm"
                                x-on:click="navigator.clipboard.writeText('{{ $canvas->getPublicUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            >
                                <template x-if="!copied">
                                    @svg('heroicon-o-clipboard', 'w-4 h-4')
                                </template>
                                <template x-if="copied">
                                    @svg('heroicon-o-check', 'w-4 h-4 text-green-500')
                                </template>
                            </x-ui-button>
                        @endif
                    </div>
                @else
                    <x-ui-button variant="ghost" size="sm" wire:click="createPublicLink">
                        @svg('heroicon-o-link', 'w-4 h-4')
                        <span>Public Link erstellen</span>
                    </x-ui-button>
                @endif
            </x-slot>
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container>
        <div class="space-y-4">
            @php
                $hasAreas = !empty($layout['areas'] ?? null) && !empty($layout['area_map'] ?? null);
                $columns = $layout['columns'] ?? 3;
                $rows = $layout['rows'] ?? 3;
            @endphp

            @if($hasAreas)
                {{-- Complex grid with named areas (BMC, Lean Canvas) --}}
                @php
                    $areasRows = array_map('trim', explode('/', $layout['areas']));
                    $cssAreas = collect($areasRows)->map(fn($row) => "'" . $row . "'")->implode(' ');
                    $areaMap = $layout['area_map'];
                @endphp
                {{-- Desktop: area-based grid, Mobile/Tablet: simple flow --}}
                <div class="hidden lg:grid gap-3" style="grid-template-columns: repeat({{ $columns }}, 1fr); grid-template-rows: {{ str_repeat('auto ', $rows) }}; grid-template-areas: {{ $cssAreas }};">
                    @foreach($blockDefs as $def)
                        @php
                            $blockKey = $def['key'];
                            $areaName = $areaMap[$blockKey] ?? null;
                        @endphp
                        @if($areaName)
                        <div style="grid-area: {{ $areaName }};">
                            @include('canvas::livewire.canvas._block', ['blockKey' => $blockKey, 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                        </div>
                        @endif
                    @endforeach
                </div>
                {{-- Mobile/Tablet fallback --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:hidden">
                    @foreach($blockDefs as $def)
                        @include('canvas::livewire.canvas._block', ['blockKey' => $def['key'], 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                    @endforeach
                </div>
            @else
                {{-- Simple grid with responsive breakpoints --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $columns }} gap-3">
                    @foreach($blockDefs as $def)
                        @include('canvas::livewire.canvas._block', ['blockKey' => $def['key'], 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                    @endforeach
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Canvas Info" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Status --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Status</h3>
                    <x-ui-badge :variant="match($canvas->status) { 'active' => 'success', 'archived' => 'secondary', default => 'warning' }">
                        {{ ucfirst($canvas->status) }}
                    </x-ui-badge>
                </div>

                {{-- Ampel (traffic_light strategy) --}}
                @if(($analysisData['strategy'] ?? null) === 'traffic_light')
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Ampel</h3>
                    <div class="d-flex items-center gap-3 p-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                        <span class="inline-block w-6 h-6 rounded-full flex-shrink-0 {{ match($analysisData['color'] ?? 'red') { 'green' => 'bg-green-500', 'yellow' => 'bg-yellow-500', default => 'bg-red-500' } }}"></span>
                        <div>
                            <div class="text-sm font-bold text-[var(--ui-secondary)]">{{ $analysisData['score'] ?? 0 }}%</div>
                            <div class="text-[11px] text-[var(--ui-muted)]">
                                {{ match($analysisData['color'] ?? 'red') { 'green' => 'Auf Kurs', 'yellow' => 'Aufmerksamkeit noetig', default => 'Kritisch' } }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Creator & Date --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Details</h3>
                    <div class="space-y-2 text-xs text-[var(--ui-muted)]">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            {{ $canvas->createdByUser?->name ?? 'Unbekannt' }}
                        </div>
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                            {{ $canvas->created_at?->format('d.m.Y H:i') }}
                        </div>
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-squares-2x2', 'w-3.5 h-3.5')
                            {{ $canvas->canvasType?->name ?? '-' }}
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                @if($canvas->description)
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Beschreibung</h3>
                    <p class="text-xs text-[var(--ui-muted)] leading-relaxed">{{ $canvas->description }}</p>
                </div>
                @endif

                {{-- Completeness --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Fortschritt</h3>
                    <div class="space-y-2">
                        {{-- Progress Bar --}}
                        <div>
                            <div class="d-flex items-center justify-between text-xs mb-1">
                                <span class="text-[var(--ui-muted)]">Vollstaendigkeit</span>
                                <span class="font-semibold text-[var(--ui-secondary)]">{{ $analysisData['completeness_percent'] ?? 0 }}%</span>
                            </div>
                            @php
                                $barColor = match($analysisData['strategy'] ?? 'basic') {
                                    'completeness' => match($analysisData['health'] ?? 'empty') {
                                        'good' => 'bg-green-500',
                                        'partial' => 'bg-yellow-500',
                                        'minimal' => 'bg-orange-500',
                                        default => 'bg-[var(--ui-muted)]',
                                    },
                                    'traffic_light' => match($analysisData['color'] ?? 'red') {
                                        'green' => 'bg-green-500',
                                        'yellow' => 'bg-yellow-500',
                                        default => 'bg-red-500',
                                    },
                                    default => 'bg-blue-500',
                                };
                            @endphp
                            <div class="w-full h-2 rounded-full bg-[var(--ui-muted-5)]">
                                <div class="h-2 rounded-full transition-all {{ $barColor }}"
                                     style="width: {{ $analysisData['completeness_percent'] ?? 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="space-y-1.5">
                            <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                                <span class="text-[11px] text-[var(--ui-muted)]">Bloecke</span>
                                <span class="text-xs font-bold text-[var(--ui-secondary)]">{{ $analysisData['filled_blocks'] ?? 0 }}/{{ $analysisData['total_blocks'] ?? 0 }}</span>
                            </div>
                            <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                                <span class="text-[11px] text-[var(--ui-muted)]">Eintraege</span>
                                <span class="text-xs font-bold text-[var(--ui-secondary)]">{{ $analysisData['total_entries'] ?? 0 }}</span>
                            </div>

                            @if(($analysisData['strategy'] ?? null) === 'completeness')
                            <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                                <span class="text-[11px] text-[var(--ui-muted)]">Health</span>
                                <x-ui-badge :variant="match($analysisData['health'] ?? 'empty') { 'good' => 'success', 'partial' => 'warning', default => 'danger' }" size="sm">
                                    {{ ucfirst($analysisData['health'] ?? 'empty') }}
                                </x-ui-badge>
                            </div>
                            @endif

                            @if(($analysisData['strategy'] ?? null) === 'traffic_light')
                            <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                                <span class="text-[11px] text-[var(--ui-muted)]">Risiken</span>
                                <span class="text-xs font-bold text-[var(--ui-secondary)]">{{ $analysisData['risk_count'] ?? 0 }}</span>
                            </div>
                            @if(($analysisData['overdue_milestones'] ?? 0) > 0)
                            <div class="d-flex items-center justify-between p-2 bg-red-500/10 rounded-md border border-red-500/20">
                                <span class="text-[11px] text-red-600">Ueberfaellig</span>
                                <span class="text-xs font-bold text-red-600">{{ $analysisData['overdue_milestones'] }}</span>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Warnings (traffic_light) --}}
                @if(!empty($analysisData['warnings'] ?? []))
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Warnungen</h3>
                    <div class="space-y-1.5">
                        @foreach($analysisData['warnings'] as $warning)
                        <div class="d-flex items-start gap-2 p-2 rounded-md bg-yellow-500/10 border border-yellow-500/20">
                            @svg('heroicon-o-exclamation-triangle', 'w-3.5 h-3.5 text-yellow-600 mt-0.5 flex-shrink-0')
                            <span class="text-[11px] text-[var(--ui-secondary)]">{{ $warning }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar (completeness recommendations) --}}
    @if(($analysisData['strategy'] ?? null) === 'completeness')
    <x-slot name="activity">
        <x-ui-page-sidebar title="Empfehlungen" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                {{-- Missing Blocks --}}
                @if(!empty($analysisData['missing_blocks'] ?? []))
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Fehlende Bloecke</h3>
                    <div class="space-y-3">
                        @foreach($analysisData['missing_blocks'] as $missing)
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)] mb-1.5">{{ $missing['label'] }}</div>
                            <ul class="space-y-1">
                                @foreach($missing['guiding_questions'] ?? [] as $question)
                                <li class="text-[11px] text-[var(--ui-muted)] d-flex items-start gap-1.5">
                                    @svg('heroicon-o-question-mark-circle', 'w-3 h-3 mt-0.5 flex-shrink-0')
                                    {{ $question }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Recommendations --}}
                @if(!empty($analysisData['recommendations'] ?? []))
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Hinweise</h3>
                    <div class="space-y-2">
                        @foreach($analysisData['recommendations'] as $rec)
                        <div class="d-flex items-start gap-2 p-2 rounded-md bg-yellow-500/10 border border-yellow-500/20">
                            @svg('heroicon-o-light-bulb', 'w-3.5 h-3.5 text-yellow-600 mt-0.5 flex-shrink-0')
                            <span class="text-[11px] text-[var(--ui-secondary)]">{{ $rec }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
    @endif
</x-ui-page>
