@props(['startup'])

@php
// --- unchanged functional logic ---------------------------------------
$palette = ['bg-purple-600', 'bg-red-600', 'bg-blue-600', 'bg-gray-100'];
$bgClass = $palette[$startup->startup_id % count($palette)];

// Badge color now keyed per-status so "Assign Coordinator" reads as the
// same rose/brand accent used on its primary button below (matches the
// reference exactly), while other statuses keep their own identity.
$badgeClasses = match ($startup->status) {
'Active' => 'border-blue-300 text-blue-800',
'Assign Coordinator' => 'border-rose-300 text-rose-800',
'Pending' => 'border-purple-300 text-purple-800',
'Rejected' => 'border-red-300 text-red-800',
default => 'border-gray-300 text-gray-700',
};

// --- presentation-only additions (purely derived, no new data) --------
// Icon shown inside the banner. Purely decorative — keyed off the same
// index used for $bgClass so it stays deterministic per startup.
$icons = [
// purple
'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
    <rect x="5" y="6" width="14" height="3" rx="1" />
    <rect x="7" y="11" width="10" height="3" rx="1" />
    <rect x="9" y="16" width="6" height="3" rx="1" />
</svg>',
// red
'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
    <path d="M12 3c-3.9 0-7 3-7 7v7.5c.6-.7 1.2-.7 1.8 0s1.2.7 1.8 0 1.2-.7 1.8 0 1.2.7 1.8 0 1.2-.7 1.8 0V10c0-4-3.1-7-7-7z" />
    <circle cx="9.5" cy="10" r="1" />
    <circle cx="14.5" cy="10" r="1" />
</svg>',
// blue
'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
    <path d="M4 12h2l2-6 3 12 2-9 2 6h5" />
</svg>',
// gray/plain — interlocking chain-link mark
'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="3" width="8" height="8" rx="2.5" />
    <rect x="13" y="13" width="8" height="8" rx="2.5" />
    <path d="M9.5 9.5l1.8 1.8M14.5 14.5l-1.8-1.8" />
</svg>',
];
$iconMarkup = $icons[$startup->startup_id % count($icons)];
$iconTone = $bgClass === 'bg-gray-100' ? 'text-blue-600' : 'text-white';

$hasSecondAction = in_array($startup->status, ['Assign Coordinator', 'Pending']);
@endphp

<div class="flex w-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-shadow duration-150 hover:shadow-md">

    {{-- Banner --}}
    <div class="{{ $bgClass }} relative flex h-28 items-center justify-center">
        <span class="absolute right-2.5 top-2.5 rounded-full border bg-white px-2.5 py-1 text-[10px] font-semibold {{ $badgeClasses }}">
            {{ $startup->status }}
        </span>
        <span class="[&>svg]:h-10 [&>svg]:w-10 {{ $iconTone }}">
            {!! $iconMarkup !!}
        </span>
    </div>

    {{-- Body --}}
    <div class="flex flex-1 flex-col gap-2 p-3.5">
        <div>
            <p class="text-sm font-bold leading-snug text-gray-900">{{ $startup->company_name }}</p>
            <p class="text-[11px] text-gray-500">{{ $startup->industry_sector }} &middot; Cohort {{ $startup->cohort_number }}</p>
        </div>

        <p class="min-h-[1.75rem] flex-1 text-[11px] leading-relaxed text-gray-500 line-clamp-2">
            {{ $startup->informationSheet?->business_description ?? 'No description submitted yet.' }}
        </p>

        <div class="flex items-center justify-between gap-2 text-[11px] text-gray-500">
            <span class="flex min-w-0 items-center gap-1">
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3 shrink-0 text-gray-400">
                    <path fill-rule="evenodd" d="M9.69 18.933a.75.75 0 0 0 .62 0c.058-.026 8.19-3.86 8.19-9.933a8.5 8.5 0 1 0-17 0c0 6.073 8.132 9.907 8.19 9.933ZM10 12.5A3 3 0 1 0 10 6.5a3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                </svg>
                <span class="truncate">{{ $startup->location }}</span>
            </span>

            @if ($startup->latestReadinessAssessment)
            <span class="flex shrink-0 items-center gap-1 font-semibold text-emerald-600">
                <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                    <path fill-rule="evenodd" d="M12 5a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0V6.81l-5.22 5.22a.75.75 0 0 1-1.06 0L7.5 9.06l-4.72 4.72a.75.75 0 0 1-1.06-1.06l5.25-5.25a.75.75 0 0 1 1.06 0l2.97 2.97L16.19 5.75h-3.44A.75.75 0 0 1 12 5Z" clip-rule="evenodd" />
                </svg>
                RLS {{ number_format($startup->latestReadinessAssessment->overall_score, 1) }}
            </span>
            @endif
        </div>

        {{--
            grid-cols-2 (not flex + flex-1) keeps both buttons exactly equal
            width no matter how long a label is — flex-1 alone lets long text
            refuse to shrink below its min-content size. Falls back to a
            single full-width column when there's no second action.
        --}}
        <div class="mt-auto grid {{ $hasSecondAction ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 pt-1">
            <a href="{{ route('admin.startups.show', $startup) }}"
                class="flex min-h-[2rem] items-center justify-center rounded-lg border border-rose-800 px-2 text-center text-xs font-semibold leading-tight text-rose-900 transition-colors hover:bg-rose-50">
                View
            </a>

            @if ($startup->status === 'Assign Coordinator')
            <a href="{{ route('admin.startups.show', $startup) }}#assign-coordinator"
                class="flex min-h-[2rem] items-center justify-center rounded-lg bg-gradient-to-r from-[#6D0D23] via-[#43306A] to-[#11386A] px-2 text-center text-xs font-semibold leading-tight text-white shadow-sm transition-all duration-300 hover:brightness-110 hover:shadow-md">
                Assign Coordinator
            </a>

            @elseif ($startup->status === 'Pending')
            <a href="{{ route('admin.information-sheet.show', $startup) }}"
                class="flex min-h-[2rem] items-center justify-center rounded-lg border border-blue-800 px-2 text-center text-xs font-semibold leading-tight text-blue-900 transition-colors hover:bg-blue-50">
                View Information Sheet
            </a>
            @endif
        </div>
    </div>
</div>