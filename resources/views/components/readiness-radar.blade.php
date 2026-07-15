@props(['trl' => 0, 'mrl' => 0, 'tmrl' => 0, 'srl' => 0, 'size' => 260])

@php
    $cx = $size / 2;
    $cy = $size / 2;
    $r = ($size / 2) - 40;

    $point = fn ($value, $angleDeg) => [
        $cx + $r * ($value / 9) * sin(deg2rad($angleDeg)),
        $cy - $r * ($value / 9) * cos(deg2rad($angleDeg)),
    ];

    [$topX, $topY] = $point($trl, 0);
    [$rightX, $rightY] = $point($mrl, 90);
    [$bottomX, $bottomY] = $point($tmrl, 180);
    [$leftX, $leftY] = $point($srl, 270);
@endphp

<svg viewBox="0 0 {{ $size }} {{ $size }}" class="w-full h-auto">
    @foreach ([0.33, 0.66, 1] as $ring)
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * $ring }}" fill="none" stroke="#e5e7eb" stroke-width="1" />
    @endforeach
    <line x1="{{ $cx }}" y1="{{ $cy - $r }}" x2="{{ $cx }}" y2="{{ $cy + $r }}" stroke="#e5e7eb" />
    <line x1="{{ $cx - $r }}" y1="{{ $cy }}" x2="{{ $cx + $r }}" y2="{{ $cy }}" stroke="#e5e7eb" />

    <polygon points="{{ $topX }},{{ $topY }} {{ $rightX }},{{ $rightY }} {{ $bottomX }},{{ $bottomY }} {{ $leftX }},{{ $leftY }}"
             fill="#7f1d3a" fill-opacity="0.2" stroke="#7f1d3a" stroke-width="2" />

    <text x="{{ $cx }}" y="{{ $cy - $r - 12 }}" text-anchor="middle" class="fill-gray-500 text-xs">TRL {{ $trl }}/9</text>
    <text x="{{ $cx + $r + 12 }}" y="{{ $cy + 4 }}" text-anchor="start" class="fill-gray-500 text-xs">MRL {{ $mrl }}/9</text>
    <text x="{{ $cx }}" y="{{ $cy + $r + 22 }}" text-anchor="middle" class="fill-gray-500 text-xs">TMRL {{ $tmrl }}/9</text>
    <text x="{{ $cx - $r - 12 }}" y="{{ $cy + 4 }}" text-anchor="end" class="fill-gray-500 text-xs">SRL {{ $srl }}/9</text>
</svg>