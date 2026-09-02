@php $instructions = $instructions ?? []; @endphp

<table style="
    margin-bottom: 8px;
    margin-left: -12px;
    margin-right: -12px;
    width: 727px;
    border-collapse: collapse;
">
    <tr>
        {{-- PUP SEAL --}}
        <td style="
            width: 86px;
            text-align: left;
            vertical-align: middle;
            padding: 0;
        ">
            @if (file_exists(public_path('images/exports/pup-seal.png')))
            <img
                src="{{ public_path('images/exports/pup-seal.png') }}"
                style="
                        width: 98px;
                        height: 98px;
                        display: block;
                        transform: translateX(8px);
                    ">
            @endif
        </td>

        {{-- HEADER TEXT --}}
        <td style="
    width: 547px;
    text-align: left;
    vertical-align: middle;
    padding: 0;
    font-family: 'Cambria', serif;
    line-height: 1;
    transform: translateX(25px);
">

            <div style="
        font-size: 9pt;
        font-weight: normal;
        margin: 0 0 2px 0;
        white-space: nowrap;
    ">
                REPUBLIC OF THE PHILIPPINES
            </div>

            <div style="
        font-size: 11pt;
        font-weight: bold;
        margin: 0 0 2px 0;
        white-space: nowrap;
    ">
                POLYTECHNIC UNIVERSITY OF THE PHILIPPINES
            </div>

            <div style="
        font-size: 10pt;
        font-weight: normal;
        margin: 0 0 2px 0;
        white-space: nowrap;
    ">
                OFFICE OF THE VICE PRESIDENT FOR RESEARCH, EXTENSION, AND DEVELOPMENT
            </div>

            <div style="
        font-size: 12pt;
        font-weight: bold;
        margin: 0;
        white-space: nowrap;
    ">
                TECHNOLOGY BUSINESS INCUBATION AND DEVELOPMENT OFFICE
            </div>
        </td>

        {{-- BAGONG PILIPINAS --}}
        <td style="
    width: 98px;
    text-align: right;
    vertical-align: middle;
    padding: 0;
">
            @if (file_exists(public_path('images/exports/bagong-pilipinas.png')))
            <img
                src="{{ public_path('images/exports/bagong-pilipinas.png') }}"
                style="
                width: 107px;
                display: block;
                margin-left: auto;
                transform: translate(-17px, -8px);
            ">
            @endif
        </td>
    </tr>
</table>

{{-- HORIZONTAL LINE --}}
<div style="
    border-top: 1px solid #000;
    margin-bottom: 8px;
"></div>


{{-- FORM NUMBER --}}
<div style="
    font-style: italic;
    font-weight: bold;
    font-size: 10pt;
    margin: 0;
">
    {{ $formNo }}
</div>


{{-- TITLE --}}
<div style="
    text-align: center;
    font-weight: bold;
    font-size: 13pt;
    margin: 2px 0 8px 0;
">
    {{ $title }}
</div>


{{-- INSTRUCTIONS --}}
@if (count($instructions))
<ul style="
        margin: 0 0 8px 16px;
        padding: 0;
        font-size: 9pt;
    ">
    @foreach ($instructions as $line)
    <li style="
                margin-bottom: 2px;
            ">
        {!! $line !!}
    </li>
    @endforeach
</ul>
@endif