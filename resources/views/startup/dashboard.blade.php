<x-layouts.founder title="Dashboard">

    @if (! $startup)
        <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center text-gray-500 shadow-sm">
            No startup profile is linked to this account yet.
        </div>
    @else
        @php
            $cohortSuffix = str_pad((string) $cohortSequence, 3, '0', STR_PAD_LEFT);
        @endphp

        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, Founder!</h1>
            <p class="text-gray-500 mt-1">{{ $startup->company_name }} &middot; {{ $startup->batch_label }} - {{ $cohortSuffix }}</p>
        </div>

        {{-- Action Required --}}
        @if ($needsProfileSetup)
            <div class="mb-6 flex flex-col gap-4 rounded-2xl bg-rose-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex items-center justify-center rounded-xl bg-[#6D0D23] text-white" style="width: 44px; height: 44px;">
                        <span class="icon-mask" style="width: 20px; height: 20px; --icon: url('{{ asset('images/icons/warning.svg') }}')"></span>
                    </span>
                    <div>
                        <p class="font-bold text-gray-900">Action Required: Startup Profile</p>
                        <p class="text-sm text-gray-600">Complete your Startup Profile before you can fill out the Information Sheet.</p>
                    </div>
                </div>
                <a href="{{ route('startup.profile.edit') }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-2.5 text-sm font-semibold text-white">
                    <span class="icon-mask" style="width: 16px; height: 16px; --icon: url('{{ asset('images/icons/info-sheet.svg') }}')"></span>
                    Open Profile
                </a>
            </div>
        @elseif ($needsInformationSheet)
            <div class="mb-6 flex flex-col gap-4 rounded-2xl bg-rose-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex items-center justify-center rounded-xl bg-[#6D0D23] text-white" style="width: 44px; height: 44px;">
                        <span class="icon-mask" style="width: 20px; height: 20px; --icon: url('{{ asset('images/icons/warning.svg') }}')"></span>
                    </span>
                    <div>
                        <p class="font-bold text-gray-900">Action Required: Startup Information Sheet</p>
                        <p class="text-sm text-gray-600">Complete TBIDO Form No.001 to activate Startup Account.</p>
                    </div>
                </div>
                <a href="{{ route('startup.information-sheet.edit') }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-2.5 text-sm font-semibold text-white">
                    <span class="icon-mask" style="width: 16px; height: 16px; --icon: url('{{ asset('images/icons/info-sheet.svg') }}')"></span>
                    Open Form
                </a>
            </div>
        @endif

        {{-- Overall Readiness --}}
        <div class="mb-6 rounded-2xl bg-gradient-to-br from-[#6D0D23] to-[#9F1239] p-6 text-white shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/80">
                        Overall Readiness
                        @if ($readinessStage)
                            <span class="text-white/60">({{ strtoupper(str_replace('-', ' ', $readinessStage)) }})</span>
                        @endif
                    </p>
                    <p class="mt-2 font-bold" style="font-size: 44px; line-height: 1;">
                        {{ $assessment && $assessment->overall_score !== null ? number_format($assessment->overall_score, 1) : '—' }}
                        <span class="text-lg font-medium text-white/70">/9</span>
                    </p>
                    <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-sm font-semibold">
                        <svg style="width: 14px; height: 14px;" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 13l5-5 3 3 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M13 5h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        {{ $overallLabel }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:w-auto">
                    @foreach (\App\Support\ReadinessRubric::TYPES as $type)
                        <div class="rounded-2xl bg-white/15 p-4 text-center" style="min-width: 96px;">
                            <p class="text-xs font-semibold uppercase tracking-wide text-white/80">{{ $type }}</p>
                            <p class="mt-1 font-bold" style="font-size: 28px; line-height: 1;">
                                {{ $assessment?->scoreFor($type) ?? '—' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            @php
                $quickLinks = [
                    ['route' => 'startup.submissions.index', 'icon' => 'submit-roadblock.svg', 'title' => 'Submit Roadblock?', 'description' => "Need an expert? Tell us you're stuck."],
                    ['route' => 'startup.meetings.index', 'icon' => 'coordProfile.svg', 'title' => 'My Meetings', 'description' => 'Check your schedule and upcoming events.'],
                    ['route' => 'startup.readiness.index', 'icon' => 'riskMon.svg', 'title' => 'Readiness Results', 'description' => 'See your TRL/MRL/TMRL/SRL details.'],
                ];
            @endphp
            @foreach ($quickLinks as $link)
                <a href="{{ route($link['route']) }}" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:shadow-md">
                    <span class="flex items-center justify-center rounded-xl bg-rose-50 text-[#6D0D23]" style="width: 48px; height: 48px;">
                        <span class="icon-mask" style="width: 22px; height: 22px; --icon: url('{{ asset('images/icons/' . $link['icon']) }}')"></span>
                    </span>
                    <p class="mt-4 font-bold text-gray-900">{{ $link['title'] }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $link['description'] }}</p>
                </a>
            @endforeach
        </div>

        {{-- Startup Onboarding --}}
        <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
            <p class="text-lg font-bold text-gray-900">Startup Onboarding <span class="font-medium text-gray-400">(Information Sheet)</span></p>
            <div class="mt-4 border-t border-gray-100"></div>
            <div class="mt-8 overflow-x-auto">
                <div style="min-width: 560px;">
                    <x-step-tracker :steps="$onboardingSteps" />
                </div>
            </div>
        </div>

        {{-- Graduation Progress --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
            <p class="text-lg font-bold text-gray-900">Graduation Progress <span class="font-medium text-gray-400">(Roadmap)</span></p>
            <div class="mt-4 border-t border-gray-100"></div>
            <div class="mt-8 overflow-x-auto">
                <div style="min-width: 680px;">
                    <x-step-tracker :steps="$graduationSteps" />
                </div>
            </div>
        </div>
    @endif

</x-layouts.founder>
