<div class="min-h-screen bg-[var(--ui-bg)]">
    {{-- Header --}}
    <div class="border-b border-[var(--ui-border)]/60 bg-[var(--ui-surface)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-bold text-[var(--ui-secondary)]">{{ $canvas->name }}</h1>
            @if($canvas->description)
                <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $canvas->description }}</p>
            @endif
            <div class="d-flex items-center gap-3 mt-2 text-xs text-[var(--ui-muted)]">
                <span>{{ $canvas->canvasType?->name ?? 'Canvas' }}</span>
                <span>&middot;</span>
                <span>{{ $canvas->updated_at?->format('d.m.Y H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- Canvas Grid --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="space-y-4">
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
                <div class="grid gap-3" style="grid-template-columns: repeat({{ $columns }}, 1fr); grid-template-rows: {{ str_repeat('auto ', $rows) }}; grid-template-areas: {{ $cssAreas }};">
                    @foreach($blockDefs as $def)
                        @php
                            $blockKey = $def['key'];
                            $areaName = $areaMap[$blockKey] ?? null;
                            $blockData = $canvasData['blocks'][$blockKey] ?? null;
                            $blockCommentCount = $blockData ? $comments->where('building_block_id', $blockData['id'])->count() : 0;
                        @endphp
                        @if($areaName)
                        <div style="grid-area: {{ $areaName }};">
                            <div class="relative">
                                @include('canvas::livewire.canvas._block', ['blockKey' => $blockKey, 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                                @if($blockCommentCount > 0)
                                    <div class="absolute top-1 right-1 bg-blue-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                        {{ $blockCommentCount }}
                                    </div>
                                @endif
                                @if($blockData)
                                    <button
                                        wire:click="$set('commentBlockId', {{ $blockData['id'] }})"
                                        class="absolute bottom-1 right-1 opacity-0 hover:opacity-100 focus:opacity-100 transition-opacity p-1 rounded bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]"
                                        title="Kommentar zu diesem Block"
                                    >
                                        @svg('heroicon-o-chat-bubble-left', 'w-3.5 h-3.5')
                                    </button>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-{{ $columns }} gap-3">
                    @foreach($blockDefs as $def)
                        @php
                            $blockKey = $def['key'];
                            $blockData = $canvasData['blocks'][$blockKey] ?? null;
                            $blockCommentCount = $blockData ? $comments->where('building_block_id', $blockData['id'])->count() : 0;
                        @endphp
                        <div class="relative group">
                            @include('canvas::livewire.canvas._block', ['blockKey' => $blockKey, 'blocks' => $canvasData['blocks'], 'blockDefs' => $blockDefs])
                            @if($blockCommentCount > 0)
                                <div class="absolute top-1 right-1 bg-blue-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                    {{ $blockCommentCount }}
                                </div>
                            @endif
                            @if($blockData)
                                <button
                                    wire:click="$set('commentBlockId', {{ $blockData['id'] }})"
                                    class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity p-1 rounded bg-[var(--ui-surface)] border border-[var(--ui-border)]/60 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]"
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

        {{-- Comment Section --}}
        <div class="mt-8 max-w-3xl mx-auto">
            <h2 class="text-lg font-bold text-[var(--ui-secondary)] mb-4">Kommentare</h2>

            {{-- Comment Form --}}
            <div class="rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] p-4 mb-6">
                <form wire:submit="addComment">
                    @if($commentBlockId)
                        @php
                            $selectedBlock = $canvas->buildingBlocks->firstWhere('id', $commentBlockId);
                        @endphp
                        <div class="d-flex items-center gap-2 mb-3">
                            <x-ui-badge variant="info" size="sm">
                                @svg('heroicon-o-cube', 'w-3 h-3 mr-1')
                                {{ $selectedBlock?->label ?? 'Block' }}
                            </x-ui-badge>
                            <button type="button" wire:click="$set('commentBlockId', null)" class="text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                                &times; Entfernen
                            </button>
                        </div>
                    @else
                        <div class="mb-3">
                            <select wire:model="commentBlockId" class="text-xs rounded border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] text-[var(--ui-secondary)] px-2 py-1">
                                <option value="">Canvas-weiter Kommentar</option>
                                @foreach($canvas->buildingBlocks as $block)
                                    <option value="{{ $block->id }}">{{ $block->label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <textarea
                        wire:model="commentContent"
                        rows="3"
                        placeholder="Kommentar schreiben..."
                        class="w-full rounded-md border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] text-sm text-[var(--ui-secondary)] p-3 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                    @error('commentContent')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror

                    <div class="d-flex justify-end mt-2">
                        <x-ui-button type="submit" variant="primary" size="sm">
                            Kommentar senden
                        </x-ui-button>
                    </div>
                </form>
            </div>

            {{-- Comments List --}}
            <div class="space-y-3">
                @forelse($comments as $comment)
                    <div class="rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-surface)] p-4">
                        <div class="d-flex items-center gap-2 mb-2">
                            @if($comment->building_block_id)
                                <x-ui-badge variant="info" size="sm">
                                    @svg('heroicon-o-cube', 'w-3 h-3 mr-1')
                                    {{ $comment->buildingBlock?->label ?? 'Block' }}
                                </x-ui-badge>
                            @else
                                <x-ui-badge variant="secondary" size="sm">Canvas-weit</x-ui-badge>
                            @endif
                            <span class="text-[11px] text-[var(--ui-muted)]">{{ $comment->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-[var(--ui-secondary)] whitespace-pre-line">{{ $comment->content }}</p>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-sm text-[var(--ui-muted)]">Noch keine Kommentare vorhanden.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
