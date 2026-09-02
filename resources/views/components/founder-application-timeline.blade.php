@props(['startup'])

@php
    $founder = $startup->user;

    $steps = [
        [
            'title' => 'Account Activated',
            'time' => $founder->created_at,
            'desc' => 'Founder account activated',
            'status' => 'done',
        ],
        [
            'title' => 'Pending Review',
            'time' => $founder->created_at,
            'desc' => 'Application submitted and currently awaiting admin evaluation.',
            // This step is only ever "done" in the sense that it happened —
            // visually it reads as the in-progress stage until a final
            // decision is made, so it's styled the same as a pending step.
            'status' => 'pending',
        ],
    ];

    if ($founder->isApprovedAccount()) {
        $steps[] = [
            'title' => 'Approved',
            'time' => $startup->application_decided_at,
            'desc' => 'Application accepted.',
            'status' => 'done',
        ];
    } elseif ($founder->isRejected()) {
        $steps[] = [
            'title' => 'Rejected',
            'time' => $startup->application_decided_at,
            'desc' => 'Application rejected.',
            'status' => 'rejected',
        ];
    } else {
        $steps[] = [
            'title' => 'Final Decision',
            'time' => null,
            'desc' => 'Awaiting review completion.',
            'status' => 'pending',
        ];
    }

    // Icon + color per status, shared by the circular badge and the row tint.
    $styles = [
        'done' => ['row' => 'bg-green-50', 'badge' => 'bg-green-500', 'icon' => 'check'],
        'pending' => ['row' => 'bg-amber-50', 'badge' => 'bg-amber-500', 'icon' => 'clock'],
        'rejected' => ['row' => 'bg-red-50', 'badge' => 'bg-red-500', 'icon' => 'x'],
    ];
@endphp

<div class="space-y-2">
    @foreach ($steps as $step)
        @php $style = $styles[$step['status']]; @endphp
        <div class="flex items-start gap-3 rounded-lg px-4 py-3 {{ $style['row'] }}">
            <span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full {{ $style['badge'] }}">
                @if ($style['icon'] === 'check')
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 10.5l3 3 7-7" />
                    </svg>
                @elseif ($style['icon'] === 'clock')
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="10" cy="10" r="7.25" />
                        <path d="M10 6v4l2.5 2.5" />
                    </svg>
                @else
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 6l8 8M14 6l-8 8" />
                    </svg>
                @endif
            </span>

            <div class="min-w-0">
                <p class="font-bold text-sm text-gray-900">{{ $step['title'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $step['time']?->format('M j, Y g:i A') ?? '-----------------' }}
                    <span class="text-gray-600">&nbsp;&middot;&nbsp;{{ $step['desc'] }}</span>
                </p>
            </div>
        </div>
    @endforeach
</div>
