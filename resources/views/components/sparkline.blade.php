@props(['points' => [], 'color' => '#6D0D23'])

@php
    // Minimal decorative trend line for the Dashboard stat cards. No chart
    // library exists in this app (see risk-monitoring's conic-gradient
    // donuts), so this is a hand-rolled normalized polyline. viewBox is
    // fixed at 100x32 with preserveAspectRatio="none" so it stretches to
    // fill whatever width the card gives it via the wrapping <svg> tag's
    // own w-full class, without needing any risky Tailwind height utility.
    $data = count($points) ? array_values($points) : [0];
    $max = max($data);
    $min = min($data);
    $range = max(1, $max - $min);
    $n = count($data);
    $coords = collect($data)->map(function ($v, $i) use ($n, $min, $range) {
        $x = $n > 1 ? ($i / ($n - 1)) * 100 : 0;
        $y = 30 - (($v - $min) / $range) * 28;
        return "{$x},{$y}";
    })->implode(' ');
@endphp

<svg viewBox="0 0 100 32" preserveAspectRatio="none" class="w-full" style="height: 36px;">
    <polyline points="{{ $coords }}" fill="none" stroke="{{ $color }}" stroke-width="2.5"
        stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
</svg>
