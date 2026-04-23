@props(['blockKey', 'blockDef', 'block', 'entries', 'layout'])

@php
    $label = $blockDef['label'] ?? ucfirst(str_replace('_', ' ', $blockKey));
    $blockEntries = collect($entries)->where('block_key', $blockKey)->values();
    $blockModel = $block;
    $map = is_array($layout['area_map'] ?? null) ? $layout['area_map'] : [];
    $areaKey = $map[$blockKey] ?? $blockKey;
@endphp

<div class="workshop-block" style="grid-area: {{ $areaKey }}">
    {{-- Header --}}
    <div class="workshop-block-header">
        <div class="flex items-center gap-2">
            <h4>{{ $label }}</h4>
            <span class="count-badge">{{ $blockEntries->count() }}</span>
        </div>
        <button class="add-btn" x-on:click="addNote('{{ $blockKey }}')" title="Notiz hinzufuegen">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
            </svg>
        </button>
    </div>

    {{-- Body (dropzone) --}}
    <div class="workshop-block-body" data-block-id="{{ $blockModel?->id }}">
        @if($blockEntries->isEmpty())
            <div class="empty-state">Klicke + um eine Notiz zu erstellen</div>
        @endif

        @foreach($blockEntries as $entry)
            @php
                $meta = $entry['metadata'] ?? [];
                $x = $meta['x'] ?? (20 + $loop->index * 30);
                $y = $meta['y'] ?? (20 + $loop->index * 30);
                $w = $meta['width'] ?? 200;
                $h = $meta['height'] ?? 150;
                $color = $meta['color'] ?? 'yellow';
            @endphp
            <div class="workshop-note workshop-note-{{ $color }}"
                 data-entry-id="{{ $entry['id'] }}"
                 data-x="{{ $x }}"
                 data-y="{{ $y }}"
                 style="width: {{ $w }}px; height: {{ $h }}px; transform: translate({{ $x }}px, {{ $y }}px);">

                {{-- Drag Handle --}}
                <div class="drag-handle">
                    <div class="flex items-center gap-1.5">
                        <div class="drag-dots">
                            <span></span><span></span><span></span>
                            <span></span><span></span><span></span>
                        </div>
                        {{-- Color dot --}}
                        <div class="relative" x-on:click.stop>
                            <div class="color-dot"
                                 style="background: {{ match($color) {
                                     'yellow' => '#fbbf24',
                                     'blue' => '#60a5fa',
                                     'green' => '#4ade80',
                                     'pink' => '#f472b6',
                                     'purple' => '#a78bfa',
                                     'orange' => '#fb923c',
                                     'teal' => '#2dd4bf',
                                     'red' => '#f87171',
                                     default => '#fbbf24',
                                 } }}"
                                 x-on:click="toggleColorPicker({{ $entry['id'] }})"
                            ></div>
                            {{-- Color Picker Dropdown --}}
                            <div x-show="colorPickerOpen === {{ $entry['id'] }}"
                                 x-on:click.outside="colorPickerOpen = null"
                                 x-transition
                                 class="absolute top-full left-0 mt-1 flex gap-1 p-1.5 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                <template x-for="c in colors" :key="c">
                                    <div class="color-dot"
                                         :class="{ 'active': '{{ $color }}' === c }"
                                         :style="'background:' + ({yellow:'#fbbf24',blue:'#60a5fa',green:'#4ade80',pink:'#f472b6',purple:'#a78bfa',orange:'#fb923c',teal:'#2dd4bf',red:'#f87171'}[c])"
                                         x-on:click="changeColor({{ $entry['id'] }}, c)"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </div>
                    {{-- Delete --}}
                    <button class="note-delete"
                            x-on:click.stop="if(confirm('Notiz loeschen?')) deleteNote({{ $entry['id'] }})"
                            title="Loeschen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="note-body">
                    <input type="text"
                           value="{{ $entry['title'] }}"
                           placeholder="Titel..."
                           x-on:blur="updateNoteText({{ $entry['id'] }}, $event.target.value, $event.target.closest('.note-body').querySelector('textarea').value)"
                           x-on:keydown.enter="$event.target.blur()"
                    />
                    <textarea
                        placeholder="Notiz..."
                        x-on:blur="updateNoteText({{ $entry['id'] }}, $event.target.closest('.note-body').querySelector('input').value, $event.target.value)"
                    >{{ $entry['content'] }}</textarea>
                </div>

                {{-- Resize Handle --}}
                <div class="resize-handle"></div>
            </div>
        @endforeach
    </div>
</div>
