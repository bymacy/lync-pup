@props(['steps' => []])

@php
    // All positioning here is inline CSS, deliberately not Tailwind's
    // top-*/inset-* utilities — this app's compiled CSS only includes the
    // exact utility values already used elsewhere, and untested directional
    // offsets silently resolve to 0 instead of erroring (see the Risk
    // Monitoring donut chart fix for the full story). Inline styles sidestep
    // that entirely and are guaranteed to render regardless.
    $total = count($steps);
    $segments = max($total - 1, 1);
    $doneCount = collect($steps)->filter(fn ($s) => $s['state'] === 'done')->count();
    $progressPct = $total > 1 ? min($doneCount, $segments) / $segments * 100 : 0;
@endphp

<div class="relative" style="padding: 0 22px;">
    {{-- Base track + colored progress overlay, vertically centered on the
         44px circles (22px = half the circle height). --}}
    <div class="absolute" style="top: 22px; left: 22px; right: 22px; height: 2px; background-color: #E5E7EB;"></div>
    <div class="absolute" style="top: 22px; left: 22px; height: 2px; background-color: #6D0D23; width: calc((100% - 44px) * {{ $progressPct / 100 }});"></div>

    <div class="relative flex justify-between">
        @foreach ($steps as $step)
            <div class="flex flex-col items-center" style="width: 130px;">
                @if ($step['state'] === 'done')
                    <div class="flex items-center justify-center rounded-full bg-[#6D0D23] text-white shadow-sm" style="width: 44px; height: 44px;">
                        <svg style="width: 18px; height: 18px;" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 10.5l4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                @elseif ($step['state'] === 'current')
                    <div class="flex items-center justify-center rounded-full border-2 border-[#6D0D23] bg-white font-bold text-[#6D0D23]" style="width: 44px; height: 44px;">
                        {{ $step['number'] }}
                    </div>
                @else
                    <div class="flex items-center justify-center rounded-full border border-gray-300 bg-white font-semibold text-gray-400" style="width: 44px; height: 44px;">
                        {{ $step['number'] }}
                    </div>
                @endif

                <p class="mt-3 text-center text-sm font-semibold {{ $step['state'] === 'upcoming' ? 'text-gray-400' : 'text-gray-800' }}">
                    {{ $step['label'] }}
                </p>
            </div>
        @endforeach
    </div>
</div>
