@props(['startup'])

@php
    $palette = ['bg-purple-600', 'bg-red-600', 'bg-blue-600', 'bg-gray-100'];
    $bgClass = $palette[$startup->startup_id % count($palette)];

    $badgeClasses = match ($startup->status) {
        'Active', 'Assign Coordinator' => 'border-blue-300 text-blue-800',
        'Pending' => 'border-purple-300 text-purple-800',
        'Rejected' => 'border-red-300 text-red-800',
        default => 'border-gray-300 text-gray-700',
    };
@endphp

<div class="rounded-xl border border-gray-200 overflow-hidden flex flex-col">
    <div class="{{ $bgClass }} h-32 flex items-center justify-center relative">
        <span class="absolute top-3 right-3 text-xs font-medium bg-white border {{ $badgeClasses }} rounded-full px-3 py-1">
            {{ $startup->status }}
        </span>
    </div>

    <div class="p-4 flex-1 flex flex-col">
        <p class="font-bold text-gray-900">{{ $startup->company_name }}</p>
        <p class="text-sm text-gray-500 mb-2">{{ $startup->industry_sector }} · Cohort {{ $startup->cohort_number }}</p>
        <p class="text-sm text-gray-600 flex-1 line-clamp-3">{{ $startup->informationSheet?->business_description ?? 'No description submitted yet.' }}</p>

        <div class="flex items-center justify-between text-sm text-gray-500 mt-3 mb-4">
            <span>{{ $startup->location }}</span>
            @if ($startup->latestReadinessAssessment)
                <span>RLS {{ number_format($startup->latestReadinessAssessment->overall_score, 1) }}</span>
            @endif
        </div>

        <div class="flex gap-2 mt-auto">
            <a href="{{ route('admin.startups.show', $startup) }}"
               class="flex-1 text-center text-sm font-medium border border-rose-800 text-rose-900 rounded-lg py-2 hover:bg-rose-50">
                View
            </a>
            @if ($startup->status === 'Assign Coordinator')
                <a href="{{ route('admin.startups.show', $startup) }}#assign-coordinator"
                   class="flex-1 text-center text-sm font-medium bg-rose-900 text-white rounded-lg py-2 hover:bg-rose-950">
                    Assign Coordinator
                </a>
            @elseif ($startup->status === 'Pending')
                <a href="{{ route('admin.information-sheet.show', $startup) }}"
                   class="flex-1 text-center text-sm font-medium border border-blue-800 text-blue-900 rounded-lg py-2 hover:bg-blue-50">
                    View Information Sheet
                </a>
            @endif
        </div>
    </div>
</div>