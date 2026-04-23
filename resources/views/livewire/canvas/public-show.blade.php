<div class="h-screen flex flex-col bg-[#f5f5f0]" x-data="{ commentsOpen: true, activeBlock: '', ...blockNav() }" x-init="initBlockNav()">
    {{-- Header --}}
    <div class="shrink-0 border-b border-gray-200 bg-white/95 backdrop-blur-sm z-30">
        <div class="px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <div class="min-w-0 grow">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-bold text-[#1a1a2e] truncate">{{ $canvas->name }}</h1>
                    <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#f2ca52] text-[#1a1a2e] shadow-sm">
                        @svg('heroicon-o-squares-2x2', 'w-3.5 h-3.5')
                        {{ $canvas->canvasType?->name ?? 'Canvas' }}
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-400">
                    <span>{{ $canvas->updated_at?->format('d.m.Y H:i') }}</span>
                </div>
            </div>
            <button
                x-on:click="commentsOpen = !commentsOpen"
                class="shrink-0 ml-4 flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                :class="commentsOpen ? 'bg-[#f2ca52]/20 text-[#1a1a2e] hover:bg-[#f2ca52]/30' : 'bg-gray-100 text-gray-400 hover:text-[#1a1a2e]'"
            >
                @svg('heroicon-o-chat-bubble-left-right', 'w-4 h-4')
                <span class="hidden sm:inline" x-text="commentsOpen ? 'Kommentare ausblenden' : 'Kommentare einblenden'"></span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] font-bold bg-[#f2ca52] text-[#1a1a2e]">{{ $allComments->count() }}</span>
            </button>
        </div>
    </div>

    {{-- Block Navigation --}}
    <div class="shrink-0 border-b border-gray-200/30 bg-white/80 backdrop-blur-sm z-20">
        <div class="px-4 sm:px-6 overflow-x-auto">
            <div class="flex items-center gap-1 py-2">
                <span class="shrink-0 text-[11px] font-bold text-[#f2ca52] uppercase tracking-wider mr-2">{{ $canvas->canvasType?->name ?? 'Canvas' }}</span>
                <span class="shrink-0 w-px h-4 bg-gray-200/40 mr-1"></span>
                @foreach($blockDefs as $def)
                    @php
                        $blockKey = $def['key'];
                        $config = collect($blockDefs)->firstWhere('key', $blockKey) ?? [];
                        $label = $config['label'] ?? ucfirst(str_replace('_', ' ', $blockKey));
                        $blockData = $canvasData['blocks'][$blockKey] ?? null;
                        $blockCommentCount = $blockData ? $allComments->where('building_block_id', $blockData['id'])->count() : 0;
                    @endphp
                    <button
                        x-on:click="scrollToBlock('pub-block-{{ $blockKey }}')"
                        :class="activeBlock === 'pub-block-{{ $blockKey }}' ? 'bg-[#f2ca52] text-[#1a1a2e] shadow-sm' : 'text-gray-400 hover:text-[#1a1a2e] hover:bg-yellow-50'"
                        class="shrink-0 px-3 py-1.5 rounded-full text-[11px] font-medium transition-all whitespace-nowrap flex items-center gap-1.5"
                    >
                        {{ $label }}
                        @if($blockCommentCount > 0)
                            <span class="inline-flex items-center justify-center min-w-[1rem] h-4 px-1 rounded-full text-[9px] font-bold"
                                  :class="activeBlock === 'pub-block-{{ $blockKey }}' ? 'bg-[#1a1a2e]/20 text-[#1a1a2e]' : 'bg-[#f2ca52]/20 text-[#f2ca52]'">{{ $blockCommentCount }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Body: Canvas + Sidebar --}}
    <div class="grow flex min-h-0">
        {{-- Canvas Area --}}
        <div class="grow min-w-0 overflow-y-auto" id="canvas-scroll-area">
            <div class="px-4 sm:px-6 py-6 space-y-6">
                @if($canvas->description)
                <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
                    <div class="flex items-center gap-2 mb-2">
                        @svg('heroicon-o-document-text', 'w-4 h-4 text-gray-400')
                        <h2 class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">Beschreibung</h2>
                    </div>
                    <p class="text-[13px] text-[#1a1a2e] leading-relaxed whitespace-pre-line">{{ $canvas->description }}</p>
                </div>
                @endif

                @foreach($blockDefs as $def)
                    @php
                        $blockKey = $def['key'];
                        $blockData = $canvasData['blocks'][$blockKey] ?? null;
                        $blockCommentCount = $blockData ? $allComments->where('building_block_id', $blockData['id'])->count() : 0;
                    @endphp
                    <div id="pub-block-{{ $blockKey }}" class="relative group scroll-mt-4" data-pub-block>
                        @include('canvas::livewire.canvas._block', ['blockKey' => $blockKey, 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                        @if($blockData)
                            <div class="absolute top-2 right-2 flex items-center gap-1">
                                @if($blockCommentCount > 0)
                                    <button
                                        wire:click="filterByBlock({{ $blockData['id'] }})"
                                        x-on:click="commentsOpen = true"
                                        class="flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-[#f2ca52] text-[#1a1a2e] hover:bg-[#e0b83e] transition-colors shadow-sm"
                                    >
                                        @svg('heroicon-s-chat-bubble-left', 'w-3 h-3')
                                        {{ $blockCommentCount }}
                                    </button>
                                @endif
                                <button
                                    wire:click="$set('commentBlockId', {{ $blockData['id'] }})"
                                    x-on:click="commentsOpen = true"
                                    class="opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity p-1.5 rounded-full bg-white border border-gray-200 text-gray-400 hover:text-[#f2ca52] shadow-sm"
                                    title="Kommentar zu diesem Block"
                                >
                                    @svg('heroicon-o-chat-bubble-left', 'w-3.5 h-3.5')
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
                {{-- Spacer so the last block can scroll to top and get highlighted --}}
                <div class="h-[60vh]"></div>
            </div>
        </div>

        {{-- Comment Sidebar --}}
        <div
            x-show="commentsOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="shrink-0 w-[85vw] sm:w-[400px] border-l border-gray-200 bg-white flex flex-col
                   fixed inset-y-0 right-0 z-40
                   lg:relative lg:inset-auto lg:z-auto"
            x-cloak
        >
            {{-- Sidebar Header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-[#1a1a2e]">Kommentare</h2>
                    <span class="text-[10px] font-semibold text-gray-500 bg-gray-100 rounded-full px-2 py-0.5">{{ $allComments->count() }}</span>
                </div>
                <div class="flex items-center gap-1">
                    @if($filterBlockId)
                        <button
                            wire:click="filterByBlock(null)"
                            class="flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-medium bg-[#f2ca52]/20 text-[#1a1a2e] hover:bg-[#f2ca52]/30 transition-colors"
                        >
                            @svg('heroicon-o-funnel', 'w-3 h-3')
                            Filter aktiv
                            @svg('heroicon-o-x-mark', 'w-3 h-3')
                        </button>
                    @endif
                    <button x-on:click="commentsOpen = false" class="p-1.5 rounded-full text-gray-400 hover:text-[#1a1a2e] hover:bg-yellow-50 transition-colors">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                    </button>
                </div>
            </div>

            {{-- Block Filter Chips --}}
            <div class="px-4 py-2 border-b border-gray-100 shrink-0 overflow-x-auto">
                <div class="flex items-center gap-1.5 flex-nowrap">
                    <button
                        wire:click="filterByBlock(null)"
                        class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-medium transition-colors whitespace-nowrap {{ !$filterBlockId ? 'bg-[#f2ca52] text-[#1a1a2e]' : 'bg-gray-100 text-gray-400 hover:text-[#1a1a2e]' }}"
                    >
                        Alle
                    </button>
                    @foreach($canvas->buildingBlocks as $block)
                        @php $blockCount = $allComments->where('building_block_id', $block->id)->count(); @endphp
                        @if($blockCount > 0)
                        <button
                            wire:click="filterByBlock({{ $block->id }})"
                            class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-medium transition-colors whitespace-nowrap {{ $filterBlockId === $block->id ? 'bg-[#f2ca52] text-[#1a1a2e]' : 'bg-gray-100 text-gray-400 hover:text-[#1a1a2e]' }}"
                        >
                            {{ $block->label }} ({{ $blockCount }})
                        </button>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Comment Form --}}
            <div class="px-4 py-3 border-b border-gray-100 shrink-0">
                <form wire:submit="addComment">
                    @if($replyToId)
                        @php $replyTarget = $comments->firstWhere('id', $replyToId); @endphp
                        <div class="flex items-center gap-2 mb-2 px-2 py-1.5 rounded-xl bg-[#f2ca52]/10 border border-[#f2ca52]/30">
                            @svg('heroicon-o-arrow-uturn-left', 'w-3 h-3 text-[#f2ca52] shrink-0')
                            <span class="text-[10px] text-[#1a1a2e] truncate grow">
                                Antwort auf: {{ Str::limit($replyTarget?->content ?? '', 50) }}
                            </span>
                            <button type="button" wire:click="cancelReply" class="shrink-0 text-gray-400 hover:text-[#1a1a2e]">
                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                            </button>
                        </div>
                    @elseif($commentBlockId)
                        @php $selectedBlock = $canvas->buildingBlocks->firstWhere('id', $commentBlockId); @endphp
                        <div class="flex items-center gap-2 mb-2 px-2 py-1.5 rounded-xl bg-gray-50 border border-gray-200">
                            @svg('heroicon-o-cube', 'w-3 h-3 text-gray-400 shrink-0')
                            <span class="text-[10px] text-[#1a1a2e] grow">{{ $selectedBlock?->label ?? 'Block' }}</span>
                            <button type="button" wire:click="$set('commentBlockId', null)" class="shrink-0 text-gray-400 hover:text-[#1a1a2e]">
                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                            </button>
                        </div>
                    @else
                        <select wire:model="commentBlockId" class="w-full appearance-none text-[13px] rounded-xl border border-gray-200 bg-white text-gray-900 px-3 py-2 pr-10 mb-2 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#f2ca52]/30 focus:border-[#f2ca52] transition-colors">
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
                            class="grow rounded-xl border border-gray-200 bg-white text-xs text-[#1a1a2e] p-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-[#f2ca52]/30 focus:border-[#f2ca52] transition-colors"
                        ></textarea>
                        <button
                            type="submit"
                            class="shrink-0 self-end px-3 py-2 rounded-full bg-[#f2ca52] text-[#1a1a2e] text-xs font-bold hover:bg-[#e0b83e] transition-colors disabled:opacity-50 shadow-sm"
                            wire:loading.attr="disabled"
                        >
                            @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                        </button>
                    </div>
                    @error('commentContent')
                        <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            {{-- Comments List --}}
            <div class="grow overflow-y-auto px-4 py-3 space-y-3">
                @forelse($comments as $comment)
                    <div class="space-y-2">
                        {{-- Root Comment --}}
                        <div class="rounded-xl border border-gray-200 bg-white p-3 hover:shadow-sm transition-shadow">
                            <div class="flex items-center gap-2 mb-1.5">
                                @if($comment->building_block_id)
                                    <span class="flex items-center gap-1 text-[9px] font-medium text-[#f2ca52] bg-[#f2ca52]/10 rounded-full px-1.5 py-0.5">
                                        @svg('heroicon-o-cube', 'w-2.5 h-2.5')
                                        {{ $comment->buildingBlock?->label ?? 'Block' }}
                                    </span>
                                @else
                                    <span class="text-[9px] font-medium text-gray-400 bg-gray-100 rounded-full px-1.5 py-0.5">Canvas-weit</span>
                                @endif
                                <span class="text-[10px] text-gray-300 ml-auto">{{ $comment->created_at->format('d.m. H:i') }}</span>
                            </div>
                            <p class="text-xs text-[#1a1a2e] leading-relaxed whitespace-pre-line">{{ $comment->content }}</p>
                            <div class="mt-2 flex items-center gap-2">
                                <button
                                    wire:click="setReplyTo({{ $comment->id }})"
                                    class="flex items-center gap-1 text-[10px] text-gray-400 hover:text-[#f2ca52] transition-colors"
                                >
                                    @svg('heroicon-o-arrow-uturn-left', 'w-3 h-3')
                                    Antworten
                                </button>
                                @if($comment->replies->count() > 0)
                                    <span class="text-[10px] text-gray-300">
                                        {{ $comment->replies->count() }} {{ $comment->replies->count() === 1 ? 'Antwort' : 'Antworten' }}
                                    </span>
                                @endif
                                <button
                                    wire:click="deleteComment({{ $comment->id }})"
                                    wire:confirm="Kommentar und alle Antworten l&ouml;schen?"
                                    class="flex items-center gap-1 text-[10px] text-gray-400 hover:text-red-500 transition-colors ml-auto"
                                >
                                    @svg('heroicon-o-trash', 'w-3 h-3')
                                </button>
                            </div>
                        </div>

                        {{-- Replies --}}
                        @if($comment->replies->count() > 0)
                            <div class="ml-4 space-y-2 border-l-2 border-gray-100 pl-3">
                                @foreach($comment->replies as $reply)
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-2.5 group/reply">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[10px] text-gray-300">{{ $reply->created_at->format('d.m. H:i') }}</span>
                                            <button
                                                wire:click="deleteComment({{ $reply->id }})"
                                                wire:confirm="Antwort l&ouml;schen?"
                                                class="ml-auto opacity-0 group-hover/reply:opacity-100 text-gray-400 hover:text-red-500 transition-all"
                                            >
                                                @svg('heroicon-o-trash', 'w-3 h-3')
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-[#1a1a2e] leading-relaxed whitespace-pre-line">{{ $reply->content }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12">
                        @svg('heroicon-o-chat-bubble-left-right', 'w-8 h-8 text-gray-200 mx-auto mb-3')
                        <p class="text-xs text-gray-400">
                            {{ $filterBlockId ? 'Keine Kommentare f&uuml;r diesen Block.' : 'Noch keine Kommentare vorhanden.' }}
                        </p>
                        @if($filterBlockId)
                            <button wire:click="filterByBlock(null)" class="mt-2 text-[10px] text-[#f2ca52] hover:text-[#e0b83e]">
                                Alle Kommentare anzeigen
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Mobile Overlay Backdrop --}}
        <div
            x-show="commentsOpen"
            x-on:click="commentsOpen = false"
            class="fixed inset-0 bg-black/20 z-30 lg:hidden"
            x-cloak
        ></div>
    </div>

    <script>
    function blockNav() {
        return {
            _observer: null,
            initBlockNav() {
                this.$nextTick(() => {
                    const scrollArea = document.getElementById('canvas-scroll-area');
                    const blocks = scrollArea?.querySelectorAll('[data-pub-block]');
                    if (!blocks || !blocks.length) return;
                    this.activeBlock = blocks[0]?.id || '';
                    this._observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.activeBlock = entry.target.id;
                            }
                        });
                    }, { root: scrollArea, rootMargin: '-10% 0px -70% 0px', threshold: 0 });
                    blocks.forEach(block => this._observer.observe(block));
                });
            },
            scrollToBlock(id) {
                document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }
    </script>
</div>
