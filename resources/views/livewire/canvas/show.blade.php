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
                @php $showColor = $canvas->color; @endphp
                @if($showColor)
                <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $showColor }}"></span>
                @endif
                <x-ui-badge variant="primary" size="sm">
                    {{ $canvas->canvasType?->name ?? 'Canvas' }}
                </x-ui-badge>
                @if($canvas->tags->isNotEmpty())
                    @foreach($canvas->tags as $tag)
                    <x-ui-badge variant="secondary" size="sm">{{ $tag->name }}</x-ui-badge>
                    @endforeach
                @endif
                @if(!empty($entityLinks))
                    @foreach($entityLinks as $entityLink)
                    <x-ui-badge variant="info" size="sm">
                        @if($entityLink['icon'] && str_starts_with($entityLink['icon'], 'heroicon-'))
                            @svg($entityLink['icon'], 'w-3 h-3')
                        @else
                            @svg('heroicon-o-building-office', 'w-3 h-3')
                        @endif
                        {{ $entityLink['name'] }}
                    </x-ui-badge>
                    @endforeach
                @endif
            </x-slot>

            {{-- Rechts: Actions --}}
            <a href="{{ route('canvas.canvases.pdf', $canvas) }}" target="_blank">
                <x-ui-button variant="ghost" size="sm">
                    @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                    <span>PDF</span>
                </x-ui-button>
            </a>

            @if($canvas->public_token)
                <div class="flex items-center gap-1" x-data="{ copied: false }">
                    <x-ui-button
                        variant="{{ $canvas->is_public ? 'primary' : 'ghost' }}"
                        size="sm"
                        wire:click="togglePublicLink"
                    >
                        @svg('heroicon-o-globe-alt', 'w-4 h-4')
                        <span>{{ $canvas->is_public ? 'Public' : 'Privat' }}</span>
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
                    <span>Teilen</span>
                </x-ui-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container padding="p-0" spacing="" background="">
        {{-- Meta-Infos --}}
        <div class="px-4 sm:px-6 py-4 border-b border-[var(--ui-border)]/40 bg-[var(--ui-surface)]/50">
            <div class="flex flex-wrap items-start gap-6">
                {{-- Status --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-[var(--ui-muted)]">Status</span>
                    <x-ui-badge :variant="\Platform\Canvas\Models\Canvas::STATUS_VARIANTS[$canvas->status] ?? 'secondary'">
                        {{ \Platform\Canvas\Models\Canvas::STATUS_LABELS[$canvas->status] ?? $canvas->status }}
                    </x-ui-badge>
                </div>

                {{-- Ampel (traffic_light strategy) --}}
                @if(($analysisData['strategy'] ?? null) === 'traffic_light')
                <div class="flex items-center gap-2">
                    <span class="inline-block w-4 h-4 rounded-full flex-shrink-0 {{ match($analysisData['color'] ?? 'red') { 'green' => 'bg-green-500', 'yellow' => 'bg-yellow-500', default => 'bg-red-500' } }}"></span>
                    <span class="text-xs font-semibold text-[var(--ui-secondary)]">{{ $analysisData['score'] ?? 0 }}%</span>
                    <span class="text-xs text-[var(--ui-muted)]">
                        {{ match($analysisData['color'] ?? 'red') { 'green' => 'Auf Kurs', 'yellow' => 'Aufmerksamkeit noetig', default => 'Kritisch' } }}
                    </span>
                </div>
                @endif

                {{-- Creator --}}
                <div class="flex items-center gap-1.5 text-xs text-[var(--ui-muted)]">
                    @svg('heroicon-o-user', 'w-3.5 h-3.5')
                    {{ $canvas->createdByUser?->name ?? 'Unbekannt' }}
                </div>

                {{-- Date --}}
                <div class="flex items-center gap-1.5 text-xs text-[var(--ui-muted)]">
                    @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                    {{ $canvas->created_at?->format('d.m.Y H:i') }}
                </div>

                {{-- Completeness --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[var(--ui-muted)]">Fortschritt</span>
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
                    <div class="w-24 h-2 rounded-full bg-[var(--ui-muted-5)]">
                        <div class="h-2 rounded-full transition-all {{ $barColor }}"
                             style="width: {{ $analysisData['completeness_percent'] ?? 0 }}%"></div>
                    </div>
                    <span class="text-xs font-semibold text-[var(--ui-secondary)]">{{ $analysisData['completeness_percent'] ?? 0 }}%</span>
                </div>

                {{-- Stats --}}
                <div class="flex items-center gap-3 text-xs text-[var(--ui-muted)]">
                    <span>{{ $analysisData['filled_blocks'] ?? 0 }}/{{ $analysisData['total_blocks'] ?? 0 }} Bloecke</span>
                    <span>{{ $analysisData['total_entries'] ?? 0 }} Eintraege</span>
                    @if(($analysisData['strategy'] ?? null) === 'completeness')
                        <x-ui-badge :variant="match($analysisData['health'] ?? 'empty') { 'good' => 'success', 'partial' => 'warning', default => 'danger' }" size="sm">
                            {{ ucfirst($analysisData['health'] ?? 'empty') }}
                        </x-ui-badge>
                    @endif
                    @if(($analysisData['strategy'] ?? null) === 'traffic_light')
                        <span>{{ $analysisData['risk_count'] ?? 0 }} Risiken</span>
                        @if(($analysisData['overdue_milestones'] ?? 0) > 0)
                            <span class="text-red-600 font-medium">{{ $analysisData['overdue_milestones'] }} Ueberfaellig</span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Description --}}
            @if($canvas->description)
            <p class="mt-2 text-xs text-[var(--ui-muted)] leading-relaxed">{{ $canvas->description }}</p>
            @endif

            {{-- Warnings (traffic_light) --}}
            @if(!empty($analysisData['warnings'] ?? []))
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($analysisData['warnings'] as $warning)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-yellow-500/10 border border-yellow-500/20">
                    @svg('heroicon-o-exclamation-triangle', 'w-3.5 h-3.5 text-yellow-600 flex-shrink-0')
                    <span class="text-[11px] text-[var(--ui-secondary)]">{{ $warning }}</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Recommendations (completeness) --}}
            @if(($analysisData['strategy'] ?? null) === 'completeness' && !empty($analysisData['missing_blocks'] ?? []))
            <div class="mt-3">
                <details class="group">
                    <summary class="text-xs font-medium text-[var(--ui-muted)] cursor-pointer hover:text-[var(--ui-secondary)]">
                        {{ count($analysisData['missing_blocks']) }} fehlende Bloecke anzeigen
                    </summary>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($analysisData['missing_blocks'] as $missing)
                        <div class="px-2.5 py-1.5 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)]">{{ $missing['label'] }}</div>
                            @foreach($missing['guiding_questions'] ?? [] as $question)
                            <div class="text-[11px] text-[var(--ui-muted)] flex items-start gap-1 mt-0.5">
                                @svg('heroicon-o-question-mark-circle', 'w-3 h-3 mt-0.5 flex-shrink-0')
                                {{ $question }}
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                </details>
            </div>
            @endif

            @if(($analysisData['strategy'] ?? null) === 'completeness' && !empty($analysisData['recommendations'] ?? []))
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($analysisData['recommendations'] as $rec)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-yellow-500/10 border border-yellow-500/20">
                    @svg('heroicon-o-light-bulb', 'w-3.5 h-3.5 text-yellow-600 flex-shrink-0')
                    <span class="text-[11px] text-[var(--ui-secondary)]">{{ $rec }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div x-data="blockNav()" x-init="init()">
            {{-- Block Navigation --}}
            <div class="sticky top-0 z-20 border-b border-[var(--ui-border)]/40 bg-[var(--ui-surface)]/95 backdrop-blur-sm">
                <div class="px-4 sm:px-6 overflow-x-auto">
                    <div class="flex items-center gap-1 py-2">
                        <span class="shrink-0 text-[11px] font-bold text-[rgb(var(--ui-primary-rgb))] uppercase tracking-wider mr-2">{{ $canvas->canvasType?->name ?? 'Canvas' }}</span>
                        <span class="shrink-0 w-px h-4 bg-[var(--ui-border)]/40 mr-1"></span>
                        @foreach($blockDefs as $def)
                            @php
                                $blockKey = $def['key'];
                                $config = collect($blockDefs)->firstWhere('key', $blockKey) ?? [];
                                $label = $config['label'] ?? ucfirst(str_replace('_', ' ', $blockKey));
                            @endphp
                            <button
                                x-on:click="scrollTo('block-{{ $blockKey }}')"
                                :class="activeBlock === 'block-{{ $blockKey }}' ? 'bg-[rgb(var(--ui-primary-rgb))] text-white shadow-sm' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]'"
                                class="shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-medium transition-all whitespace-nowrap"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Blocks --}}
            <div class="p-4 sm:p-6 space-y-6">
                @foreach($blockDefs as $def)
                    <div id="block-{{ $def['key'] }}" class="scroll-mt-14" data-block>
                        @include('canvas::livewire.canvas._block', ['blockKey' => $def['key'], 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                    </div>
                @endforeach
                <div class="h-[60vh]"></div>
            </div>
        </div>

        <script>
        function blockNav() {
            return {
                activeBlock: '',
                observer: null,
                init() {
                    this.$nextTick(() => {
                        const scrollArea = this.$el.closest('.overflow-y-auto');
                        const blocks = this.$el.querySelectorAll('[data-block]');
                        if (!blocks.length) return;
                        this.activeBlock = blocks[0]?.id || '';
                        this.observer = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    this.activeBlock = entry.target.id;
                                }
                            });
                        }, { root: scrollArea, rootMargin: '-10% 0px -70% 0px', threshold: 0 });
                        blocks.forEach(block => this.observer.observe(block));
                    });
                },
                scrollTo(id) {
                    const el = document.getElementById(id);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
        </script>
    </x-ui-page-container>

    {{-- Left Sidebar: Kommentare --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Kommentare ({{ $allComments->count() }})" width="w-80" :defaultOpen="true" storeKey="sidebarOpen">
            <div class="p-4 space-y-4">
                {{-- Block Filter Chips --}}
                <div class="overflow-x-auto -mx-4 px-4">
                    <div class="flex items-center gap-1.5 flex-nowrap">
                        <button
                            wire:click="filterByBlock(null)"
                            class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-medium transition-colors whitespace-nowrap {{ !$filterBlockId ? 'bg-[rgb(var(--ui-primary-rgb))] text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                        >
                            Alle
                        </button>
                        @foreach($canvas->buildingBlocks as $block)
                            @php $blockCount = $allComments->where('building_block_id', $block->id)->count(); @endphp
                            @if($blockCount > 0)
                            <button
                                wire:click="filterByBlock({{ $block->id }})"
                                class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-medium transition-colors whitespace-nowrap {{ $filterBlockId === $block->id ? 'bg-[rgb(var(--ui-primary-rgb))] text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                            >
                                {{ $block->label }} ({{ $blockCount }})
                            </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Comment Form --}}
                <form wire:submit="addComment" class="space-y-2">
                    @if($replyToId)
                        @php $replyTarget = $comments->firstWhere('id', $replyToId); @endphp
                        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-[rgb(var(--ui-primary-rgb))]/5 border border-[rgb(var(--ui-primary-rgb))]/20">
                            @svg('heroicon-o-arrow-uturn-left', 'w-3 h-3 text-[rgb(var(--ui-primary-rgb))] shrink-0')
                            <span class="text-[10px] text-[var(--ui-secondary)] truncate grow">
                                Antwort auf: {{ Str::limit($replyTarget?->content ?? '', 50) }}
                            </span>
                            <button type="button" wire:click="cancelReply" class="shrink-0 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                            </button>
                        </div>
                    @elseif($commentBlockId)
                        @php $selectedBlock = $canvas->buildingBlocks->firstWhere('id', $commentBlockId); @endphp
                        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/30">
                            @svg('heroicon-o-cube', 'w-3 h-3 text-[var(--ui-muted)] shrink-0')
                            <span class="text-[10px] text-[var(--ui-secondary)] grow">{{ $selectedBlock?->label ?? 'Block' }}</span>
                            <button type="button" wire:click="$set('commentBlockId', null)" class="shrink-0 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                            </button>
                        </div>
                    @else
                        @php
                            $blockOptions = $canvas->buildingBlocks->mapWithKeys(fn($b) => [$b->id => $b->label])->toArray();
                        @endphp
                        <x-ui-input-select
                            name="commentBlockId"
                            :options="$blockOptions"
                            :nullable="true"
                            nullLabel="Canvas-weiter Kommentar"
                            size="sm"
                            wire:model="commentBlockId"
                        />
                    @endif

                    <div class="flex gap-2">
                        <textarea
                            wire:model="commentContent"
                            rows="2"
                            placeholder="{{ $replyToId ? 'Antwort schreiben...' : 'Kommentar schreiben...' }}"
                            class="grow rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-bg)] text-xs text-[var(--ui-secondary)] p-2.5 resize-none focus:ring-1 focus:ring-[rgb(var(--ui-primary-rgb))]"
                        ></textarea>
                        <button
                            type="submit"
                            class="shrink-0 self-end px-3 py-2 rounded-lg bg-[rgb(var(--ui-primary-rgb))] text-white text-xs font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
                            wire:loading.attr="disabled"
                        >
                            @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                        </button>
                    </div>
                    @error('commentContent')
                        <p class="text-[10px] text-red-500">{{ $message }}</p>
                    @enderror
                </form>

                {{-- Comments List --}}
                <div class="space-y-3">
                    @forelse($comments as $comment)
                        <div class="space-y-2">
                            {{-- Root Comment --}}
                            <div class="rounded-lg border border-[var(--ui-border)]/30 bg-[var(--ui-bg)] p-3 hover:border-[var(--ui-border)]/50 transition-colors">
                                <div class="flex items-center gap-2 mb-1.5">
                                    @if($comment->building_block_id)
                                        <span class="flex items-center gap-1 text-[9px] font-medium text-[rgb(var(--ui-primary-rgb))] bg-[rgb(var(--ui-primary-rgb))]/10 rounded px-1.5 py-0.5">
                                            @svg('heroicon-o-cube', 'w-2.5 h-2.5')
                                            {{ $comment->buildingBlock?->label ?? 'Block' }}
                                        </span>
                                    @else
                                        <span class="text-[9px] font-medium text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded px-1.5 py-0.5">Canvas-weit</span>
                                    @endif
                                    <span class="text-[10px] text-[var(--ui-muted)]/60 ml-auto">{{ $comment->created_at->format('d.m. H:i') }}</span>
                                </div>
                                <p class="text-xs text-[var(--ui-secondary)] leading-relaxed whitespace-pre-line">{{ $comment->content }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <button
                                        wire:click="setReplyTo({{ $comment->id }})"
                                        class="flex items-center gap-1 text-[10px] text-[var(--ui-muted)] hover:text-[rgb(var(--ui-primary-rgb))] transition-colors"
                                    >
                                        @svg('heroicon-o-arrow-uturn-left', 'w-3 h-3')
                                        Antworten
                                    </button>
                                    @if($comment->replies->count() > 0)
                                        <span class="text-[10px] text-[var(--ui-muted)]/60">
                                            {{ $comment->replies->count() }} {{ $comment->replies->count() === 1 ? 'Antwort' : 'Antworten' }}
                                        </span>
                                    @endif
                                    <button
                                        wire:click="deleteComment({{ $comment->id }})"
                                        wire:confirm="Kommentar und alle Antworten loeschen?"
                                        class="flex items-center gap-1 text-[10px] text-[var(--ui-muted)] hover:text-red-500 transition-colors ml-auto"
                                    >
                                        @svg('heroicon-o-trash', 'w-3 h-3')
                                    </button>
                                </div>
                            </div>

                            {{-- Replies --}}
                            @if($comment->replies->count() > 0)
                                <div class="ml-4 space-y-2 border-l-2 border-[var(--ui-border)]/20 pl-3">
                                    @foreach($comment->replies as $reply)
                                        <div class="rounded-lg border border-[var(--ui-border)]/20 bg-[var(--ui-muted-5)]/30 p-2.5 group/reply">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-[10px] text-[var(--ui-muted)]/60">{{ $reply->created_at->format('d.m. H:i') }}</span>
                                                <button
                                                    wire:click="deleteComment({{ $reply->id }})"
                                                    wire:confirm="Antwort loeschen?"
                                                    class="ml-auto opacity-0 group-hover/reply:opacity-100 text-[var(--ui-muted)] hover:text-red-500 transition-all"
                                                >
                                                    @svg('heroicon-o-trash', 'w-3 h-3')
                                                </button>
                                            </div>
                                            <p class="text-[11px] text-[var(--ui-secondary)] leading-relaxed whitespace-pre-line">{{ $reply->content }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8">
                            @svg('heroicon-o-chat-bubble-left-right', 'w-8 h-8 text-[var(--ui-muted)]/30 mx-auto mb-3')
                            <p class="text-xs text-[var(--ui-muted)]/60">
                                {{ $filterBlockId ? 'Keine Kommentare fuer diesen Block.' : 'Noch keine Kommentare.' }}
                            </p>
                            @if($filterBlockId)
                                <button wire:click="filterByBlock(null)" class="mt-2 text-[10px] text-[rgb(var(--ui-primary-rgb))] hover:opacity-80">
                                    Alle Kommentare anzeigen
                                </button>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar: Aktivitaeten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitaeten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivitaeten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)] hover:bg-[var(--ui-muted)] transition-colors">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-[var(--ui-secondary)] leading-snug">
                                        {{ $activity['title'] ?? 'Aktivitaet' }}
                                    </div>
                                </div>
                                @if(($activity['type'] ?? null) === 'system')
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-xs text-[var(--ui-muted)]">
                                            @svg('heroicon-o-cog', 'w-3 h-3')
                                            System
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                @svg('heroicon-o-clock', 'w-3 h-3')
                                <span>{{ $activity['time'] ?? '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivitaeten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Aenderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
