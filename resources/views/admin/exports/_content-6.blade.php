@php
    $data = $document?->data ?? [];
    $v = fn ($val) => $val !== null && $val !== '' ? e($val) : '&nbsp;';
@endphp
@include('admin.exports._letterhead', ['formNo' => 'PUP-TBIDO FORM No. 006', 'title' => 'STARTUP GROWTH STRATEGY (DAGITAB PROGRAM)'])

<div class="field-row"><span class="field-label">Startup Name:</span> {!! $v($startup->company_name) !!}</div>
<div class="field-row">
    <span class="field-label">Business Stage:</span>
    @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_6_BUSINESS_STAGES as $stage)
        <span class="checkbox">{{ data_get($data, "business_stage.$stage") ? 'X' : '' }}</span> {{ $stage }}&nbsp;&nbsp;
    @endforeach
</div>

@foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_6_SECTIONS as $sectionKey => $section)
<div class="section-title">{{ $section['title'] }}</div>
<table class="bordered" style="margin-top: 4px;">
    <tr>
        @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_6_ROW_COLUMNS as $label)
        <th>{{ $label }}</th>
        @endforeach
    </tr>
    @forelse (data_get($data, $sectionKey, []) as $row)
    @if (collect($row)->filter()->isNotEmpty())
    <tr>
        @foreach (array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_6_ROW_COLUMNS) as $col)
        <td>{!! $v($row[$col] ?? null) !!}</td>
        @endforeach
    </tr>
    @endif
    @empty
    @endforelse
</table>
@endforeach

<table style="margin-top: 12px;">
    <tr>
        @foreach (data_get($data, 'prepared_by', []) as $person)
        <td width="25%">
            <div class="sig-label">Prepared By:</div>
            <div class="sig-name">{!! $v($person['name'] ?? null) !!}</div>
            <div class="sig-position">{!! nl2br($v($person['position'] ?? null)) !!}</div>
        </td>
        @endforeach
        <td width="25%">
            <div class="sig-label">Noted By:</div>
            <div class="sig-name">{!! $v(data_get($data, 'noted_by')) !!}</div>
            <div class="sig-position">{!! $v(data_get($data, 'noted_by_position')) !!}</div>
        </td>
    </tr>
</table>
