@props(['startup'])

@php
    $founder = $startup->user;

    $steps = [
        [
            'title' => 'Account Activated',
            'time' => $founder->created_at,
            'desc' => 'Founder account activated',
            'color' => 'green',
        ],
        [
            'title' => 'Pending Review',
            'time' => $founder->created_at,
            'desc' => 'Application submitted and currently awaiting admin evaluation.',
            'color' => 'green',
        ],
    ];

    if ($founder->isApprovedAccount()) {
        $steps[] = [
            'title' => 'Approved',
            'time' => $startup->application_decided_at,
            'desc' => 'Application accepted.',
            'color' => 'green',
        ];
    } elseif ($founder->isRejected()) {
        $steps[] = [
            'title' => 'Rejected',
            'time' => $startup->application_decided_at,
            'desc' => 'Application rejected.',
            'color' => 'red',
        ];
    } else {
        $steps[] = [
            'title' => 'Final Decision',
            'time' => null,
            'desc' => 'Awaiting review completion.',
            'color' => 'pending',
        ];
    }
@endphp

<div class="border border-gray-200 rounded-lg p-4">
    @foreach ($steps as $step)
        <div class="flex gap-3 relative {{ ! $loop->last ? 'pb-5' : '' }}">
            @if (! $loop->last)
                <span class="absolute left-[7px] top-4 bottom-0 w-px bg-gray-200"></span>
            @endif

            <span class="mt-1 w-3.5 h-3.5 rounded-full flex-shrink-0
                {{ match ($step['color']) {
                    'green' => 'bg-green-600',
                    'red' => 'bg-red-600',
                    default => 'bg-gray-300',
                } }}"></span>

            <div class="min-w-0">
                <p class="font-bold text-sm text-gray-900">{{ $step['title'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $step['time']?->format('M j, Y g:i A') ?? '-----------------' }}
                    <span class="text-gray-600">&nbsp;&nbsp;{{ $step['desc'] }}</span>
                </p>
            </div>
        </div>
    @endforeach
</div>
