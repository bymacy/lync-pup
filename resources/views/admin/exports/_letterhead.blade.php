{{--
    Shared PUP-TBIDO letterhead used by every exported document.
    $formNo and $title are required. $instructions (optional array of
    strings) renders as bullet points under the title — used by forms that
    have their own "how to fill this out" notes (e.g. the Information
    Sheet).

    PUP seal (left) and "Bagong Pilipinas" logo (right) load from
    public/images/exports/pup-seal.png and bagong-pilipinas.png when
    present; the file_exists() checks keep this degrading gracefully if
    either file is ever removed.
--}}
@php $instructions = $instructions ?? []; @endphp
<table style="margin-bottom: 4px;">
    <tr>
        <td width="70" style="text-align: center; vertical-align: middle;">
            @if (file_exists(public_path('images/exports/pup-seal.png')))
                <img src="{{ public_path('images/exports/pup-seal.png') }}" style="width: 60px; height: 60px;">
            @endif
        </td>
        <td style="text-align: center; vertical-align: middle; line-height: 1.25;">
            <div style="font-size: 11px;">REPUBLIC OF THE PHILIPPINES</div>
            <div style="font-size: 14px; font-weight: bold;">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</div>
            <div style="font-size: 10px;">OFFICE OF THE VICE PRESIDENT FOR RESEARCH, EXTENSION, AND DEVELOPMENT</div>
            <div style="font-size: 11px; font-weight: bold;">TECHNOLOGY BUSINESS INCUBATION AND DEVELOPMENT OFFICE</div>
        </td>
        <td width="70" style="text-align: center; vertical-align: middle;">
            @if (file_exists(public_path('images/exports/bagong-pilipinas.png')))
                <img src="{{ public_path('images/exports/bagong-pilipinas.png') }}" style="width: 60px;">
            @endif
        </td>
    </tr>
</table>
<div style="border-top: 1px solid #000; margin-bottom: 8px;"></div>

<div style="font-style: italic; font-weight: bold; font-size: 10px;">{{ $formNo }}</div>
<div style="text-align: center; font-weight: bold; font-size: 13px; margin: 2px 0 8px;">{{ $title }}</div>

@if (count($instructions))
<ul style="margin: 0 0 8px 16px; padding: 0; font-size: 9px;">
    @foreach ($instructions as $line)
    <li style="margin-bottom: 2px;">{!! $line !!}</li>
    @endforeach
</ul>
@endif
