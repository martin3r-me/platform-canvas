@props(['blockKey', 'blockDef', 'block', 'canvasData', 'placement'])

@php
    $label = $blockDef['label'] ?? ucfirst(str_replace('_', ' ', $blockKey));
    $description = $blockDef['description'] ?? '';
    $guidingQuestions = $blockDef['guiding_questions'] ?? [];
    $blockData = $canvasData['blocks'][$blockKey] ?? null;
    $entries = $blockData['entries'] ?? [];

    // Grid placement via grid-column/grid-row
    $gridStyle = '';
    if ($placement) {
        $col = $placement['col'];
        $row = $placement['row'];
        $colspan = $placement['colspan'] ?? 1;
        $rowspan = $placement['rowspan'] ?? 1;
        $gridStyle = "grid-column: {$col} / span {$colspan}; grid-row: {$row} / span {$rowspan};";
    }

    // Icon mapping for known block types
    $iconMap = [
        'key_partners' => 'heroicon-o-link',
        'key_activities' => 'heroicon-o-check-badge',
        'key_resources' => 'heroicon-o-wrench-screwdriver',
        'value_propositions' => 'heroicon-o-gift',
        'customer_relationships' => 'heroicon-o-heart',
        'channels' => 'heroicon-o-megaphone',
        'customer_segments' => 'heroicon-o-user-group',
        'cost_structure' => 'heroicon-o-calculator',
        'revenue_streams' => 'heroicon-o-banknotes',
        'strengths' => 'heroicon-o-bolt',
        'weaknesses' => 'heroicon-o-exclamation-triangle',
        'opportunities' => 'heroicon-o-arrow-trending-up',
        'threats' => 'heroicon-o-shield-exclamation',
        'problem' => 'heroicon-o-exclamation-circle',
        'solution' => 'heroicon-o-light-bulb',
        'key_metrics' => 'heroicon-o-chart-bar',
        'unique_value_proposition' => 'heroicon-o-star',
        'unfair_advantage' => 'heroicon-o-trophy',
        'project_goal' => 'heroicon-o-flag',
        'scope' => 'heroicon-o-viewfinder-circle',
        'stakeholders' => 'heroicon-o-users',
        'risks' => 'heroicon-o-exclamation-triangle',
        'milestones' => 'heroicon-o-calendar-days',
        'resources' => 'heroicon-o-cube',
        'budget' => 'heroicon-o-currency-euro',
        'communication' => 'heroicon-o-chat-bubble-left-right',
        'governance' => 'heroicon-o-building-library',
        // COREFIT Process blocks
        'target_description' => 'heroicon-o-flag',
        'value_proposition' => 'heroicon-o-gift',
        'cost_analysis' => 'heroicon-o-calculator',
        'risk_assessment' => 'heroicon-o-shield-exclamation',
        'improvement_levers' => 'heroicon-o-wrench-screwdriver',
        'action_plan' => 'heroicon-o-clipboard-document-check',
        'standardization_notes' => 'heroicon-o-document-check',
        'process_landscape' => 'heroicon-o-map',
        'corefit_classification_notes' => 'heroicon-o-adjustments-horizontal',
    ];
    $icon = $iconMap[$blockKey] ?? 'heroicon-o-square-3-stack-3d';
@endphp

<div class="workshop-grid-block"
     data-block-id="{{ $block?->id }}"
     @if($gridStyle) style="{{ $gridStyle }}" @endif
>
    {{-- Header: Title + Icon --}}
    <div class="workshop-grid-block-header">
        <h4>{{ $label }}</h4>
        @svg($icon, 'w-5 h-5 text-gray-300')
    </div>

    {{-- Body --}}
    <div class="workshop-grid-block-body">
        {{-- Guiding Questions --}}
        @if(!empty($guidingQuestions))
            <div class="guiding-questions">
                @foreach($guidingQuestions as $question)
                    <div class="guiding-question">{{ $question }}</div>
                @endforeach
            </div>
        @endif

        {{-- Entries --}}
        @if(!empty($entries))
            <div class="grid-entries">
                @foreach($entries as $entry)
                    <div class="grid-entry">
                        @if(!empty($entry['title']))
                            <span class="grid-entry-title">{{ $entry['title'] }}</span>
                        @endif
                        @if(!empty($entry['content']))
                            <span class="grid-entry-content">{{ $entry['content'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
