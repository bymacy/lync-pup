{{--
    Shared cohort filter dropdown — same look/behavior as the one built into
    the Dashboard's cohort selector and Founder Applications' own filter.

    Selecting an option here updates '?cohort=' on this page's URL, which
    ResolveSelectedCohort (see bootstrap/app.php's 'select-cohort' alias)
    picks up and stores as the admin's one, app-wide selected cohort — so it
    stays selected on every other module too, not just this page.

    Props:
      - cohorts: full Cohort collection (Active + Archived), e.g. Cohort::orderBy('number')->get()
      - selected: the currently selected cohort_id (nullable int)
      - clearParams: extra query params to null out alongside 'cohort' when switching
        (e.g. ['page'] so a filtered page number doesn't carry over to a shorter list)
--}}
@props(['cohorts', 'selected' => null, 'clearParams' => []])

@php
    $nullParams = collect($clearParams)->mapWithKeys(fn ($p) => [$p => null])->all();
    // "All Cohort" must produce an actual '?cohort=' in the URL (empty
    // string), not an absent key — http_build_query() silently drops null
    // values entirely, and ResolveSelectedCohort only clears a previously
    // selected cohort when the 'cohort' key is actually present on the
    // request. A dropped key would just leave whatever cohort was already
    // selected untouched instead of switching back to "All Cohort".
    $linkFor = fn ($cohortId) => request()->fullUrlWithQuery(['cohort' => $cohortId ?? '', ...$nullParams]);
    $activeCohorts = $cohorts->where('status', 'Active');
    $archivedCohorts = $cohorts->where('status', 'Inactive');
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} x-data="{ cohortFilterOpen: false }" @click.outside="cohortFilterOpen = false">
    <button type="button" @click="cohortFilterOpen = !cohortFilterOpen"
        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 shadow-sm">
        {{ $selected ? ($cohorts->firstWhere('cohort_id', $selected)?->display_label ?? 'All Cohort') : 'All Cohort' }}
        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 111.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="cohortFilterOpen" x-cloak
        class="absolute right-0 z-20 mt-2 rounded-xl border border-gray-100 bg-white shadow-xl"
        style="width: 260px;">
        <div class="py-2">
            <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-widest text-gray-400">Active</p>
            <a href="{{ $linkFor(null) }}"
                class="flex items-center justify-between px-4 py-2 text-sm transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white {{ ! $selected ? 'bg-blue-50 text-[#11386A] font-medium' : 'text-gray-700' }}">
                All Cohort
                @if (! $selected)
                    <svg class="h-4 w-4 text-[#11386A]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                @endif
            </a>
            @foreach ($activeCohorts as $c)
                <a href="{{ $linkFor($c->cohort_id) }}"
                    class="flex items-center justify-between px-4 py-2 text-sm transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white {{ $selected === $c->cohort_id ? 'bg-blue-50 text-[#11386A] font-medium' : 'text-gray-700' }}">
                    {{ $c->display_label }}
                    @if ($selected === $c->cohort_id)
                        <svg class="h-4 w-4 text-[#11386A]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                    @endif
                </a>
            @endforeach

            @if ($archivedCohorts->count())
                <div class="my-2 border-t border-gray-100"></div>
                <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-widest text-gray-400">Archived</p>
                @foreach ($archivedCohorts as $c)
                    <a href="{{ $linkFor($c->cohort_id) }}"
                        class="flex items-center justify-between px-4 py-2 text-sm transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white {{ $selected === $c->cohort_id ? 'bg-blue-50 text-[#11386A] font-medium' : 'text-gray-500' }}">
                        {{ $c->display_label }}
                        @if ($selected === $c->cohort_id)
                            <svg class="h-4 w-4 text-[#11386A]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</div>
