{{--
    Shared PUP-TBIDO letterhead + footer used by every exported document.
    $formNo and $title are required; everything else is page content passed
    via the slot.
--}}
<div style="text-align: center; font-size: 10px; line-height: 1.3; margin-bottom: 10px;">
    <div>REPUBLIC OF THE PHILIPPINES</div>
    <div style="font-weight: bold;">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</div>
    <div>OFFICE OF THE VICE PRESIDENT FOR RESEARCH, EXTENSION, AND DEVELOPMENT</div>
    <div>TECHNOLOGY BUSINESS INCUBATION AND DEVELOPMENT OFFICE</div>
</div>

<div style="text-align: center; margin-bottom: 4px;">
    <span style="border: 1px solid #6D0D23; color: #6D0D23; font-size: 10px; font-style: italic; padding: 3px 10px; border-radius: 4px;">
        {{ $formNo }}
    </span>
</div>

<h1 style="text-align: center; font-size: 15px; color: #11386A; margin: 6px 0 14px;">{{ $title }}</h1>
