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

            <div class="flex items-center gap-3">
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
                                class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                All Cohort
                                @if (! $selectedCohort)
                                    <svg class="h-4 w-4 text-[#6D0D23]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                @endif
                            </a>
                            @foreach ($cohorts->where('status', 'Active') as $c)
                                <a href="{{ request()->fullUrlWithQuery(['cohort' => $c->cohort_id]) }}"
                                    class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    {{ $c->display_label }}
                                    @if ($selectedCohort && $selectedCohort->cohort_id === $c->cohort_id)
                                        <svg class="h-4 w-4 text-[#6D0D23]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                    @endif
                                </a>
                            @endforeach

                            @if ($cohorts->where('status', 'Inactive')->count())
                                <div class="my-2 border-t border-gray-100"></div>
                                <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-widest text-gray-400">Archived</p>
                                @foreach ($cohorts->where('status', 'Inactive') as $c)
                                    <a href="{{ request()->fullUrlWithQuery(['cohort' => $c->cohort_id]) }}"
                                        class="flex items-center justify-between px-4 py-2 text-sm text-gray-500 hover:bg-gray-50">
                                        {{ $c->display_label }}
                                        @if ($selectedCohort && $selectedCohort->cohort_id === $c->cohort_id)
                                            <svg class="h-4 w-4 text-[#6D0D23]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                                        @endif
                                    </a>
                                @endforeach
                            @endif

                            <div class="my-2 border-t border-gray-100"></div>
                            <button type="button" @click="modal = 'create'; cohortMenuOpen = false"
                                class="flex w-full items-center gap-2 px-4 py-2 text-sm font-medium text-[#6D0D23] hover:bg-gray-50">
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
                        @php $hasCohort = (bool) $selectedCohort; @endphp
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
                            <button type="button" @click="if (({{ $hasCohort ? 'true' : 'false' }})) { modal = 'archive'; actionsMenuOpen = false }"
                                {{ $hasCohort ? '' : 'disabled' }}
                                class="flex w-full items-center gap-2 px-4 py-3 text-sm font-medium transition-colors {{ $hasCohort ? 'text-gray-700 hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white' : 'text-gray-300 cursor-not-allowed' }}">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            <div class="relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#FFE8EE] bg-[#FFF7F7] p-5 shadow-sm" style="height: 152px;">
                <img src="{{ asset('images/icons/dashboard-admin.svg') }}" alt="" aria-hidden="true"
                    class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="flex shrink-0 items-start gap-3" style="height: 72px;">
                        <div class="flex shrink-0 items-center justify-center rounded-2xl bg-[#FFD5DF]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/3person-gradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">Total Startup</p>
                            <p class="font-bold text-gray-900" style="font-size: 1.875rem; line-height: 1.1;">{{ $totalStartups }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">Active startup in the system</p>
                </div>
            </div>

            <div class="relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#D2E5FF] bg-[#F8FBFF] p-5 shadow-sm" style="height: 152px;">
                <img src="{{ asset('images/icons/blue-line.svg') }}" alt="" aria-hidden="true"
                    class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="flex shrink-0 items-start gap-3" style="height: 72px;">
                        <div class="flex shrink-0 items-center justify-center rounded-2xl bg-[#C1DBFF]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/1person-gradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">Assessed Startup</p>
                            <div class="mt-1 space-y-0.5">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm text-gray-500 inline-block" style="width: 68px;">Pre RL's</span>
                                    <span class="font-bold text-gray-900 text-xl">{{ $stats['assessed_startup']['pre_rl'] }}</span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm text-gray-500 inline-block" style="width: 68px;">Post RL's</span>
                                    <span class="font-bold text-gray-900 text-xl">{{ $stats['assessed_startup']['post_rl'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">Pre RL's {{ $stats['assessed_startup']['pre_rl_trend'] >= 0 ? 'up' : 'down' }} {{ abs($stats['assessed_startup']['pre_rl_trend']) }}% | Post RL's {{ $stats['assessed_startup']['post_rl_trend'] >= 0 ? 'up' : 'down' }} {{ abs($stats['assessed_startup']['post_rl_trend']) }}%</p>
                </div>
            </div>

            <div class="relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#FFEAC1] bg-[#FFFBF2] p-5 shadow-sm" style="height: 152px;">
                <img src="{{ asset('images/icons/yellow-line.svg') }}" alt="" aria-hidden="true"
                    class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="flex shrink-0 items-start gap-3" style="height: 72px;">
                        <div class="flex shrink-0 items-center justify-center rounded-2xl bg-[#FFDB96]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/bell-gradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">At Risk Startup</p>
                            <p class="font-bold text-gray-900" style="font-size: 1.875rem; line-height: 1.1;">{{ $stats['at_risk_startup']['value'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">{{ $stats['at_risk_startup']['percent_of_total'] }}% of total startup</p>
                </div>
            </div>

            <div class="relative flex flex-col overflow-hidden rounded-xl border-[3px] border-[#D8C7FF] bg-[#FAF6FF] p-5 shadow-sm" style="height: 152px;">
                <img src="{{ asset('images/icons/purple-line.svg') }}" alt="" aria-hidden="true"
                    class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2" style="width: 105px; height: auto;">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div class="flex shrink-0 items-start gap-3" style="height: 72px;">
                        <div class="flex shrink-0 items-center justify-center rounded-2xl bg-[#DCCBFF]" style="width: 64px; height: 64px;">
                            <img src="{{ asset('images/icons/bell-gradient.svg') }}" alt="" class="h-12 w-12 object-contain">
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-800 font-semibold text-sm">Intervention Provided</p>
                            <p class="font-bold text-gray-900" style="font-size: 1.875rem; line-height: 1.1;">{{ $stats['intervention_provided']['value'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-[#6D0D23] mt-3">This month</p>
                </div>
            </div>
        </div>

        {{-- Incubation Progress + Risk Classification --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 items-stretch">
            <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-3">
                    <h2 class="text-white font-semibold text-lg">Incubation Progress</h2>
                </div>
                <div class="p-7 flex items-center gap-8">
                    <div class="relative shrink-0 rounded-full" style="width: 180px; height: 180px; background: {{ $incubationGradient }};">
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
                            @foreach ($incubationProgress['breakdown']->reverse() as $row)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2.5 pr-2">
                                        <span class="flex items-center gap-2 text-gray-700 font-medium">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $row['color'] }}"></span>
                                            <span class="leading-tight text-[13px]">{{ $row['range'] }}</span>
                                        </span>
                                    </td>
                                    <td class="py-2.5 pl-2 text-right text-gray-500 whitespace-nowrap align-top">{{ $row['count'] }} ({{ $row['percent'] }}%)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-3 flex items-center justify-between">
                    <h2 class="text-white font-semibold text-lg">Risk Classification</h2>
                    <a href="{{ route('admin.risk-monitoring.index') }}" class="flex items-center gap-2 text-sm font-medium text-white/80 hover:text-white">
                        See All
                        <img src="{{ asset('images/icons/arrow-right.svg') }}" alt="" class="h-3 w-3">
                    </a>
                </div>
                <div class="p-7 flex items-center gap-8">
                    <div class="relative shrink-0 rounded-full" style="width: 180px; height: 180px; background: {{ $riskGradient }};">
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
                                        <span class="flex items-center gap-2 text-gray-700 font-medium">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: {{ $row['color'] }}"></span>
                                            {{ $row['label'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pl-2 text-right text-gray-500 whitespace-nowrap">{{ $row['count'] }} ({{ $row['percent'] }}%)</td>
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
                            <div class="shrink-0 mx-auto flex items-center justify-center" style="width: 380px; min-height: 330px;">
                                <x-readiness-radar
                                    :trl="$averageReadiness['scores']['TRL']"
                                    :mrl="$averageReadiness['scores']['MRL']"
                                    :tmrl="$averageReadiness['scores']['TMRL']"
                                    :srl="$averageReadiness['scores']['SRL']"
                                    :size="300" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full xl:max-w-2xl" style="min-width: 0;">
                                @foreach ($categoryBoxes as $box)
                                    @php $score = $averageReadiness['scores'][$box['key']]; @endphp
                                    <div class="rounded-xl p-5" style="border: 2px solid {{ $box['color'] }}; min-width: 0;">
                                        <p class="text-sm font-semibold uppercase tracking-wide text-gray-400 truncate">{{ $box['label'] }}</p>
                                        <p class="mt-1.5 whitespace-nowrap">
                                            <span class="text-3xl font-bold text-gray-900">{{ $box['key'] }} {{ $score }}</span><span class="text-base text-gray-400">/9</span>
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
                            Overall: {{ $averageReadiness['overall_score'] }}/9 ({{ $averageReadiness['overall_label'] }})
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
        <div x-show="modal === 'create'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
            <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                        Create Cohort
                    </h3>
                    <button type="button" class="text-white/80 hover:text-white" @click="modal = null">&#10005;</button>
                </div>
                <form method="POST" action="{{ route('admin.cohorts.store') }}" class="p-6 space-y-4">
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
                            <input type="date" name="start_date" value="{{ $reopenModal === 'create' ? old('start_date') : '' }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" value="{{ $reopenModal === 'create' ? old('end_date') : '' }}" class="w-full border rounded-lg px-3 py-2 text-sm">
                            @if ($reopenModal === 'create') @error('end_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm">{{ $reopenModal === 'create' ? old('description') : '' }}</textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="modal = null" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                        <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Create Cohort</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedCohort)
            {{-- Edit Cohort modal --}}
            <div x-show="modal === 'edit'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
                <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-white font-semibold flex items-center gap-2">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-8.5 8.5a2 2 0 01-.878.507l-3 .75a.5.5 0 01-.606-.606l.75-3a2 2 0 01.507-.878l8.5-8.5z" /></svg>
                            Edit Cohort
                        </h3>
                        <button type="button" class="text-white/80 hover:text-white" @click="modal = null">&#10005;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.cohorts.update', $selectedCohort) }}" class="p-6 space-y-4">
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
                                <input type="date" name="start_date"
                                    value="{{ $reopenModal === 'edit' ? old('start_date', optional($selectedCohort->start_date)->format('Y-m-d')) : optional($selectedCohort->start_date)->format('Y-m-d') }}"
                                    class="w-full border rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                <input type="date" name="end_date"
                                    value="{{ $reopenModal === 'edit' ? old('end_date', optional($selectedCohort->end_date)->format('Y-m-d')) : optional($selectedCohort->end_date)->format('Y-m-d') }}"
                                    class="w-full border rounded-lg px-3 py-2 text-sm">
                                @if ($reopenModal === 'edit') @error('end_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm">{{ $reopenModal === 'edit' ? old('description', $selectedCohort->description) : $selectedCohort->description }}</textarea>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="modal = null" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                            <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Cohort Details modal --}}
            <div x-show="modal === 'details'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
                <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-white font-semibold">Cohort Details</h3>
                        <button type="button" class="text-white/80 hover:text-white" @click="modal = null">&#10005;</button>
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
                            <p class="text-gray-800">{{ $selectedCohort->description ?: '—' }}</p>
                        </div>
                    </div>
                    <div class="px-6 pb-6">
                        <button type="button" @click="modal = null" class="w-full border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                    </div>
                </div>
            </div>

            {{-- Archive / End Cohort modal --}}
            <div x-show="modal === 'archive'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
                <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-white font-semibold">Archive / End Cohort</h3>
                        <button type="button" class="text-white/80 hover:text-white" @click="modal = null; archiveConfirm = ''">&#10005;</button>
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
            <div x-show="modal === 'delete'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal = null">
                <div class="w-full max-w-lg rounded-xl bg-white overflow-hidden shadow-xl" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 flex items-center justify-between">
                        <h3 class="text-white font-semibold">Delete Cohort</h3>
                        <button type="button" class="text-white/80 hover:text-white" @click="modal = null; deleteConfirm = ''">&#10005;</button>
                    </div>
                    <div class="p-6">
                        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 mb-4">
                            <p class="text-sm font-semibold text-rose-700 mb-1">This action cannot be undone</p>
                            <p class="text-xs text-rose-600">Deleting <span class="font-semibold">{{ $selectedCohort->display_label }}</span> permanently removes the cohort record. Startups already assigned to it keep their history but will no longer be linked to this cohort.</p>
                        </div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="font-semibold">DELETE</span> to confirm</label>
                        <input type="text" x-model="deleteConfirm" class="w-full border rounded-lg px-3 py-2 text-sm mb-4" placeholder="DELETE">
                        <form method="POST" action="{{ route('admin.cohorts.destroy', $selectedCohort) }}" class="flex gap-3">
                            @csrf
                            @method('DELETE')
                            <button type="button" @click="modal = null; deleteConfirm = ''" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
                            <button type="submit" :disabled="deleteConfirm !== 'DELETE'" :class="deleteConfirm === 'DELETE' ? 'opacity-100' : 'opacity-40 cursor-not-allowed'"
                                class="flex-1 bg-rose-600 text-white rounded-lg py-2.5 text-sm font-medium">Delete Cohort</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Success confirmation modals --}}
        @foreach ([
            'created' => ['title' => 'New Cohort Created', 'desc' => 'The new cohort has been created and is now available in your Active cohort list.'],
            'updated' => ['title' => 'Cohort Edited', 'desc' => 'The cohort details have been updated successfully.'],
            'archived' => ['title' => 'Cohort Ended', 'desc' => 'The cohort has been archived. Its startups and history remain intact.'],
            'deleted' => ['title' => 'Cohort Deleted', 'desc' => 'The cohort has been permanently removed.'],
        ] as $key => $copy)
            <div x-show="successModal === '{{ $key }}'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="successModal = null">
                <div class="w-full max-w-sm rounded-xl bg-white overflow-hidden shadow-xl text-center" @click.stop>
                    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 pt-8 pb-10">
                        <div class="mx-auto flex items-center justify-center rounded-full bg-white/20" style="width: 64px; height: 64px;">
                            <svg class="h-8 w-8 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.42 0l-3.25-3.25a1 1 0 111.42-1.42l2.54 2.54 6.54-6.54a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>
                        </div>
                    </div>
                    <div class="px-6 -mt-4 pb-6">
                        <div class="rounded-xl bg-white p-4">
                            <h3 class="font-bold text-gray-900 text-lg">{{ $copy['title'] }}</h3>
                            <p class="text-sm text-gray-500 mt-2">{{ $copy['desc'] }}</p>
                            <button type="button" @click="successModal = null"
                                class="mt-5 w-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Continue</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</x-layouts.admin>
