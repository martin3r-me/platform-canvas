<div class="h-screen overflow-hidden bg-[var(--ui-bg)] d-flex flex-col" x-data="{ commentsOpen: true }">
    {{-- Fixed Header --}}
    <div class="flex-shrink-0 border-b border-[var(--ui-border)]/60 bg-[var(--ui-surface)]/95 backdrop-blur-sm z-30">
        <div class="px-4 sm:px-6 lg:px-8 py-3 d-flex items-center justify-between">
            <div class="min-w-0 flex-grow-1">
                <h1 class="text-lg font-bold text-[var(--ui-secondary)] truncate">{{ $canvas->name }}</h1>
                <div class="d-flex items-center gap-2 mt-0.5 text-xs text-[var(--ui-muted)]">
                    <span>{{ $canvas->canvasType?->name ?? 'Canvas' }}</span>
                    <span class="opacity-40">&middot;</span>
                    <span>{{ $canvas->updated_at?->format('d.m.Y H:i') }}</span>
                    @if($canvas->description)
                        <span class="opacity-40">&middot;</span>
                        <span class="hidden sm:inline truncate max-w-xs">{{ $canvas->description }}</span>
                    @endif
                </div>
            </div>
            <button
                x-on:click="commentsOpen = !commentsOpen"
                class="flex-shrink-0 ml-4 d-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                :class="commentsOpen ? 'bg-blue-500/10 text-blue-600 hover:bg-blue-500/20' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]'"
            >
                @svg('heroicon-o-chat-bubble-left-right', 'w-4 h-4')
                <span class="hidden sm:inline" x-text="commentsOpen ? 'Kommentare ausblenden' : 'Kommentare einblenden'"></span>
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] font-bold bg-blue-500 text-white">{{ $allComments->count() }}</span>
            </button>
        </div>
    </div>

    {{-- Main Layout: Canvas + Sidebar --}}
    <div class="flex-grow-1 d-flex min-h-0">
        {{-- Canvas Area --}}
        <div class="flex-grow-1 min-w-0 overflow-y-auto p-4 sm:p-6 lg:p-8">
            @php
                $hasAreas = !empty($layout['areas'] ?? null) && !empty($layout['area_map'] ?? null);
                $columns = $layout['columns'] ?? 3;
                $rows = $layout['rows'] ?? 3;
            @endphp

            @if($hasAreas)
                @php
                    $areasRows = array_map('trim', explode('/', $layout['areas']));
                    $cssAreas = collect($areasRows)->map(fn($row) => "'" . $row . "'")->implode(' ');
                    $areaMap = $layout['area_map'];
                @endphp
                {{-- Desktop: area grid --}}
                <div class="hidden lg:grid gap-3" style="grid-template-columns: repeat({{ $columns }}, 1fr); grid-template-rows: {{ str_repeat('auto ', $rows) }}; grid-template-areas: {{ $cssAreas }};">
                    @foreach($blockDefs as $def)
                        @php
                            $blockKey = $def['key'];
                            $areaName = $areaMap[$blockKey] ?? null;
                            $blockData = $canvasData['blocks'][$blockKey] ?? null;
                            $blockCommentCount = $blockData ? $allComments->where('building_block_id', $blockData['id'])->count() : 0;
                        @endphp
                        @if($areaName)
                        <div style="grid-area: {{ $areaName }};" class="relative group">
                            @include('canvas::livewire.canvas._block', ['blockKey' => $blockKey, 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                            @if($blockCommentCount > 0)
                                <button
                                    wire:click="filterByBlock({{ $blockData['id'] }})"
                                    x-on:click="commentsOpen = true"
                                    class="absolute top-2 right-2 d-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500 text-white hover:bg-blue-600 transition-colors shadow-sm"
                                >
                                    @svg('heroicon-s-chat-bubble-left', 'w-3 h-3')
                                    {{ $blockCommentCount }}
                                </button>
                            @endif
                            @if($blockData)
                                <button
                                    wire:click="$set('commentBlockId', {{ $blockData['id'] }})"
                                    x-on:click="commentsOpen = true"
                                    class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity p-1.5 rounded-lg bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 text-[var(--ui-muted)] hover:text-blue-500 shadow-sm"
                                    title="Kommentar zu diesem Block"
                                >
                                    @svg('heroicon-o-chat-bubble-left', 'w-3.5 h-3.5')
                                </button>
                            @endif
                        </div>
                        @endif
                    @endforeach
                </div>
                {{-- Mobile/Tablet fallback --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:hidden">
                    @foreach($blockDefs as $def)
                        @php
                            $blockKey = $def['key'];
                            $blockData = $canvasData['blocks'][$blockKey] ?? null;
                            $blockCommentCount = $blockData ? $allComments->where('building_block_id', $blockData['id'])->count() : 0;
                        @endphp
                        <div class="relative group">
                            @include('canvas::livewire.canvas._block', ['blockKey' => $blockKey, 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                            @if($blockCommentCount > 0)
                                <button
                                    wire:click="filterByBlock({{ $blockData['id'] }})"
                                    x-on:click="commentsOpen = true"
                                    class="absolute top-2 right-2 d-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500 text-white hover:bg-blue-600 transition-colors shadow-sm"
                                >
                                    @svg('heroicon-s-chat-bubble-left', 'w-3 h-3')
                                    {{ $blockCommentCount }}
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $columns }} gap-3">
                    @foreach($blockDefs as $def)
                        @php
                            $blockKey = $def['key'];
                            $blockData = $canvasData['blocks'][$blockKey] ?? null;
                            $blockCommentCount = $blockData ? $allComments->where('building_block_id', $blockData['id'])->count() : 0;
                        @endphp
                        <div class="relative group">
                            @include('canvas::livewire.canvas._block', ['blockKey' => $blockKey, 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                            @if($blockCommentCount > 0)
                                <button
                                    wire:click="filterByBlock({{ $blockData['id'] }})"
                                    x-on:click="commentsOpen = true"
                                    class="absolute top-2 right-2 d-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500 text-white hover:bg-blue-600 transition-colors shadow-sm"
                                >
                                    @svg('heroicon-s-chat-bubble-left', 'w-3 h-3')
                                    {{ $blockCommentCount }}
                                </button>
                            @endif
                            @if($blockData)
                                <button
                                    wire:click="$set('commentBlockId', {{ $blockData['id'] }})"
                                    x-on:click="commentsOpen = true"
                                    class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity p-1.5 rounded-lg bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 text-[var(--ui-muted)] hover:text-blue-500 shadow-sm"
                                    title="Kommentar zu diesem Block"
                                >
                                    @svg('heroicon-o-chat-bubble-left', 'w-3.5 h-3.5')
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Comment Sidebar (Desktop: right panel, Mobile: overlay) --}}
        <div
            x-show="commentsOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="w-[85vw] sm:w-[400px] lg:w-[400px] flex-shrink-0 border-l border-[var(--ui-border)]/60 bg-[var(--ui-surface)] d-flex flex-col
                   fixed lg:relative inset-y-0 right-0 z-40 lg:z-auto"
            x-cloak
        >
            {{-- Sidebar Header --}}
            <div class="d-flex items-center justify-between px-4 py-3 border-b border-[var(--ui-border)]/40 flex-shrink-0">
                <div class="d-flex items-center gap-2">
                    <h2 class="text-sm font-bold text-[var(--ui-secondary)]">Kommentare</h2>
                    <span class="text-[10px] font-semibold text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-full px-2 py-0.5">{{ $allComments->count() }}</span>
                </div>
                <div class="d-flex items-center gap-1">
                    @if($filterBlockId)
                        <button
                            wire:click="filterByBlock(null)"
                            class="d-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-medium bg-blue-500/10 text-blue-600 hover:bg-blue-500/20 transition-colors"
                        >
                            @svg('heroicon-o-funnel', 'w-3 h-3')
                            Filter aktiv
                            @svg('heroicon-o-x-mark', 'w-3 h-3')
                        </button>
                    @endif
                    <button x-on:click="commentsOpen = false" class="lg:hidden p-1 rounded text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                        @svg('heroicon-o-x-mark', 'w-5 h-5')
                    </button>
                </div>
            </div>

            {{-- Block Filter Chips --}}
            <div class="px-4 py-2 border-b border-[var(--ui-border)]/20 flex-shrink-0 overflow-x-auto">
                <div class="d-flex items-center gap-1.5 flex-nowrap">
                    <button
                        wire:click="filterByBlock(null)"
                        class="flex-shrink-0 px-2.5 py-1 rounded-full text-[10px] font-medium transition-colors whitespace-nowrap {{ !$filterBlockId ? 'bg-blue-500 text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                    >
                        Alle
                    </button>
                    @foreach($canvas->buildingBlocks as $block)
                        @php $blockCount = $allComments->where('building_block_id', $block->id)->count(); @endphp
                        @if($blockCount > 0)
                        <button
                            wire:click="filterByBlock({{ $block->id }})"
                            class="flex-shrink-0 px-2.5 py-1 rounded-full text-[10px] font-medium transition-colors whitespace-nowrap {{ $filterBlockId === $block->id ? 'bg-blue-500 text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                        >
                            {{ $block->label }} ({{ $blockCount }})
                        </button>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Comment Form --}}
            <div class="px-4 py-3 border-b border-[var(--ui-border)]/30 flex-shrink-0">
                <form wire:submit="addComment">
                    @if($replyToId)
                        @php $replyTarget = $comments->firstWhere('id', $replyToId); @endphp
                        <div class="d-flex items-center gap-2 mb-2 px-2 py-1.5 rounded-lg bg-blue-500/5 border border-blue-500/20">
                            @svg('heroicon-o-arrow-uturn-left', 'w-3 h-3 text-blue-500 flex-shrink-0')
                            <span class="text-[10px] text-blue-600 truncate flex-grow-1">
                                Antwort auf: {{ Str::limit($replyTarget?->content ?? '', 50) }}
                            </span>
                            <button type="button" wire:click="cancelReply" class="flex-shrink-0 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                            </button>
                        </div>
                    @elseif($commentBlockId)
                        @php $selectedBlock = $canvas->buildingBlocks->firstWhere('id', $commentBlockId); @endphp
                        <div class="d-flex items-center gap-2 mb-2 px-2 py-1.5 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/30">
                            @svg('heroicon-o-cube', 'w-3 h-3 text-[var(--ui-muted)] flex-shrink-0')
                            <span class="text-[10px] text-[var(--ui-secondary)] flex-grow-1">{{ $selectedBlock?->label ?? 'Block' }}</span>
                            <button type="button" wire:click="$set('commentBlockId', null)" class="flex-shrink-0 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                            </button>
                        </div>
                    @else
                        <select wire:model="commentBlockId" class="w-full text-[11px] rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-bg)] text-[var(--ui-secondary)] px-2.5 py-1.5 mb-2 focus:ring-1 focus:ring-blue-500">
                            <option value="">Canvas-weiter Kommentar</option>
                            @foreach($canvas->buildingBlocks as $block)
                                <option value="{{ $block->id }}">{{ $block->label }}</option>
                            @endforeach
                        </select>
                    @endif

                    <div class="d-flex gap-2">
                        <textarea
                            wire:model="commentContent"
                            rows="2"
                            placeholder="{{ $replyToId ? 'Antwort schreiben...' : 'Kommentar schreiben...' }}"
                            class="flex-grow-1 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-bg)] text-xs text-[var(--ui-secondary)] p-2.5 resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                        <button
                            type="submit"
                            class="flex-shrink-0 self-end px-3 py-2 rounded-lg bg-blue-500 text-white text-xs font-medium hover:bg-blue-600 transition-colors disabled:opacity-50"
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
            <div class="flex-grow-1 overflow-y-auto px-4 py-3 space-y-3">
                @forelse($comments as $comment)
                    {{-- Root Comment --}}
                    <div class="space-y-2">
                        <div class="rounded-lg border border-[var(--ui-border)]/30 bg-[var(--ui-bg)] p-3 hover:border-[var(--ui-border)]/50 transition-colors">
                            <div class="d-flex items-center gap-2 mb-1.5">
                                @if($comment->building_block_id)
                                    <span class="d-flex items-center gap-1 text-[9px] font-medium text-blue-600 bg-blue-500/10 rounded px-1.5 py-0.5">
                                        @svg('heroicon-o-cube', 'w-2.5 h-2.5')
                                        {{ $comment->buildingBlock?->label ?? 'Block' }}
                                    </span>
                                @else
                                    <span class="text-[9px] font-medium text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded px-1.5 py-0.5">Canvas-weit</span>
                                @endif
                                <span class="text-[10px] text-[var(--ui-muted)]/60 ml-auto">{{ $comment->created_at->format('d.m. H:i') }}</span>
                            </div>
                            <p class="text-xs text-[var(--ui-secondary)] leading-relaxed whitespace-pre-line">{{ $comment->content }}</p>
                            <div class="mt-2 d-flex items-center gap-2">
                                <button
                                    wire:click="setReplyTo({{ $comment->id }})"
                                    class="d-flex items-center gap-1 text-[10px] text-[var(--ui-muted)] hover:text-blue-500 transition-colors"
                                >
                                    @svg('heroicon-o-arrow-uturn-left', 'w-3 h-3')
                                    Antworten
                                </button>
                                @if($comment->replies->count() > 0)
                                    <span class="text-[10px] text-[var(--ui-muted)]/60">
                                        {{ $comment->replies->count() }} {{ $comment->replies->count() === 1 ? 'Antwort' : 'Antworten' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Replies --}}
                        @if($comment->replies->count() > 0)
                            <div class="ml-4 space-y-2 border-l-2 border-[var(--ui-border)]/20 pl-3">
                                @foreach($comment->replies as $reply)
                                    <div class="rounded-lg border border-[var(--ui-border)]/20 bg-[var(--ui-muted-5)]/30 p-2.5">
                                        <div class="d-flex items-center gap-2 mb-1">
                                            <span class="text-[10px] text-[var(--ui-muted)]/60">{{ $reply->created_at->format('d.m. H:i') }}</span>
                                        </div>
                                        <p class="text-[11px] text-[var(--ui-secondary)] leading-relaxed whitespace-pre-line">{{ $reply->content }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12">
                        @svg('heroicon-o-chat-bubble-left-right', 'w-8 h-8 text-[var(--ui-muted)]/30 mx-auto mb-3')
                        <p class="text-xs text-[var(--ui-muted)]/60">
                            {{ $filterBlockId ? 'Keine Kommentare f&uuml;r diesen Block.' : 'Noch keine Kommentare vorhanden.' }}
                        </p>
                        @if($filterBlockId)
                            <button wire:click="filterByBlock(null)" class="mt-2 text-[10px] text-blue-500 hover:text-blue-600">
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
</div>
