@props(['blockKey', 'blockDef', 'block', 'canvasData', 'layout'])

@php
    $label = $blockDef['label'] ?? ucfirst(str_replace('_', ' ', $blockKey));
    $blockData = $canvasData['blocks'][$blockKey] ?? null;
    $entries = $blockData['entries'] ?? [];
    $map = is_array($layout['area_map'] ?? null) ? $layout['area_map'] : [];
    $hasAreaMap = !empty($map) && isset($map[$blockKey]);
    $areaKey = $map[$blockKey] ?? $blockKey;
@endphp

<div class="workshop-grid-block"
     data-block-id="{{ $block?->id }}"
     @if($hasAreaMap) style="grid-area: {{ $areaKey }}" @endif
>
    {{-- Header --}}
    <div class="workshop-grid-block-header">
        <h4>{{ $label }}</h4>
        <span class="entry-count">{{ count($entries) }}</span>
    </div>

    {{-- Body: compact entry list --}}
    <div class="workshop-grid-block-body">
        @if(empty($entries))
            <div class="empty-hint">Keine Eintraege</div>
        @else
            @foreach($entries as $entry)
                <div class="grid-entry">
                    @if(!empty($entry['title']))
                        <span class="grid-entry-title">{{ $entry['title'] }}</span>
                    @endif
                    @if(!empty($entry['content']))
                        <span class="grid-entry-content">{{ Str::limit($entry['content'], 80) }}</span>
                    @endif
                    @if(empty($entry['title']) && empty($entry['content']))
                        <span class="grid-entry-content" style="font-style: italic; opacity: 0.5;">Leer</span>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>
