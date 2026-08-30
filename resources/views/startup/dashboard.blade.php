<x-layouts.founder title="Dashboard">

    @if (! $startup)
        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center text-gray-500 shadow-sm sm:p-8">
            No startup profile is linked to this account yet.
        </div>
    @else
        @php
            $cohortSuffix = str_pad((string) $cohortSequence, 3, '0', STR_PAD_LEFT);
        @endphp

        <div class="mb-5 sm:mb-6">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Welcome back, Founder!</h1>
            <p class="mt-1 text-sm text-gray-500 sm:text-base">{{ $startup->company_name }} &middot; {{ $startup->batch_label }} - {{ $cohortSuffix }}</p>
        </div>

        {{-- Action Required --}}
        @if ($needsProfileSetup)
            <div class="mb-5 flex flex-col gap-4 rounded-2xl bg-rose-50 p-4 sm:mb-6 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-center gap-3 sm:gap-4">
                    <span class="flex shrink-0 items-center justify-center rounded-xl bg-[#6D0D23] text-white" style="width: 44px; height: 44px;">
                        <span class="icon-mask" style="width: 20px; height: 20px; --icon: url('{{ asset('images/icons/warning.svg') }}')"></span>
                    </span>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900">Action Required: Startup Profile</p>
                        <p class="text-sm text-gray-600">Complete your Startup Profile before you can fill out the Information Sheet.</p>
                    </div>
                </div>
                <a href="{{ route('startup.profile.edit') }}"
                    class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-2.5 text-sm font-semibold text-white sm:w-auto">
                    <span class="icon-mask" style="width: 16px; height: 16px; --icon: url('{{ asset('images/icons/info-sheet.svg') }}')"></span>
                    Open Profile
                </a>
            </div>
        @elseif ($needsInformationSheet)
            <div class="mb-5 flex flex-col gap-4 rounded-2xl bg-rose-50 p-4 sm:mb-6 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-center gap-3 sm:gap-4">
                    <span class="flex shrink-0 items-center justify-center rounded-xl bg-[#6D0D23] text-white" style="width: 44px; height: 44px;">
                        <span class="icon-mask" style="width: 20px; height: 20px; --icon: url('{{ asset('images/icons/warning.svg') }}')"></span>
                    </span>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900">Action Required: Startup Information Sheet</p>
                        <p class="text-sm text-gray-600">Complete TBIDO Form No.001 to activate Startup Account.</p>
                    </div>
                </div>
                <a href="{{ route('startup.information-sheet.edit') }}"
                    class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-2.5 text-sm font-semibold text-white sm:w-auto">
                    <span class="icon-mask" style="width: 16px; height: 16px; --icon: url('{{ asset('images/icons/info-sheet.svg') }}')"></span>
                    Open Form
                </a>
            </div>
        @endif

        {{-- What's new: admin-side changes the founder hasn't opened yet.
             Blue, not pink — these are updates to be aware of, not chores
             blocking their progress. Each clears itself once the linked page
             is visited (see DashboardController::updates()). --}}
        @foreach ($updates ?? [] as $update)
            <div class="{{ $loop->last ? 'mb-5 sm:mb-6' : 'mb-3' }} flex flex-col gap-4 rounded-2xl border border-sky-100 bg-sky-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-center gap-3 sm:gap-4">
                    <span class="flex shrink-0 items-center justify-center rounded-xl bg-[#11386A] text-white" style="width: 44px; height: 44px;">
                        <span class="icon-mask" style="width: 20px; height: 20px; --icon: url('{{ asset('images/icons/' . $update['icon']) }}')"></span>
                    </span>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900">{{ $update['title'] }}</p>
                        <p class="text-sm text-gray-600">{{ $update['body'] }}</p>
                    </div>
                </div>
                <a href="{{ route('startup.notifications.show', $update['id']) }}"
                    class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-[#11386A] bg-white px-5 py-2.5 text-sm font-semibold text-[#11386A] transition hover:bg-[#11386A] hover:text-white sm:w-auto">
                    {{ $update['action'] }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;">
                        <path d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endforeach

        {{-- Overall Readiness --}}
        <div class="mb-5 rounded-2xl bg-gradient-to-r from-[#6C0E24] to-[#AE0129] p-5 text-white shadow-sm sm:mb-6 sm:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between lg:gap-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/80">
                        Overall Readiness
                        @if ($readinessStage)
                            <span class="text-white/60">({{ strtoupper(str_replace('-', ' ', $readinessStage)) }})</span>
                        @endif
                    </p>
                    <p class="mt-2 text-[34px] font-bold leading-none sm:text-[44px]">
                        {{ $assessment && $assessment->overall_score !== null ? number_format($assessment->overall_score, 1) : '—' }}
                        <span class="text-base font-medium text-white/70 sm:text-lg">/9</span>
                    </p>
                    <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold sm:mt-4">
                        <svg style="width: 14px; height: 14px;" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 13l5-5 3 3 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M13 5h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        {{ $overallLabel }}
                    </span>
                </div>

                <div class="grid grid-cols-4 gap-2 sm:gap-3 lg:w-auto">
                    @foreach (\App\Support\ReadinessRubric::TYPES as $type)
                        <div class="flex min-w-0 flex-col items-center justify-center rounded-xl bg-white/15 px-2 py-2 text-center sm:min-w-[118px] sm:px-4 sm:py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-white/70 sm:text-xs">{{ $type }}</p>
                            <p class="mt-0.5 text-[26px] font-bold leading-none sm:text-[40px]">
                                {{ $assessment?->scoreFor($type) ?? '—' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="mb-5 grid grid-cols-1 gap-3 sm:mb-6 sm:gap-4 lg:grid-cols-3 lg:gap-6">
            @php
                $quickLinks = [
                    ['route' => 'startup.submissions.index', 'icon' => 'airplane.svg', 'title' => 'Submit Roadblock?', 'description' => "Need an expert? Tell us you're stuck."],
                    ['route' => 'startup.meetings.index', 'icon' => 'coordProfile.svg', 'title' => 'My Meetings', 'description' => 'Check your schedule and upcoming events.'],
                    ['route' => 'startup.readiness.index', 'icon' => 'scale.svg', 'title' => 'Readiness Results', 'description' => 'See your TRL/MRL/TMRL/SRL details.'],
                ];
            @endphp
            @foreach ($quickLinks as $link)
                {{-- Row on phones and tablets (icon, text, chevron); stacked card from lg up. --}}
                <a href="{{ route($link['route']) }}"
                    class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition duration-200 hover:border-[#E8AFBF] hover:shadow-md sm:p-5 lg:block lg:p-6 lg:hover:-translate-y-0.5">
                    <span class="flex shrink-0 items-center justify-center rounded-xl bg-rose-50 text-[#6D0D23]" style="width: 48px; height: 48px;">
                        <span class="icon-mask" style="width: 22px; height: 22px; --icon: url('{{ asset('images/icons/' . $link['icon']) }}')"></span>
                    </span>
                    <div class="min-w-0 flex-1 lg:mt-4">
                        <p class="font-bold text-gray-900">{{ $link['title'] }}</p>
                        <div class="mt-1 flex items-end justify-between gap-3 sm:gap-4">
                            <p class="text-sm text-gray-500">{{ $link['description'] }}</p>
                            <span
                                class="flex shrink-0 items-center justify-center rounded-full bg-rose-50 text-[#6D0D23] transition duration-200 group-hover:bg-[#6D0D23] group-hover:text-white"
                                style="width: 34px; height: 34px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px;">
                                    <path d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Startup Onboarding & Graduation Progress --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-base font-bold text-gray-900">Startup Onboarding &amp; Graduation Progress</p>

            <div class="mt-3 border-t border-gray-200 sm:mt-4"></div>

            {{-- Label sits above the track on phones, beside it from sm up; the
                 track itself scrolls horizontally when it can't fit. --}}
            <div class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:gap-6">
                <div class="shrink-0">
                    <p class="text-sm font-bold text-gray-900">Onboarding</p>
                    <p class="text-sm text-gray-500">(Information Sheet)</p>
                </div>
                <div class="-mx-4 overflow-x-auto pb-1 sm:mx-0 sm:flex-1 sm:pb-0">
                    <div class="flex min-w-[420px] items-center xl:min-w-[620px]">
                        <x-step-tracker :steps="$onboardingSteps" />
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200"></div>

            <div class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:gap-6">
                <div class="shrink-0 sm:max-w-[100px]">
                    <p class="text-sm font-bold leading-snug text-gray-900">Graduation Roadmap</p>
                </div>
                <div class="-mx-4 overflow-x-auto pb-1 sm:mx-0 sm:flex-1 sm:pb-0">
                    <div class="flex min-w-[520px] items-center xl:min-w-[760px]">
                        <x-step-tracker :steps="$graduationSteps" />
                    </div>
                </div>
            </div>
        </div>
    @endif

</x-layouts.founder>
