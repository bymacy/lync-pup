<x-layouts.admin title="Dashboard">

    @php
        // --- Incubation Progress donut (0-5 scale, see DashboardController) ---
        $incubationTotal = max($incubationProgress['total'], 1);
        $incubationActive = collect($incubationProgress['breakdown'])->filter(fn ($b) => $b['count'] > 0)->values();
        $gapDeg1 = $incubationActive->count() > 1 ? 5 : 0;
        $availableDeg1 = 360 - ($gapDeg1 * $incubationActive->count());
        $cursor1 = 0;
        $segments1 = [];
        foreach ($incubationActive as $b) {
            $sliceDeg = ($b['count'] / $incubationTotal) * $availableDeg1;
            $start = $cursor1;
            $end = $start + $sliceDeg;
            $segments1[] = "{$b['color']} {$start}deg {$end}deg";
            $segments1[] = 'white ' . $end . 'deg ' . ($end + $gapDeg1) . 'deg';
            $cursor1 = $end + $gapDeg1;
        }
        $incubationGradient = $segments1 ? 'conic-gradient(' . implode(', ', $segments1) . ')' : '#E5E7EB';

        // --- Risk Classification donut ---
        $riskTotal = max($riskClassification['total'], 1);
        $riskActive = collect($riskClassification['breakdown'])->filter(fn ($b) => $b['count'] > 0)->values();
        $gapDeg2 = $riskActive->count() > 1 ? 5 : 0;
        $availableDeg2 = 360 - ($gapDeg2 * $riskActive->count());
        $cursor2 = 0;
        $segments2 = [];
        foreach ($riskActive as $b) {
            $sliceDeg = ($b['count'] / $riskTotal) * $availableDeg2;
            $start = $cursor2;
            $end = $start + $sliceDeg;
            $segments2[] = "{$b['color']} {$start}deg {$end}deg";
            $segments2[] = 'white ' . $end . 'deg ' . ($end + $gapDeg2) . 'deg';
            $cursor2 = $end + $gapDeg2;
        }
        $riskGradient = $segments2 ? 'conic-gradient(' . implode(', ', $segments2) . ')' : '#E5E7EB';

        // Re-open the create/edit modal after a failed validation redirect,
        // so the user's input + error messages aren't stranded behind a
        // closed modal.
        $reopenModal = null;
        if ($errors->any()) {
            $reopenModal = old('_method') === 'PATCH' ? 'edit' : 'create';
        }
    @endphp

    <div x-data="{
            cohortMenuOpen: false,
            actionsMenuOpen: false,
            modal: {{ $reopenModal ? "'{$reopenModal}'" : 'null' }},
            successModal: {{ session('cohortAction') ? "'" . session('cohortAction') . "'" : 'null' }},
            archiveConfirm: '',
            deleteConfirm: '',
        }">

        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-500 mt-2 text-base">Overview of intervention, sheets, request, updates, and mentor coordination</p>
            </div>

            <div class="flex items-center gap-3 ml-auto">
                {{-- Cohort selector dropdown --}}
                <div class="relative" @click.outside="cohortMenuOpen = false">
                    <button type="button" @click="cohortMenuOpen = !cohortMenuOpen"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 shadow-sm">
                        {{ $selectedCohort ? $selectedCohort->display_label : 'All Cohort' }}
                        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 111.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="cohortMenuOpen" x-cloak
                        class="absolute right-0 z-20 mt-2 rounded-xl border border-gray-100 bg-white shadow-xl"
                        style="width: 260px;">
                        <div class="py-2">
                            <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-widest text-gray-400">Active</p>
                            <a href="{{ request()->fullUrlWithQuery(['cohort' => null]) }}"
                                class="flex items-center justify-between px-4 py-2 text-sm transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white {{ ! $selectedCohort ? 'bg-blue-50 text-[#11386A] font-medium' : 'text-gray-700' }}">
                                All Cohort
                                @if (! $selectedCohort)
                                    <svg class="h-4 w-4 text-[#11386A]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                @endif
                            </a>
                            @foreach ($cohorts->where('status', 'Active') as $c)
                                <a href="{{ request()->fullUrlWithQuery(['cohort' => $c->cohort_id]) }}"
                                    class="flex items-center justify-between px-4 py-2 text-sm transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white {{ $selectedCohort && $selectedCohort->cohort_id === $c->cohort_id ? 'bg-blue-50 text-[#11386A] font-medium' : 'text-gray-700' }}">
                                    {{ $c->display_label }}
                                    @if ($selectedCohort && $selectedCohort->cohort_id === $c->cohort_id)
                                        <svg class="h-4 w-4 text-[#11386A]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                    @endif
                                </a>
                            @endforeach

                            @if ($cohorts->where('status', 'Inactive')->count())
                                <div class="my-2 border-t border-gray-100"></div>
                                <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-widest text-gray-400">Archived</p>
                                @foreach ($cohorts->where('status', 'Inactive') as $c)
                                    <a href="{{ request()->fullUrlWithQuery(['cohort' => $c->cohort_id]) }}"
                                        class="flex items-center justify-between px-4 py-2 text-sm transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white {{ $selectedCohort && $selectedCohort->cohort_id === $c->cohort_id ? 'bg-blue-50 text-[#11386A] font-medium' : 'text-gray-500' }}">
                                        {{ $c->display_label }}
                                        @if ($selectedCohort && $selectedCohort->cohort_id === $c->cohort_id)
                                            <svg class="h-4 w-4 text-[#11386A]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                        @endif
                                    </a>
                                @endforeach
                            @endif

                            <div class="my-2 border-t border-gray-100"></div>
                            <button type="button" @click="modal = 'create'; cohortMenuOpen = false"
                                class="flex w-full items-center gap-2 px-4 py-2 text-sm font-medium text-[#6D0D23] transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                                Create New Cohort
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Three-dot cohort actions menu --}}
                <div class="relative" @click.outside="actionsMenuOpen = false">
                    <button type="button" @click="actionsMenuOpen = !actionsMenuOpen"
                        class="flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm"
                        style="width: 38px; height: 38px;">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 6a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4z" />
                        </svg>
                    </button>

                    <div x-show="actionsMenuOpen" x-cloak
                        class="absolute right-0 z-20 mt-2 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl"
                        style="width: 240px;">
                        @php
                            $hasCohort = (bool) $selectedCohort;
                            $isArchivedCohort = $selectedCohort?->isArchived() ?? false;
                            $canArchive = $hasCohort && ! $isArchivedCohort;
                        @endphp
                        <div class="py-1">
                            <button type="button" @click="modal = 'create'; actionsMenuOpen = false"
                                class="flex w-full items-center gap-2 px-4 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                                Create New Cohort
                            </button>
                            <button type="button" @click="if (({{ $hasCohort ? 'true' : 'false' }})) { modal = 'edit'; actionsMenuOpen = false }"
                                {{ $hasCohort ? '' : 'disabled' }}
                                class="flex w-full items-center gap-2 px-4 py-3 text-sm font-medium transition-colors {{ $hasCohort ? 'text-gray-700 hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white' : 'text-gray-300 cursor-not-allowed' }}">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-8.5 8.5a2 2 0 01-.878.507l-3 .75a.5.5 0 01-.606-.606l.75-3a2 2 0 01.507-.878l8.5-8.5z" /></svg>
                                Edit Cohort
                            </button>
                            <button type="button" @click="if (({{ $hasCohort ? 'true' : 'false' }})) { modal = 'details'; actionsMenuOpen = false }"
                                {{ $hasCohort ? '' : 'disabled' }}
                                class="flex w-full items-center gap-2 px-4 py-3 text-sm font-medium transition-colors {{ $hasCohort ? 'text-gray-700 hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white' : 'text-gray-300 cursor-not-allowed' }}">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3.5c-4.14 0-7.5 3.5-8.5 6.5 1 3 4.36 6.5 8.5 6.5s7.5-3.5 8.5-6.5c-1-3-4.36-6.5-8.5-6.5zm0 10.5a4 4 0 110-8 4 4 0 010 8z" /><circle cx="10" cy="10" r="1.8" /></svg>
                                View Cohort Details
                            </button>
                            <button type="button" @click="if (({{ $canArchive ? 'true' : 'false' }})) { modal = 'archive'; actionsMenuOpen = false }"
                                {{ $canArchive ? '' : 'disabled' }}
                                class="flex w-full items-center gap-2 px-4 py-3 text-sm font-medium transition-colors {{ $canArchive ? 'text-gray-700 hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white' : 'text-gray-300 cursor-not-allowed' }}">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1v7a2 2 0 01-2 2H6a2 2 0 01-2-2V7a1 1 0 01-1-1V4zm3 4v6h8V8H6zm2 2h4v1H8v-1z" /></svg>
                                Archive / End Cohort
                            </button>
                            <button type="button" @click="if (({{ $hasCohort ? 'true' : 'false' }})) { modal = 'delete'; actionsMenuOpen = false }"
                                {{ $hasCohort ? '' : 'disabled' }}
                                class="flex w-full items-center gap-2 px-4 py-3 text-sm font-medium transition-colors {{ $hasCohort ? 'text-rose-600 hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white' : 'text-gray-300 cursor-not-allowed' }}">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 2a1 1 0 00-1 1v1H4a1 1 0 100 2h12a1 1 0 100-2h-3V3a1 1 0 00-1-1H8zM5 7a1 1 0 011 1v8a2 2 0 002 2h4a2 2 0 002-2V8a1 1 0 112 0v8a4 4 0 01-4 4H8a4 4 0 01-4-4V8a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                                Delete Cohort
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-8">

        {{--
            Phone-width tightening: the 4 stat cards above sit 2-up even
            below the sm breakpoint, so their fixed padding/icon/number
            sizing (tuned for a wider single- or 2-up-at-tablet layout)
            needs to shrink to fit a ~160px column on a phone. Plain scoped
            CSS rather than Tailwind classes because this app's CSS bundle
            is pre-compiled and these exact sizes/media queries aren't
            already present in it.
        --}}
        <style>
            @media (max-width: 639px) {
                .stat-card { padding: 12px !important; }
                .stat-card .stat-icon-box { width: 40px !important; height: 40px !important; }
                .stat-card .stat-icon-box img { width: 24px !important; height: 24px !important; }
                .stat-card .stat-number { font-size: 1.2rem !important; }
                .stat-card .stat-number-sm { font-size: 0.9rem !important; }
                .stat-card .stat-header-row { min-height: 44px !important; }
                .stat-card .stat-watermark { width: 56px !important; }
            }
        </style>
            <div class="stat-card relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#FFE8EE] bg-[#FFF7F7] p-5 shadow-sm" style="min-height: 152px;">
                <img src="{{ asset('images/icons/dashboard-admin.svg') }}" alt="" aria-hidden="true"
                    class="stat-watermark pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="stat-header-row flex shrink-0 items-start gap-3" style="min-height: 72px;">
                        <div class="stat-icon-box flex shrink-0 items-center justify-center rounded-2xl bg-[#FFD5DF]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/3person-gradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">Total Startup</p>
                            <p class="stat-number font-bold text-gray-900" style="font-size: 1.875rem; line-height: 1.1;">{{ $totalStartups }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">Active startup in the system</p>
                </div>
            </div>

            <div class="stat-card relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#D2E5FF] bg-[#F8FBFF] p-5 shadow-sm" style="min-height: 152px;">
                <img src="{{ asset('images/icons/blue-line.svg') }}" alt="" aria-hidden="true"
                    class="stat-watermark pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="stat-header-row flex shrink-0 items-start gap-3" style="min-height: 72px;">
                        <div class="stat-icon-box flex shrink-0 items-center justify-center rounded-2xl bg-[#C1DBFF]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/1person-solidgradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">Assessed Startup</p>
                            <div class="mt-1 space-y-0.5">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm text-gray-500 inline-block" style="width: 68px;">Pre RL's</span>
                                    <span class="stat-number-sm font-bold text-gray-900 text-xl">{{ $stats['assessed_startup']['pre_rl'] }}</span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm text-gray-500 inline-block" style="width: 68px;">Post RL's</span>
                                    <span class="stat-number-sm font-bold text-gray-900 text-xl">{{ $stats['assessed_startup']['post_rl'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">Pre RL's {{ $stats['assessed_startup']['pre_rl_trend'] >= 0 ? 'up' : 'down' }} {{ abs($stats['assessed_startup']['pre_rl_trend']) }}% | Post RL's {{ $stats['assessed_startup']['post_rl_trend'] >= 0 ? 'up' : 'down' }} {{ abs($stats['assessed_startup']['post_rl_trend']) }}%</p>
                </div>
            </div>

            <div class="stat-card relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#FFEAC1] bg-[#FFFBF2] p-5 shadow-sm" style="min-height: 152px;">
                <img src="{{ asset('images/icons/yellow-line.svg') }}" alt="" aria-hidden="true"
                    class="stat-watermark pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="stat-header-row flex shrink-0 items-start gap-3" style="min-height: 72px;">
                        <div class="stat-icon-box flex shrink-0 items-center justify-center rounded-2xl bg-[#FFDB96]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/warning-gradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">At Risk Startup</p>
                            <p class="stat-number font-bold text-gray-900" style="font-size: 1.875rem; line-height: 1.1;">{{ $stats['at_risk_startup']['value'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">{{ $stats['at_risk_startup']['percent_of_total'] }}% of total startup</p>
                </div>
            </div>

            <div class="stat-card relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#D8C7FF] bg-[#FAF6FF] p-5 shadow-sm" style="min-height: 152px;">
                <img src="{{ asset('images/icons/purple-line.svg') }}" alt="" aria-hidden="true"
                    class="stat-watermark pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="stat-header-row flex shrink-0 items-start gap-3" style="min-height: 72px;">
                        <div class="stat-icon-box flex shrink-0 items-center justify-center rounded-2xl bg-[#DCCBFF]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/hands-gradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">Intervention Provided</p>
                            <p class="stat-number font-bold text-gray-900" style="font-size: 1.875rem; line-height: 1.1;">{{ $stats['intervention_provided']['value'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">This month</p>
                </div>
            </div>
        </div>

        {{--
            2-up on laptop/desktop, 1-up everywhere else (phones, tablets,
            and iPads — including landscape, up to iPad Pro 12.9" at
            1366px). None of Tailwind's compiled breakpoints land cleanly
            above every iPad width without also catching a standard 1024px
            iPad landscape, so this uses a plain scoped media query instead
            of a Tailwind class.
        --}}
        <style>
            @media (min-width: 1400px) {
                .donut-row-grid { grid-template-columns: 1fr 1fr; }
            }
        </style>

        {{-- Incubation Progress + Risk Classification --}}
        <div class="donut-row-grid grid grid-cols-1 gap-6 mb-8 items-stretch">
            <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm flex flex-col">
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-3">
                    <h2 class="text-white font-semibold text-lg">Incubation Progress</h2>
                </div>
                <div class="p-4 sm:p-8 flex flex-col sm:flex-row sm:items-center gap-6 sm:gap-8 flex-1">
                    <div class="relative shrink-0 rounded-full mx-auto sm:mx-0" style="width: 180px; height: 180px; background: {{ $incubationGradient }};">
                        <div class="absolute rounded-full bg-white flex flex-col items-center justify-center"
                            style="top: 25px; right: 25px; bottom: 25px; left: 25px;">
                            <span class="font-bold text-gray-800" style="font-size: 2rem;">{{ $incubationProgress['total'] }}</span>
                            <span class="text-sm text-gray-500">Total Startups</span>
                        </div>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-200">
                                <th class="py-2 pr-2 font-medium">Status</th>
                                <th class="py-2 pl-2 font-medium text-right">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($incubationProgress['breakdown'] as $row)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2.5 pr-2">
                                        <span class="flex items-center gap-2 text-gray-700">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $row['color'] }}"></span>
                                            <span class="flex flex-col leading-tight">
                                                <span class="text-[13px] font-medium text-gray-700">{{ $row['label'] }}</span>
                                                <span class="text-[12px] text-gray-400">{{ $row['range'] }}</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="py-2.5 pl-2 text-right text-gray-500 whitespace-nowrap align-middle">{{ $row['count'] }} ({{ $row['percent'] }}%)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm flex flex-col">
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-3 flex items-center justify-between">
                    <h2 class="text-white font-semibold text-lg">Risk Classification</h2>
                    <a href="{{ route('admin.risk-monitoring.index') }}" class="flex items-center gap-2 text-sm font-medium text-white/80 hover:text-white">
                        See All
                        <img src="{{ asset('images/icons/arrow-right.svg') }}" alt="" class="h-3 w-3">
                    </a>
                </div>
                <div class="p-4 sm:p-8 flex flex-col sm:flex-row sm:items-center gap-6 sm:gap-8 flex-1">
                    <div class="relative shrink-0 rounded-full mx-auto sm:mx-0" style="width: 180px; height: 180px; background: {{ $riskGradient }};">
                        <div class="absolute rounded-full bg-white flex flex-col items-center justify-center"
                            style="top: 25px; right: 25px; bottom: 25px; left: 25px;">
                            <span class="font-bold text-gray-800" style="font-size: 2rem;">{{ $riskClassification['total'] }}</span>
                            <span class="text-sm text-gray-500">Total Startups</span>
                        </div>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-200">
                                <th class="py-2 pr-2 font-medium">Status</th>
                                <th class="py-2 pl-2 font-medium text-right">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riskClassification['breakdown']->reverse() as $row)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2.5 pr-2 whitespace-nowrap">
                                        <span class="flex items-center gap-2 text-gray-700">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $row['color'] }}"></span>
                                            {{-- Second (invisible) line matches Incubation Progress's two-line
                                                 row markup exactly, so both tables' rows render at the same
                                                 height and line up row-for-row instead of Risk's single-line
                                                 rows drifting out of sync with Incubation's taller ones. --}}
                                            <span class="flex flex-col leading-tight">
                                                <span class="text-[13px] font-medium text-gray-700">{{ $row['label'] }}</span>
                                                <span class="text-[12px] text-gray-400" aria-hidden="true">&nbsp;</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="py-2.5 pl-2 text-right text-gray-500 whitespace-nowrap align-middle">{{ $row['count'] }} ({{ $row['percent'] }}%)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Average Readiness Level --}}
        <div class="mb-8" x-data="{ stageOpen: false }">
            <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-3 flex items-center justify-between">
                    <h2 class="text-white font-semibold text-lg">Average Readiness Level</h2>
                    <div class="relative" @click.outside="stageOpen = false">
                        <button type="button" @click="stageOpen = !stageOpen"
                            class="flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium text-white">
                            {{ $readinessStage }}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 111.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                        <div x-show="stageOpen" x-cloak class="absolute right-0 z-20 mt-2 overflow-hidden rounded-lg border border-gray-100 bg-white shadow-xl" style="width: 170px;">
                            @foreach (['Pre-Assessment', 'Post-Assessment'] as $s)
                                <a href="{{ request()->fullUrlWithQuery(['readinessStage' => $s]) }}"
                                    class="block px-4 py-2 text-sm transition-colors {{ $readinessStage === $s ? 'text-[#6D0D23] font-semibold' : 'text-gray-700' }} hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white">
                                    {{ $s }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @if ($averageReadiness['has_data'])
                        @php
                            $categoryBoxes = [
                                ['key' => 'TRL', 'label' => 'Technology', 'color' => '#6D0D23'],
                                ['key' => 'MRL', 'label' => 'Manufacturing', 'color' => '#11386A'],
                                ['key' => 'TMRL', 'label' => 'Team and Mgmt', 'color' => '#6D0D23'],
                                ['key' => 'SRL', 'label' => 'System / Market', 'color' => '#11386A'],
                            ];
                        @endphp
                        <div class="flex flex-col xl:flex-row xl:justify-center gap-8 items-center">
                            <div class="shrink-0 mx-auto flex w-full max-w-sm items-center justify-center" style="min-height: 330px;">
                                <x-readiness-radar
                                    :trl="$averageReadiness['scores']['TRL']"
                                    :mrl="$averageReadiness['scores']['MRL']"
                                    :tmrl="$averageReadiness['scores']['TMRL']"
                                    :srl="$averageReadiness['scores']['SRL']"
                                    :size="300" />
                            </div>
                            <style>
                                @media (max-width: 639px) {
                                    .readiness-box { padding: 12px !important; }
                                    .readiness-box .readiness-score { font-size: 1.25rem !important; }
                                    .readiness-box .readiness-score-suffix { font-size: 0.75rem !important; }
                                }
                            </style>
                            <div class="grid grid-cols-2 gap-3 sm:gap-4 w-full xl:max-w-2xl" style="min-width: 0;">
                                @foreach ($categoryBoxes as $box)
                                    @php $score = $averageReadiness['scores'][$box['key']]; @endphp
                                    <div class="readiness-box rounded-xl p-5" style="border: 2px solid {{ $box['color'] }}; min-width: 0;">
                                        <p class="text-sm font-semibold uppercase tracking-wide text-gray-400 truncate">{{ $box['label'] }}</p>
                                        <p class="mt-1.5 whitespace-nowrap">
                                            <span class="readiness-score text-3xl font-bold text-gray-900">{{ $box['key'] }} {{ number_format($score, 1) }}</span><span class="readiness-score-suffix text-base text-gray-400">/9</span>
                                        </p>
                                        <div class="mt-3 rounded-full bg-rose-100 overflow-hidden" style="height: 10px;">
                                            <div class="h-full rounded-full" style="width: {{ min(100, ($score / 9) * 100) }}%; background: #6D0D23;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-4">
                            Average across all {{ $averageReadiness['startup_count'] }} startups ({{ $averageReadiness['assessed_count'] }} assessed, {{ $averageReadiness['pending_count'] }} pending) &middot;
                            Overall: {{ number_format($averageReadiness['overall_score'], 1) }}/9 ({{ $averageReadiness['overall_label'] }})
                        </p>
                    @else
                        <p class="text-sm text-gray-400 py-16 text-center">No {{ $readinessStage }} scores recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Milestone Completion --}}
        <div class="mb-8">
            <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-3">
                    <h2 class="text-white font-semibold text-lg">Milestone Completion</h2>
                </div>
                <div class="p-8 flex flex-col sm:flex-row sm:items-stretch items-center gap-10">
                    <div class="shrink-0 flex flex-col justify-center text-center sm:pr-10 sm:text-left" style="width: 100%; max-width: 380px; border-right: 1px solid #D1D5DB;">
                        <p class="text-base text-gray-900 mb-3">Overall Completion Rate</p>
                        <p class="text-4xl font-bold text-[#6D0D23]">{{ $milestones['overall_percent'] }}%</p>
                        <div class="mt-4 rounded-full bg-rose-100 overflow-hidden" style="height: 12px;">
                            <div class="h-full rounded-full" style="width: {{ $milestones['overall_percent'] }}%; background: #6D0D23;"></div>
                        </div>
                    </div>
                    <div class="flex-1 w-full">
                        <div class="flex items-center justify-between text-sm text-gray-900 mb-4">
                            <span>Milestones</span>
                            <span>% Completed</span>
                        </div>
                        <div class="space-y-4">
                            @foreach ($milestones['milestones'] as $m)
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-sm text-gray-900 shrink-0" style="width: 170px;">{{ $m['label'] }}</span>
                                    <div class="flex-1 rounded-full bg-rose-100 overflow-hidden" style="height: 10px;">
                                        <div class="h-full rounded-full" style="width: {{ $m['percent'] }}%; background: #6D0D23;"></div>
                                    </div>
                                    <span class="text-sm text-gray-700 shrink-0" style="width: 40px; text-align: right;">{{ $m['percent'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Create Cohort modal --}}
        <div x-show="modal === 'create'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                        Create Cohort
                    </h3>
                    <button type="button"
                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                        @click="modal = null; document.getElementById('createCohortForm').reset()" aria-label="Close">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="createCohortForm" method="POST" action="{{ route('admin.cohorts.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cohort Name</label>
                        <input type="text" name="label" value="{{ $reopenModal === 'create' ? old('label') : '' }}"
                            placeholder="e.g. Cohort 6 - AY 2026-2027" class="w-full border rounded-lg px-3 py-2 text-sm">
                        @if ($reopenModal === 'create') @error('label') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="{{ $reopenModal === 'create' ? old('start_date') : '' }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            @if ($reopenModal === 'create') @error('start_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" value="{{ $reopenModal === 'create' ? old('end_date') : '' }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            @if ($reopenModal === 'create') @error('end_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="font-normal text-gray-400">(Optional)</span></label>
                        <textarea name="description" rows="3" maxlength="1000" class="w-full border rounded-lg px-3 py-2 text-sm">{{ $reopenModal === 'create' ? old('description') : '' }}</textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="modal = null; document.getElementById('createCohortForm').reset()" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                        <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Create Cohort</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedCohort)
            {{-- Edit Cohort modal --}}
            <div x-show="modal === 'edit'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-white font-semibold flex items-center gap-2">
                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-8.5 8.5a2 2 0 01-.878.507l-3 .75a.5.5 0 01-.606-.606l.75-3a2 2 0 01.507-.878l8.5-8.5z" /></svg>
                            Edit Cohort
                        </h3>
                        <button type="button"
                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                        @click="modal = null; document.getElementById('editCohortForm').reset()" aria-label="Close">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                    </div>
                    <form id="editCohortForm" method="POST" action="{{ route('admin.cohorts.update', $selectedCohort) }}" class="p-6 space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cohort Name</label>
                            <input type="text" name="label" value="{{ $reopenModal === 'edit' ? old('label', $selectedCohort->label) : $selectedCohort->label }}"
                                class="w-full border rounded-lg px-3 py-2 text-sm">
                            @if ($reopenModal === 'edit') @error('label') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                <input type="date" name="start_date" required
                                    value="{{ $reopenModal === 'edit' ? old('start_date', optional($selectedCohort->start_date)->format('Y-m-d')) : optional($selectedCohort->start_date)->format('Y-m-d') }}"
                                    @disabled($selectedCohort->isArchived())
                                    class="w-full border rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="date" name="end_date" required
                                    value="{{ $reopenModal === 'edit' ? old('end_date', optional($selectedCohort->end_date)->format('Y-m-d')) : optional($selectedCohort->end_date)->format('Y-m-d') }}"
                                    @disabled($selectedCohort->isArchived())
                                    class="w-full border rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-500">
                                @if ($reopenModal === 'edit') @error('end_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                            </div>
                        </div>
                        @if ($selectedCohort->isArchived())
                            <p class="text-xs text-gray-500 -mt-2">This cohort is archived — its start and end dates can no longer be changed.</p>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="3" maxlength="1000" class="w-full border rounded-lg px-3 py-2 text-sm">{{ $reopenModal === 'edit' ? old('description', $selectedCohort->description) : $selectedCohort->description }}</textarea>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="modal = null; document.getElementById('editCohortForm').reset()" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                            <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Cohort Details modal --}}
            <div x-show="modal === 'details'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-white font-semibold flex items-center gap-2">
                            <x-icon name="3person.svg" class="h-5 w-5 shrink-0 text-white" />
                            Cohort Details
                        </h3>
                        <button type="button"
                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                        @click="modal = null" aria-label="Close">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                    </div>
                    <div class="p-6 space-y-3 text-sm">
                        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Cohort Name</span><span class="font-medium text-gray-800">{{ $selectedCohort->display_label }}</span></div>
                        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Status</span><span class="font-medium text-gray-800">{{ $selectedCohort->status_label }}</span></div>
                        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Created</span><span class="font-medium text-gray-800">{{ $selectedCohort->created_at?->format('M d, Y') ?? '—' }}</span></div>
                        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Start Date</span><span class="font-medium text-gray-800">{{ optional($selectedCohort->start_date)->format('M d, Y') ?? '—' }}</span></div>
                        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">End Date</span><span class="font-medium text-gray-800">{{ optional($selectedCohort->end_date)->format('M d, Y') ?? '—' }}</span></div>
                        <div class="flex justify-between border-b border-gray-100 pb-2"><span class="text-gray-500">Startups</span><span class="font-medium text-gray-800">{{ $selectedCohort->startups_count }}</span></div>
                        <div>
                            <p class="text-gray-500 mb-1">Description</p>
                            <p class="text-gray-800 whitespace-pre-wrap break-words">{{ $selectedCohort->description ?: '—' }}</p>
                        </div>
                    </div>
                    <div class="px-6 pb-6">
                        <button type="button" @click="modal = null" class="w-full border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                    </div>
                </div>
            </div>

            {{-- Archive / End Cohort modal --}}
            <div x-show="modal === 'archive'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-white font-semibold">Archive / End Cohort</h3>
                        <button type="button"
                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                        @click="modal = null; archiveConfirm = ''" aria-label="Close">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">Ending <span class="font-semibold text-gray-800">{{ $selectedCohort->display_label }}</span> will:</p>
                        <ul class="space-y-2.5 mb-5">
                            @foreach ([
                                'Mark this cohort as Archived',
                                'Remove it from the Active cohort list',
                                'Keep all startup records and history intact',
                                'Stop new coordinator/assessment activity from being logged against it',
                            ] as $consequence)
                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                    <svg class="h-4 w-4 text-green-600 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                    {{ $consequence }}
                                </li>
                            @endforeach
                        </ul>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="font-semibold">END</span> to confirm</label>
                        <input type="text" x-model="archiveConfirm" class="w-full border rounded-lg px-3 py-2 text-sm mb-4" placeholder="END">
                        <form method="POST" action="{{ route('admin.cohorts.archive', $selectedCohort) }}" class="flex gap-3">
                            @csrf
                            @method('PATCH')
                            <button type="button" @click="modal = null; archiveConfirm = ''" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                            <button type="submit" :disabled="archiveConfirm !== 'END'" :class="archiveConfirm === 'END' ? 'opacity-100' : 'opacity-40 cursor-not-allowed'"
                                class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">End Cohort</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Delete Cohort modal --}}
            <div x-show="modal === 'delete'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-sm rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-5 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <x-icon name="3person.svg" class="h-6 w-6 shrink-0 text-white" />
                            <h3 class="text-white font-semibold text-base">Delete Cohort</h3>
                        </div>
                        <button type="button"
                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                        @click="modal = null; deleteConfirm = ''" aria-label="Close">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-2 rounded-lg border border-[#6D0D23] bg-white px-3 py-2 mb-3">
                            <svg class="h-4 w-4 flex-shrink-0 text-[#6D0D23]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 9v4M12 17h.01" />
                                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <p class="text-sm font-semibold text-[#6D0D23]">This action cannot be undone.</p>
                        </div>
                        <p class="text-xs text-gray-500 mb-1">Cohort:</p>
                        <p class="text-sm font-bold text-gray-900 mb-2">{{ $selectedCohort->display_label }}</p>
                        <p class="text-xs text-gray-600 mb-3">All data related to this cohort will be permanently deleted.</p>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Type <span class="font-semibold">DELETE</span> to confirm</label>
                        <input type="text" x-model="deleteConfirm" class="w-full border rounded-lg px-3 py-1.5 text-sm mb-3" placeholder="DELETE">
                        <form method="POST" action="{{ route('admin.cohorts.destroy', $selectedCohort) }}" class="flex gap-2">
                            @csrf
                            @method('DELETE')
                            <button type="button" @click="modal = null; deleteConfirm = ''" class="flex-1 rounded-lg border-2 border-[#11386A] text-[#11386A] py-2 text-sm font-semibold transition hover:bg-[#11386A]/5">Cancel</button>
                            <button type="submit" :disabled="deleteConfirm !== 'DELETE'" :class="deleteConfirm === 'DELETE' ? 'opacity-100' : 'opacity-40 cursor-not-allowed'"
                                class="flex-1 rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white py-2 text-sm font-semibold">Delete Cohort</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Success toast: fires once on load instead of a modal, if a cohort
             action (create/edit/archive/delete) just completed. --}}
        @foreach ([
            'created' => ['title' => 'New Cohort Created', 'desc' => 'The new cohort has been created and is now available in your Active cohort list.'],
            'updated' => ['title' => 'Cohort Edited', 'desc' => 'The cohort details have been updated successfully.'],
            'archived' => ['title' => 'Cohort Ended', 'desc' => 'The cohort has been archived. Its startups and history remain intact.'],
            'deleted' => ['title' => 'Cohort Deleted', 'desc' => 'The cohort has been permanently removed.'],
        ] as $key => $copy)
            <div x-init="if (successModal === '{{ $key }}') { Alpine.store('toast').success(@js($copy['title']), @js($copy['desc'])); successModal = null; }"></div>
        @endforeach

    </div>
</x-layouts.admin>
