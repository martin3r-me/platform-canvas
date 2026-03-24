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
                @if($canvas->canvasType)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-[rgb(var(--ui-primary-rgb))]/10 text-[rgb(var(--ui-primary-rgb))] border border-[rgb(var(--ui-primary-rgb))]/20">
                        @svg('heroicon-o-squares-2x2', 'w-3.5 h-3.5')
                        {{ $canvas->canvasType->name }}
                    </span>
                @endif
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
    <x-ui-page-container padding="p-0" spacing="" background="">
        <div x-data="blockNav()" x-init="init()">
            {{-- Block Navigation --}}
            <div class="sticky top-0 z-20 border-b border-[var(--ui-border)]/40 bg-[var(--ui-surface)]/95 backdrop-blur-sm">
                <div class="px-4 sm:px-6 overflow-x-auto">
                    <div class="flex items-center gap-1 py-2">
                        @if($canvas->canvasType)
                            <span class="shrink-0 text-[11px] font-bold text-[rgb(var(--ui-primary-rgb))] uppercase tracking-wider mr-2">{{ $canvas->canvasType->name }}</span>
                            <span class="shrink-0 w-px h-4 bg-[var(--ui-border)]/40 mr-1"></span>
                        @endif
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

                {{-- Recommendations (completeness) --}}
                @if(($analysisData['strategy'] ?? null) === 'completeness')
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
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar: Kommentare --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Kommentare ({{ $allComments->count() }})" width="w-96" :defaultOpen="false" storeKey="activityOpen" side="right">
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
                        <select wire:model="commentBlockId" class="w-full text-[11px] rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-bg)] text-[var(--ui-secondary)] px-2.5 py-1.5 focus:ring-1 focus:ring-[rgb(var(--ui-primary-rgb))]">
                            <option value="">Canvas-weiter Kommentar</option>
                            @foreach($canvas->buildingBlocks as $block)
                                <option value="{{ $block->id }}">{{ $block->label }}</option>
                            @endforeach
                        </select>
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
</x-ui-page>
