@php
    // Every level's checklist, pre-seeded with explicit true/false for each
    // criterion (never a missing index) so the Alpine x-model bindings below
    // always have something concrete to bind to, whether or not this
    // startup/stage has been assessed before.
    $seedProgress = [];
    foreach ($rubricLevels as $type => $levels) {
        $stored = $currentAssessment?->progressFor($type) ?? [];
        foreach ($levels as $level => $definition) {
            $count = count($definition['criteria']);
            $storedLevel = $stored[$level] ?? $stored[(string) $level] ?? [];
            $seedProgress[$type][$level] = [];
            for ($i = 0; $i < $count; $i++) {
                $seedProgress[$type][$level][] = (bool) ($storedLevel[$i] ?? false);
            }
        }
    }

    $allStages = array_merge($stages, ['Reports']);
    $justSaved = session('status') === 'assessment-saved'
        && $selectedStartup
        && (int) session('assessed_startup') === $selectedStartup->startup_id
        && session('assessed_stage') === $selectedStage;

    // TRL's "Section 1: Startup & Technology Overview" only appears on
    // Pre-Assessment — Post-Assessment reuses the exact same TRL/MRL/TMRL/SRL
    // checklist accordion but skips straight to the checklist.
    $showTrlOverview = $selectedStage === 'Pre-Assessment';

    // Always seed a full object shape (never a bare empty array) so
    // @js() below emits a JS object — an empty PHP array would otherwise
    // serialize as `[]`, breaking every `trlOverview.<key>` access in Alpine.
    $storedOverview = $currentAssessment?->trl_overview ?? [];
    $trlOverviewSeed = [
        'industry_focus' => $storedOverview['industry_focus'] ?? [],
        'tech_stack' => array_merge(
            array_fill_keys(array_keys(\App\Support\TrlOverviewForm::TECH_STACK_FIELDS), ''),
            $storedOverview['tech_stack'] ?? []
        ),
        'technical_challenges' => $storedOverview['technical_challenges'] ?? [],
        'tech_team_roles' => $storedOverview['tech_team_roles'] ?? [],
        'team_maturity_level' => $storedOverview['team_maturity_level'] ?? '',
        'testing_strategies' => $storedOverview['testing_strategies'] ?? [],
        'topics_of_interest' => $storedOverview['topics_of_interest'] ?? [],
        'mode_of_communication' => $storedOverview['mode_of_communication'] ?? '',
    ];
@endphp

<div class="mb-5 flex items-center gap-2">
    <svg class="h-5 w-5 text-rose-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="4" width="18" height="12" rx="1.5" />
        <path stroke-linecap="round" d="M8 20h8M12 16v4" />
    </svg>
    <span class="font-bold text-gray-900">{{ $selectedStartup ? $selectedStage : 'Startup' }}</span>
</div>

<div class="flex flex-wrap items-center gap-3 mb-6">
    <form method="GET" action="{{ route('admin.assessment-hub.index') }}">
        <input type="hidden" name="main" value="assessment">
        <input type="hidden" name="stage" value="{{ $selectedStage }}">
        <select name="assessment_startup" onchange="this.form.submit()"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700">
            <option value="">All Startup</option>
            @foreach ($assessableStartups as $option)
                <option value="{{ $option->startup_id }}" @selected($selectedStartup && $selectedStartup->startup_id === $option->startup_id)>
                    {{ $option->company_name }}
                </option>
            @endforeach
        </select>
    </form>

    <div class="inline-flex flex-wrap overflow-hidden rounded-lg border border-gray-200">
        @foreach ($allStages as $stageOption)
            <a href="{{ route('admin.assessment-hub.index', ['main' => 'assessment', 'stage' => $stageOption, 'assessment_startup' => $selectedStartup?->startup_id]) }}"
                class="px-4 py-2 text-sm font-semibold transition {{ $selectedStage === $stageOption ? 'bg-rose-900 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
                {{ $stageOption }}
            </a>
        @endforeach
    </div>
</div>

@if (! $selectedStartup)
    {{-- ============ Overview: every assessable startup's completion status ============ --}}
    @if ($assessableStartups->isEmpty())
        <div class="rounded-xl border border-dashed p-12 text-center text-gray-400">
            No approved startups to assess yet.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-left text-white">
                            <th class="px-4 py-3 font-semibold">#</th>
                            <th class="px-4 py-3 font-semibold">Startup</th>
                            <th class="px-4 py-3 font-semibold">Not Started</th>
                            <th class="px-4 py-3 font-semibold">Completed</th>
                            <th class="px-4 py-3 font-semibold">Documents</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($overviewRows as $i => $row)
                            <tr class="border-b border-gray-100 last:border-0 align-top">
                                <td class="px-4 py-4">{{ $i + 1 }}</td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.assessment-hub.index', ['main' => 'assessment', 'stage' => $selectedStage, 'assessment_startup' => $row['startup']->startup_id]) }}"
                                        class="flex items-center gap-2 font-medium text-gray-900 hover:text-rose-900 hover:underline">
                                        @if ($row['startup']->startup_photo_url)
                                            <img src="{{ $row['startup']->startup_photo_url }}" alt="" class="h-6 w-6 rounded-full object-cover">
                                        @else
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-900 text-xs font-semibold text-white">
                                                {{ strtoupper(substr($row['startup']->company_name, 0, 1)) }}
                                            </span>
                                        @endif
                                        {{ $row['startup']->company_name }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 font-semibold text-gray-700">{{ $row['not_started_count'] }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-700">{{ $row['completed_count'] }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($row['pills'] as $pill)
                                            <span class="whitespace-nowrap rounded-full border px-3 py-1 text-xs font-semibold
                                                {{ $pill['completed'] ? 'border-green-400 text-green-700 bg-green-50' : 'border-rose-200 text-rose-500 bg-rose-50' }}">
                                                {{ $pill['label'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@elseif ($selectedStage === 'Active-Assessment')
    @include('admin.assessment-hub._active-assessment')
@elseif ($selectedStage === 'Venture Exit')
    @include('admin.assessment-hub._venture-exit')
@elseif (! in_array($selectedStage, ['Pre-Assessment', 'Post-Assessment'], true))
    {{-- Reports comes in a future update — every other stage is wired up. --}}
    <div class="rounded-xl border border-dashed p-12 text-center text-gray-400">
        {{ $selectedStage }} is coming in a future update.
    </div>
@else
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]"
        x-data="{
            activeType: 'TRL',
            expanded: { TRL: null, MRL: null, TMRL: null, SRL: null },
            progress: @js($seedProgress),
            trlOverview: @js($trlOverviewSeed),
            showClearConfirm: false,
            showSaved: @js($justSaved),
            scoreFor(type) {
                let best = null;
                for (const level of Object.keys(this.progress[type]).map(Number).sort((a, b) => a - b)) {
                    const checks = this.progress[type][level];
                    if (checks.length && checks.every(v => v)) best = level;
                }
                return best;
            },
            toggleLevel(type, level) {
                this.expanded[type] = (this.expanded[type] === level) ? null : level;
            },
            clearAll() {
                for (const type of Object.keys(this.progress)) {
                    for (const level of Object.keys(this.progress[type])) {
                        this.progress[type][level] = this.progress[type][level].map(() => false);
                    }
                }
                this.showClearConfirm = false;
            },
        }"
        x-init="
            expanded.TRL = scoreFor('TRL') ?? 1;
            expanded.MRL = scoreFor('MRL') ?? 1;
            expanded.TMRL = scoreFor('TMRL') ?? 1;
            expanded.SRL = scoreFor('SRL') ?? 1;
        ">

        <div>
            {{-- ============ RL type selector ============ --}}
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($rubricMeta as $type => $meta)
                    <button type="button" @click="activeType = '{{ $type }}'"
                        class="rounded-lg border px-4 py-3 text-center transition"
                        :class="activeType === '{{ $type }}' ? 'border-green-400 bg-green-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
                        <p class="font-bold" :class="activeType === '{{ $type }}' ? 'text-green-700' : 'text-gray-900'">{{ $type }}</p>
                        <p class="text-xs" :class="activeType === '{{ $type }}' ? 'text-green-600' : 'text-gray-400'"
                            x-text="scoreFor('{{ $type }}') ? scoreFor('{{ $type }}') + '/9' : 'Not Started'"></p>
                    </button>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.assessment-hub.assessments.update', $selectedStartup) }}" id="assessment-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="stage" value="{{ $selectedStage }}">
                @foreach (\App\Support\ReadinessRubric::TYPES as $type)
                    <input type="hidden" name="{{ strtolower($type) }}_progress" :value="JSON.stringify(progress.{{ $type }})">
                @endforeach
                @if ($showTrlOverview)
                    <input type="hidden" name="trl_overview" :value="JSON.stringify(trlOverview)">
                @endif

                @foreach ($rubricLevels as $type => $levels)
                    <div x-show="activeType === '{{ $type }}'" @if ($type !== 'TRL') x-cloak @endif>
                        @if ($type === 'TRL' && $showTrlOverview)
                            <div class="mb-6 rounded-xl border border-gray-200 p-5">
                                <h3 class="text-base font-bold text-gray-900">Section 1: Startup & Technology Overview</h3>
                                <p class="mb-4 text-sm text-gray-500">One-time intake captured before scoring TRL — appears only on Pre-Assessment.</p>

                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Industry Focus</p>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach (\App\Support\TrlOverviewForm::INDUSTRY_FOCUS as $option)
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="checkbox" value="{{ $option }}" x-model="trlOverview.industry_focus">
                                                    {{ $option }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Team Maturity Level</p>
                                        <select x-model="trlOverview.team_maturity_level" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                            <option value="">Select...</option>
                                            @foreach (\App\Support\TrlOverviewForm::TEAM_MATURITY_LEVELS as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Tech Stack</p>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                            @foreach (\App\Support\TrlOverviewForm::TECH_STACK_FIELDS as $key => $label)
                                                <div>
                                                    <label class="mb-1 block text-xs text-gray-500">{{ $label }}</label>
                                                    <input type="text" x-model="trlOverview.tech_stack.{{ $key }}"
                                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Technical Challenges</p>
                                        <div class="flex flex-col gap-1.5">
                                            @foreach (\App\Support\TrlOverviewForm::TECHNICAL_CHALLENGES as $option)
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="checkbox" value="{{ $option }}" x-model="trlOverview.technical_challenges">
                                                    {{ $option }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Tech Team Roles</p>
                                        <div class="flex flex-col gap-1.5">
                                            @foreach (\App\Support\TrlOverviewForm::TECH_TEAM_ROLES as $option)
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="checkbox" value="{{ $option }}" x-model="trlOverview.tech_team_roles">
                                                    {{ $option }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Testing Strategies</p>
                                        <div class="flex flex-col gap-1.5">
                                            @foreach (\App\Support\TrlOverviewForm::TESTING_STRATEGIES as $option)
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="checkbox" value="{{ $option }}" x-model="trlOverview.testing_strategies">
                                                    {{ $option }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Mode of Communication</p>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach (\App\Support\TrlOverviewForm::MODES_OF_COMMUNICATION as $option)
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="radio" value="{{ $option }}" x-model="trlOverview.mode_of_communication">
                                                    {{ $option }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <p class="mb-2 text-sm font-semibold text-gray-700">Topics of Interest</p>
                                        <div class="grid grid-cols-1 gap-x-6 gap-y-1.5 sm:grid-cols-2">
                                            @foreach (array_merge(\App\Support\TrlOverviewForm::TOPICS_OF_INTEREST_COLUMN_1, \App\Support\TrlOverviewForm::TOPICS_OF_INTEREST_COLUMN_2) as $option)
                                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                                    <input type="checkbox" value="{{ $option }}" x-model="trlOverview.topics_of_interest">
                                                    {{ $option }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <h2 class="text-lg font-bold text-gray-900">{{ $rubricMeta[$type]['label'] }}</h2>
                        <p class="mb-3 text-sm text-gray-500">{{ $rubricMeta[$type]['description'] }}</p>

                        <div class="mb-3 h-2 w-full rounded-full bg-gray-200">
                            <div class="h-2 rounded-full bg-rose-900 transition-all"
                                :style="`width: ${((scoreFor('{{ $type }}') || 0) / 9) * 100}%`"></div>
                        </div>
                        <div class="mb-4 flex justify-between text-xs text-gray-400">
                            @for ($n = 1; $n <= 9; $n++)
                                <span>{{ $n }}</span>
                            @endfor
                        </div>

                        <p class="mb-4 inline-block rounded border border-rose-900 px-3 py-1 text-xs font-semibold italic text-rose-900">
                            {{ $rubricMeta[$type]['form_no'] }}
                        </p>

                        <div class="space-y-3">
                            @foreach ($levels as $level => $definition)
                                <div class="rounded-xl border p-1 transition"
                                    :class="expanded.{{ $type }} === {{ $level }} ? 'border-rose-300 bg-rose-50/40' : 'border-gray-200'">
                                    <button type="button" @click="toggleLevel('{{ $type }}', {{ $level }})"
                                        class="flex w-full items-center gap-3 px-3 py-2.5 text-left">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                            :class="progress.{{ $type }}[{{ $level }}].every(v => v) ? 'bg-rose-900 text-white' : 'bg-gray-100 text-gray-500'">
                                            {{ $level }}
                                        </span>
                                        <span class="flex-1 font-bold text-gray-900">{{ $definition['title'] }}</span>
                                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                                            :class="expanded.{{ $type }} === {{ $level }} ? 'rotate-180' : ''"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                                        </svg>
                                    </button>

                                    <div x-show="expanded.{{ $type }} === {{ $level }}" x-cloak class="px-4 pb-4 pt-1">
                                        @isset($definition['target'])
                                            <p class="mb-2 text-sm text-gray-700"><strong>Target:</strong> {{ $definition['target'] }}</p>
                                        @endisset

                                        <div class="space-y-2">
                                            @foreach ($definition['criteria'] as $i => $criterion)
                                                <label class="flex cursor-pointer items-start gap-2.5 text-sm text-gray-700">
                                                    <input type="checkbox" class="sr-only" x-model="progress.{{ $type }}[{{ $level }}][{{ $i }}]">
                                                    <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border"
                                                        :class="progress.{{ $type }}[{{ $level }}][{{ $i }}] ? 'border-rose-900 bg-rose-900 text-white' : 'border-gray-300'">
                                                        <svg x-show="progress.{{ $type }}[{{ $level }}][{{ $i }}]" class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </span>
                                                    <span>{{ $criterion }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button type="button" @click="showClearConfirm = true"
                        class="h-11 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50 sm:flex-1">
                        Clear Form
                    </button>
                    <button type="submit"
                        class="flex h-11 w-full items-center justify-center gap-2 rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-90 sm:flex-1">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Assessment
                    </button>
                </div>
            </form>

            {{-- ============ Clear Form confirmation ============ --}}
            <div x-show="showClearConfirm" x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none;">
                <div class="relative w-full max-w-lg rounded-2xl bg-white px-5 pb-5 pt-8 text-center shadow-2xl sm:px-6">
                    <button type="button" @click="showClearConfirm = false"
                        class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full border border-gray-900 text-gray-900 transition hover:border-transparent hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white"
                        aria-label="Close">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A]">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </div>

                    <h2 class="mt-2.5 bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-base font-bold text-transparent sm:text-lg">
                        Clear Form
                    </h2>

                    <p class="mt-1.5 text-xs leading-5 text-gray-600">Are you sure you want to clear this form?</p>

                    <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4">
                        <button type="button" @click="showClearConfirm = false"
                            class="h-10 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="button" @click="clearAll()"
                            class="h-10 w-full rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95">
                            Yes
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============ Save success ============ --}}
            <div x-show="showSaved" x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none;">
                <div class="relative w-full max-w-lg overflow-hidden rounded-xl bg-white">
                    <div class="flex items-center justify-center bg-gradient-to-r from-rose-950 to-blue-950 py-8">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white">
                            <svg class="h-8 w-8 text-[#11386A]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="p-8 text-center">
                        <h3 class="mb-2 text-2xl font-bold text-gray-900">Great!</h3>
                        <p class="mb-6 text-gray-500">Changes saved successfully.</p>
                        <button type="button" @click="showSaved = false"
                            class="w-full rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-3 font-semibold text-white transition hover:opacity-90">
                            Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Sidebar summary (tracks the active stage tab) ============ --}}
        <div class="h-fit rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ strtoupper($selectedStage) }}</p>
            <p class="mt-1 text-lg font-bold text-gray-900">{{ $selectedStartup->company_name }}</p>
            <p class="text-sm text-gray-500">{{ $selectedStartup->batch_label }}</p>

            @if ($stageSummary)
                <div class="mt-4">
                    <x-readiness-radar
                        :trl="$stageSummary->trl_score ?? 0"
                        :mrl="$stageSummary->mrl_score ?? 0"
                        :tmrl="$stageSummary->tmrl_score ?? 0"
                        :srl="$stageSummary->srl_score ?? 0" />
                </div>

                <p class="mt-2 text-center text-3xl font-bold text-rose-900">
                    {{ $stageSummary->overall_score !== null ? number_format($stageSummary->overall_score, 1) : '—' }}
                </p>
                <p class="text-center text-xs text-gray-400">Development Composite</p>

                <div class="mt-4 space-y-2 text-sm">
                    @foreach (\App\Support\ReadinessRubric::TYPES as $t)
                        @php $score = $stageSummary->{strtolower($t).'_score'}; @endphp
                        <div class="flex items-center justify-between gap-2">
                            <span class="w-12 text-gray-500">{{ $t }}</span>
                            <div class="h-1.5 flex-1 rounded-full bg-gray-100">
                                <div class="h-1.5 rounded-full bg-rose-900" style="width: {{ ($score ?? 0) / 9 * 100 }}%"></div>
                            </div>
                            <span class="w-10 text-right text-gray-500">{{ $score ?? '—' }}/9</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-gray-400">No {{ $selectedStage }} data yet.</p>
            @endif

            <a href="{{ route('admin.startups.show', $selectedStartup) }}"
                class="mt-5 block rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-2.5 text-center text-sm font-bold text-white transition hover:opacity-90">
                View Profile
            </a>
        </div>
    </div>
@endif
