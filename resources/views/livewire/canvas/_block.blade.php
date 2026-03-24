@props(['blockKey', 'blocks', 'blockDefs'])

@php
    $block = $blocks[$blockKey] ?? null;
    $config = collect($blockDefs)->firstWhere('key', $blockKey) ?? [];
    $label = $config['label'] ?? ucfirst(str_replace('_', ' ', $blockKey));
    $entries = $block['entries'] ?? [];
    $entryCount = count($entries);
@endphp

<div class="rounded-xl border border-[var(--ui-border)]/50 bg-[var(--ui-surface)] flex flex-col h-full overflow-hidden shadow-sm">
    {{-- Header --}}
    <div class="d-flex items-center justify-between px-4 py-2.5 border-b border-[var(--ui-border)]/30 bg-[var(--ui-muted-5)]/30">
        <h4 class="text-xs font-bold text-[var(--ui-secondary)] uppercase tracking-wider truncate">{{ $label }}</h4>
        <span class="text-[10px] font-semibold text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-full px-2 py-0.5">{{ $entryCount }}</span>
    </div>

    {{-- Body --}}
    <div class="flex-grow-1 p-3 space-y-2">
        @if($entryCount > 0)
            @foreach($entries as $entry)
            <div class="p-2.5 rounded-lg bg-[var(--ui-bg)] border border-[var(--ui-border)]/20 hover:border-[var(--ui-border)]/40 transition-colors">
                <div class="d-flex items-start gap-2">
                    <div class="flex-grow-1 min-w-0">
                        @if(!empty($entry['title']))
                        <div class="text-xs font-semibold text-[var(--ui-secondary)] leading-tight">{{ $entry['title'] }}</div>
                        @endif
                        @if(!empty($entry['content']))
                        <div class="text-[11px] text-[var(--ui-muted)] mt-1 leading-relaxed">{{ Str::limit($entry['content'], 200) }}</div>
                        @endif
                    </div>
                    @if(($entry['entry_type'] ?? 'text') !== 'text')
                    <span class="flex-shrink-0 text-[9px] font-medium text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded px-1.5 py-0.5 uppercase tracking-wide">{{ $entry['entry_type'] }}</span>
                    @endif
                </div>
            </div>
            @endforeach
        @else
            <div class="py-6 text-center">
                <span class="text-[11px] text-[var(--ui-muted)]/60 italic">Keine Eintr&auml;ge</span>
            </div>
        @endif
    </div>
</div>
