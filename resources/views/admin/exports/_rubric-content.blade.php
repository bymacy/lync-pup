{{--
    Shared content for the 8 TRL/MRL/TMRL/SRL rubric documents (Pre- and
    Post-Assessment). Expects: $startup, $type (TRL|MRL|TMRL|SRL),
    $stage (Pre-Assessment|Post-Assessment), $assessment (nullable
    ReadinessLevelAssessment for that stage).
--}}
@php
    $meta = \App\Support\ReadinessRubric::meta($stage)[$type];
    $levels = \App\Support\ReadinessRubric::levels($type);
    $progress = $assessment?->progressFor($type) ?? [];
    $score = $assessment?->scoreFor($type);
    $prefix = $stage === 'Post-Assessment' ? 'POST ' : '';
    $title = strtoupper($prefix.$meta['label']).' ('.$type.')';
    $v = fn ($val) => $val !== null && $val !== '' ? e($val) : '&nbsp;';
    $d = fn ($val) => $val ? \Illuminate\Support\Carbon::parse($val)->format('m/d/Y') : '&nbsp;';

    // Per-type signatory field mapping (mirrors the Assessment Hub blades).
    $signatories = match ($type) {
        'TRL' => [
            ['label' => 'Prepared By', 'name' => $assessment?->prepared_by, 'position' => $assessment?->prepared_by_position],
            ['label' => 'Noted By', 'name' => $assessment?->trl_noted_by, 'position' => $assessment?->trl_noted_by_position],
            ['label' => 'Approved by', 'name' => $assessment?->approved_by, 'position' => $assessment?->approved_by_position],
        ],
        'SRL' => [
            ['label' => 'Evaluated by', 'name' => $assessment?->srl_evaluated_by, 'position' => $assessment?->srl_evaluated_by_position],
            ['label' => 'Reviewed by', 'name' => $assessment?->srl_reviewed_by, 'position' => $assessment?->srl_reviewed_by_position],
            ['label' => 'Noted by', 'name' => $assessment?->srl_noted_by, 'position' => $assessment?->srl_noted_by_position],
        ],
        default => [
            ['label' => 'Evaluated by', 'name' => $assessment?->evaluated_by, 'position' => $assessment?->evaluated_by_position],
            ['label' => 'Reviewed by', 'name' => $assessment?->reviewed_by, 'position' => $assessment?->reviewed_by_position],
            ['label' => 'Noted by', 'name' => $assessment?->noted_by, 'position' => $assessment?->noted_by_position],
        ],
    };
@endphp

@include('admin.exports._letterhead', ['formNo' => $meta['form_no'], 'title' => $title])

<div class="field-row"><span class="field-label">Startup Name:</span> {!! $v($startup->company_name) !!}
    &nbsp;&nbsp;&nbsp;<span class="field-label">Date:</span> {!! $d($assessment?->assessment_date) !!}
    &nbsp;&nbsp;&nbsp;<span class="field-label">Score:</span> {!! $score !== null ? $score.'/9' : '&nbsp;' !!}
</div>

@if ($type === 'TRL' && $stage === 'Pre-Assessment')
    @php $overview = $assessment?->trl_overview ?? []; @endphp
    <div class="section-title">SECTION 1: STARTUP &amp; TECHNOLOGY OVERVIEW</div>
    <table style="margin-top: 6px;">
        <tr>
            <td width="50%">
                <div class="field-row"><span class="field-label">Founder:</span> {!! $v(data_get($overview, 'founder')) !!}</div>
                <div class="field-row"><span class="field-label">Tech Lead:</span> {!! $v(data_get($overview, 'tech_lead')) !!}</div>
                <div class="field-row"><span class="field-label">Contact Info:</span> {!! $v(data_get($overview, 'contact_info')) !!}</div>
                <div class="field-row"><span class="field-label">Brief Description:</span></div>
                <div style="border: 1px solid #9ca3af; padding: 5px; min-height: 24px;">{!! nl2br($v(data_get($overview, 'brief_description'))) !!}</div>
                <div class="field-row" style="margin-top: 4px;"><span class="field-label">Key Features:</span></div>
                <div style="border: 1px solid #9ca3af; padding: 5px; min-height: 24px;">{!! nl2br($v(data_get($overview, 'key_features'))) !!}</div>
            </td>
            <td width="50%">
                <div class="field-row"><span class="field-label">Industry Focus:</span></div>
                @foreach (\App\Support\TrlOverviewForm::INDUSTRY_FOCUS as $option)
                    <span class="checkbox">{{ in_array($option, data_get($overview, 'industry_focus', [])) ? 'X' : '' }}</span> {{ $option }}&nbsp;&nbsp;
                @endforeach

                <div class="field-row" style="margin-top: 6px;"><span class="field-label">Tech Stack:</span></div>
                @foreach (\App\Support\TrlOverviewForm::TECH_STACK_FIELDS as $key => $label)
                    <div class="field-row">&bull; {{ $label }}: {!! $v(data_get($overview, "tech_stack.$key")) !!}</div>
                @endforeach

                <div class="field-row" style="margin-top: 6px;"><span class="field-label">Technical Challenges &amp; Risks:</span></div>
                @foreach (\App\Support\TrlOverviewForm::TECHNICAL_CHALLENGES as $option)
                    <span class="checkbox">{{ in_array($option, data_get($overview, 'technical_challenges', [])) ? 'X' : '' }}</span> {{ $option }}&nbsp;&nbsp;
                @endforeach
            </td>
        </tr>
    </table>

    <table class="bordered" style="margin-top: 8px;">
        <tr><th>Tech Team Capability</th><th>Name</th></tr>
        @foreach (\App\Support\TrlOverviewForm::TECH_TEAM_ROLES as $role)
        <tr><td>{{ $role }}</td><td>{!! $v(data_get($overview, "tech_team.$role")) !!}</td></tr>
        @endforeach
    </table>

    <table style="margin-top: 8px;">
        <tr>
            <td width="50%">
                <div class="field-row"><span class="field-label">Tech Maturity Level:</span></div>
                @foreach (\App\Support\TrlOverviewForm::TEAM_MATURITY_LEVELS as $option)
                    <span class="checkbox">{{ data_get($overview, 'team_maturity_level') === $option ? 'X' : '' }}</span> {{ $option }}<br>
                @endforeach
            </td>
            <td width="50%">
                <div class="field-row"><span class="field-label">Testing Strategy:</span></div>
                @foreach (\App\Support\TrlOverviewForm::TESTING_STRATEGIES as $option)
                    <span class="checkbox">{{ in_array($option, data_get($overview, 'testing_strategies', [])) ? 'X' : '' }}</span> {{ $option }}<br>
                @endforeach
            </td>
        </tr>
    </table>

    <table style="margin-top: 8px;">
        <tr>
            <td width="50%">
                <div class="field-row"><span class="field-label">Topics of Interest:</span></div>
                @foreach (array_merge(\App\Support\TrlOverviewForm::TOPICS_OF_INTEREST_COLUMN_1, \App\Support\TrlOverviewForm::TOPICS_OF_INTEREST_COLUMN_2) as $option)
                    <span class="checkbox">{{ in_array($option, data_get($overview, 'topics_of_interest', [])) ? 'X' : '' }}</span> {{ $option }}&nbsp;&nbsp;
                @endforeach
            </td>
            <td width="50%">
                <div class="field-row"><span class="field-label">Mode of Communication:</span></div>
                @foreach (\App\Support\TrlOverviewForm::MODES_OF_COMMUNICATION as $option)
                    <span class="checkbox">{{ data_get($overview, 'mode_of_communication') === $option ? 'X' : '' }}</span> {{ $option }}&nbsp;&nbsp;
                @endforeach
            </td>
        </tr>
    </table>

    <div class="section-title">SECTION 2: {{ strtoupper($meta['label']) }} ({{ $type }})</div>
@endif

<table class="bordered" style="margin-top: 6px;">
    @foreach ($levels as $levelNum => $definition)
    <tr>
        <td style="background: #f3f4f6; font-weight: bold;" colspan="2">
            {{ $type }} {{ $levelNum }}{{ isset($definition['target']) ? ':' : ' –' }} {{ $definition['title'] }}
        </td>
    </tr>
    @if (isset($definition['target']))
    <tr><td colspan="2" style="font-style: italic; font-size: 10px;">Target: {{ $definition['target'] }}</td></tr>
    @endif
    @foreach ($definition['criteria'] as $i => $criterion)
    @php
        $checked = data_get($progress, "$levelNum.$i") ?? data_get($progress, "$levelNum")[$i] ?? false;
    @endphp
    <tr>
        <td width="20"><span class="checkbox">{{ $checked ? 'X' : '' }}</span></td>
        <td>{{ $criterion }}</td>
    </tr>
    @endforeach
    @endforeach
</table>

<table style="margin-top: 12px;">
    <tr>
        @foreach ($signatories as $sig)
        <td width="33%">
            <div style="font-size: 10px;">{{ $sig['label'] }}:</div>
            <div style="font-weight: bold; margin-top: 14px; border-top: 1px solid #4b5563; padding-top: 2px;">{!! $v($sig['name']) !!}</div>
            <div style="font-size: 9px; color: #6b7280;">{!! nl2br($v($sig['position'])) !!}</div>
        </td>
        @endforeach
    </tr>
</table>
