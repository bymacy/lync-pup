<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lync PUP-TBIDO — Where Innovation Meets Opportunity</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-data="landingPage()" class="antialiased bg-white">

    {{-- ==================== HERO ==================== --}}
    <header class="relative overflow-hidden bg-gradient-to-br from-[#4A0A18] via-[#2C0F35] to-[#11386A] pb-24 pt-10 text-white">

        {{-- Decorative circuit-style background — purely visual, no data. --}}
        <div class="pointer-events-none absolute inset-0 opacity-20">
            <svg class="h-full w-full" viewBox="0 0 1200 800" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="1000" cy="150" r="120" stroke="white" stroke-width="1" />
                <circle cx="1000" cy="150" r="80" stroke="white" stroke-width="1" />
                <path d="M700 100 L750 100 L750 150 L820 150" stroke="white" stroke-width="1" />
                <path d="M650 300 L720 300 L720 250" stroke="white" stroke-width="1" />
                <path d="M900 400 L960 400 L960 460 L1020 460" stroke="white" stroke-width="1" />
                <circle cx="150" cy="500" r="2" fill="white" />
                <circle cx="200" cy="520" r="2" fill="white" />
                <circle cx="180" cy="560" r="2" fill="white" />
            </svg>
        </div>

        <div class="relative mx-auto max-w-6xl px-6">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 text-xs font-bold text-[#6D0D23] shadow-sm">
                <x-icon name="upcoming-mentorship.svg" class="h-3.5 w-3.5" />
                LYNC PUP MANAGEMENT SYSTEM
            </span>

            <h1 class="mt-6 max-w-2xl text-4xl font-extrabold leading-tight sm:text-5xl">
                Where <span class="text-amber-400">Innovation</span>
                <br>
                Meets <span class="text-amber-400">Opportunity.</span>
            </h1>

            <p class="mt-5 max-w-xl text-sm text-white/80 sm:text-base">
                PUP TBIDO empowers startups to transform ideas into impactful ventures with the support
                of experts, networks, and real-world resources.
            </p>

            <div class="mt-8 inline-flex flex-wrap items-stretch divide-x divide-gray-200 rounded-2xl bg-white text-center text-[#11386A] shadow-lg">
                <div class="px-6 py-3">
                    <p class="text-2xl font-extrabold">{{ $stats['active_ventures'] }}</p>
                    <p class="text-xs font-medium text-gray-500">Active Ventures</p>
                </div>
                <div class="px-6 py-3">
                    <p class="text-2xl font-extrabold">{{ $stats['sectors'] }}</p>
                    <p class="text-xs font-medium text-gray-500">Sectors</p>
                </div>
                <div class="px-6 py-3">
                    <p class="text-2xl font-extrabold">{{ $stats['graduated'] }}</p>
                    <p class="text-xs font-medium text-gray-500">Graduated</p>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#cohorts"
                    @click.prevent="scrollToCohorts()"
                    class="inline-flex items-center gap-2 rounded-full bg-[#6D0D23] px-6 py-3 text-sm font-bold text-white shadow-lg transition hover:opacity-90">
                    Explore Startups
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>

                <a href="{{ route('login') }}"
                    class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-[#11386A] shadow-lg transition hover:bg-gray-50">
                    Founder / Admin Login
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    {{-- ==================== MEET OUR INCUBATEES ==================== --}}
    <main class="relative -mt-14 rounded-t-[2.5rem] bg-white pb-16 pt-10">
        <div id="cohorts" class="mx-auto max-w-6xl scroll-mt-8 px-6">
            <div class="text-center">
                <h2 class="text-2xl font-extrabold text-gray-900 sm:text-3xl">
                    Meet Our <span class="text-[#11386A]">Incubatees</span>
                </h2>
                <p class="mt-1 text-sm text-gray-500">Innovative Startups. Real Solutions. Growing Impact.</p>
            </div>

            @if ($cohortShowcase->isEmpty())
                <p class="mt-10 text-center text-sm text-gray-400">No approved startups to show yet.</p>
            @else
                {{-- Cohort tabs --}}
                <div class="mt-8 flex justify-center gap-8 border-b border-gray-200">
                    @foreach ($cohortShowcase as $index => $group)
                        <button type="button"
                            @click="activeCohortIndex = {{ $index }}"
                            class="relative -mb-px pb-3 text-sm font-bold transition"
                            :class="activeCohortIndex === {{ $index }} ? 'text-[#6D0D23]' : 'text-gray-500 hover:text-gray-700'">
                            {{ $group['cohort']->display_label }}
                            <span class="absolute inset-x-0 -bottom-px h-0.5 rounded-full transition"
                                :class="activeCohortIndex === {{ $index }} ? 'bg-[#6D0D23]' : 'bg-transparent'"></span>
                        </button>
                    @endforeach
                </div>

                {{-- Cohort panels --}}
                @foreach ($cohortShowcase as $index => $group)
                    @php
                        $paletteBg = ['bg-purple-600', 'bg-red-600', 'bg-blue-600', 'bg-gray-100'];
                        $paletteTone = ['text-white', 'text-white', 'text-white', 'text-blue-600'];
                    @endphp
                    <div x-show="activeCohortIndex === {{ $index }}" {{ $index === 0 ? '' : 'x-cloak' }} class="mt-8">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($group['startups'] as $sIndex => $startup)
                                <div x-show="{{ $sIndex }} < 4 || isExpanded({{ $group['cohort']->cohort_id }})"
                                    class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 shadow-sm transition hover:shadow-md">
                                    <div class="{{ $paletteBg[$startup['palette_index']] }} flex h-28 items-center justify-center relative">
                                        <span class="absolute right-3 top-3 rounded-full bg-white px-2.5 py-1 text-[10px] font-semibold text-gray-700">
                                            {{ $startup['stage_label'] }}
                                        </span>
                                        @if ($startup['photo_url'])
                                            <img src="{{ $startup['photo_url'] }}" alt="" class="h-12 w-12 rounded-lg object-cover">
                                        @else
                                            <span class="{{ $paletteTone[$startup['palette_index']] }} text-2xl font-extrabold">
                                                {{ strtoupper(substr($startup['name'], 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-1 flex-col gap-2 p-4">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">{{ $startup['name'] }}</p>
                                            <p class="text-[11px] text-gray-500">{{ $startup['sector'] ?? 'Uncategorized' }} &middot; {{ $startup['cohort_label'] }}</p>
                                        </div>

                                        <p class="min-h-[2.5rem] flex-1 text-[11px] leading-relaxed text-gray-500 line-clamp-2">
                                            {{ $startup['description'] ?? 'No description submitted yet.' }}
                                        </p>

                                        <div class="flex items-center justify-between text-[11px] text-gray-500">
                                            <span class="flex min-w-0 items-center gap-1">
                                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3 shrink-0 text-gray-400">
                                                    <path fill-rule="evenodd" d="M9.69 18.933a.75.75 0 0 0 .62 0c.058-.026 8.19-3.86 8.19-9.933a8.5 8.5 0 1 0-17 0c0 6.073 8.132 9.907 8.19 9.933ZM10 12.5A3 3 0 1 0 10 6.5a3 3 0 0 0 0 6Z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="truncate">{{ $startup['location'] ?? '—' }}</span>
                                            </span>

                                            @if ($startup['overall_score'] !== null)
                                                <span class="flex shrink-0 items-center gap-1 font-semibold text-emerald-600">
                                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3">
                                                        <path fill-rule="evenodd" d="M12 5a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0V6.81l-5.22 5.22a.75.75 0 0 1-1.06 0L7.5 9.06l-4.72 4.72a.75.75 0 0 1-1.06-1.06l5.25-5.25a.75.75 0 0 1 1.06 0l2.97 2.97L16.19 5.75h-3.44A.75.75 0 0 1 12 5Z" clip-rule="evenodd" />
                                                    </svg>
                                                    RLS {{ number_format($startup['overall_score'], 1) }}
                                                </span>
                                            @endif
                                        </div>

                                        <button type="button"
                                            @click="openStartup(@js($startup))"
                                            class="mt-1 w-full rounded-lg border border-rose-800 py-1.5 text-center text-xs font-semibold text-rose-900 transition hover:bg-rose-50">
                                            View
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($group['startups']->count() > 4)
                            <div class="mt-6 flex justify-center" x-show="!isExpanded({{ $group['cohort']->cohort_id }})">
                                <button type="button" @click="expandCohort({{ $group['cohort']->cohort_id }})"
                                    class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-5 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50">
                                    View All Startups
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        {{-- ==================== ABOUT LYNC PUP ==================== --}}
        <div class="mx-auto mt-16 max-w-6xl px-6">
            <div class="rounded-2xl bg-gradient-to-r from-[#6D0D23] to-[#11386A] p-8 text-white">
                <h3 class="text-xl font-extrabold">About Lync PUP</h3>
                <p class="mt-2 max-w-3xl text-sm text-white/80">
                    A centralized management system that streamlines the incubation lifecycle through automated progress
                    monitoring, data-driven readiness assessments, and secure intellectual property governance.
                </p>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['icon' => 'check-box.svg', 'title' => 'Readiness', 'body' => 'Track TRL, MRL, TMRL & SRL signals across every venture.'],
                        ['icon' => 'riskMon.svg', 'title' => 'Progress Analytics', 'body' => 'Identify at-risk ventures through real-time monitoring.'],
                        ['icon' => '3person.svg', 'title' => 'Mentoring', 'body' => 'Connect with experts to clear roadblocks.'],
                        ['icon' => 'coordProfile.svg', 'title' => 'Centralized', 'body' => 'Incubation lifecycle through a unified growth portal.'],
                    ] as $feature)
                        <div class="rounded-xl bg-white p-4 text-gray-900">
                            <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-[#6D0D23] text-white">
                                <x-icon name="{{ $feature['icon'] }}" class="h-5 w-5" />
                            </span>
                            <p class="text-sm font-bold">{{ $feature['title'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $feature['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </main>

    {{-- ==================== STARTUP DETAIL MODAL ==================== --}}
    <div x-show="modalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
        @keydown.escape.window="closeStartup()">
        <div class="my-auto w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="closeStartup()">
            <template x-if="activeStartup">
                <div>
                    {{-- Header banner --}}
                    <div class="flex items-center gap-3 bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-5 text-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="3.25" />
                                <path stroke-linecap="round" d="M4.5 19.5c0-3.4 3.4-5.4 7.5-5.4s7.5 2 7.5 5.4" />
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold">Startup</h3>
                        <button type="button" @click="closeStartup()" aria-label="Close"
                            class="ml-auto flex h-6 w-6 items-center justify-center rounded-full border border-white text-white transition hover:bg-white hover:text-[#6D0D23]">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[80vh] overflow-y-auto p-6">
                        {{-- Title row --}}
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-[#6D0D23]/10 text-lg font-extrabold text-[#6D0D23]"
                                x-text="activeStartup.name.charAt(0).toUpperCase()"></span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-extrabold text-gray-900" x-text="activeStartup.name"></h2>
                                    <span class="rounded-full border border-gray-300 px-2.5 py-0.5 text-[11px] font-semibold text-gray-600" x-text="activeStartup.stage_label"></span>
                                </div>
                                <p class="text-sm text-gray-500" x-text="activeStartup.description || 'No description submitted yet.'"></p>
                            </div>
                        </div>

                        <p class="mt-3 text-xs font-medium text-gray-500">
                            <span x-text="activeStartup.sector || 'Uncategorized'"></span> &middot;
                            <span x-text="activeStartup.cohort_label"></span> &middot;
                            <span x-text="activeStartup.location || '—'"></span>
                        </p>

                        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_260px]">
                            <div>
                                {{-- Readiness Level --}}
                                <div class="flex items-center justify-between">
                                    <p class="flex items-center gap-1.5 text-sm font-bold text-[#11386A]">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M12 5a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0V6.81l-5.22 5.22a.75.75 0 0 1-1.06 0L7.5 9.06l-4.72 4.72a.75.75 0 0 1-1.06-1.06l5.25-5.25a.75.75 0 0 1 1.06 0l2.97 2.97L16.19 5.75h-3.44A.75.75 0 0 1 12 5Z" clip-rule="evenodd" />
                                        </svg>
                                        Readiness Level
                                    </p>

                                    <template x-if="Object.keys(activeStartup.stages).length">
                                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                            <button type="button" @click="open = !open"
                                                class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700">
                                                <span x-text="stageKey"></span>
                                                <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                            <div x-show="open" x-cloak class="absolute right-0 z-10 mt-1 w-40 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
                                                <template x-for="key in Object.keys(activeStartup.stages)" :key="key">
                                                    <button type="button" @click="stageKey = key; open = false"
                                                        class="block w-full px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-50" x-text="key"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <template x-if="currentStage">
                                    <div class="mt-3 rounded-xl border border-gray-200 p-4">
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-[auto_1fr]">
                                            {{-- Radar --}}
                                            <svg viewBox="0 0 200 200" class="mx-auto h-44 w-44">
                                                <polygon points="100,20 180,100 100,180 20,100" fill="none" stroke="#E5E7EB" stroke-width="1" />
                                                <polygon points="100,47 153,100 100,153 47,100" fill="none" stroke="#E5E7EB" stroke-width="1" />
                                                <polygon points="100,73 127,100 100,127 73,100" fill="none" stroke="#E5E7EB" stroke-width="1" />
                                                <line x1="100" y1="100" x2="100" y2="20" stroke="#E5E7EB" />
                                                <line x1="100" y1="100" x2="180" y2="100" stroke="#E5E7EB" />
                                                <line x1="100" y1="100" x2="100" y2="180" stroke="#E5E7EB" />
                                                <line x1="100" y1="100" x2="20" y2="100" stroke="#E5E7EB" />

                                                <polygon :points="radarPolygon" fill="#6D0D2333" stroke="#6D0D23" stroke-width="2" />

                                                <text x="100" y="12" text-anchor="middle" class="fill-gray-500" style="font-size:9px">TRL <tspan x="100" dy="10" x-text="(scoreFor('TRL') ?? 0) + '/9'"></tspan></text>
                                                <text x="188" y="103" text-anchor="start" class="fill-gray-500" style="font-size:9px">MRL <tspan x="188" dy="10" x-text="(scoreFor('MRL') ?? 0) + '/9'"></tspan></text>
                                                <text x="100" y="196" text-anchor="middle" class="fill-gray-500" style="font-size:9px">TMRL <tspan x="100" dy="-2" x-text="(scoreFor('TMRL') ?? 0) + '/9'"></tspan></text>
                                                <text x="12" y="103" text-anchor="end" class="fill-gray-500" style="font-size:9px">SRL <tspan x="12" dy="10" x-text="(scoreFor('SRL') ?? 0) + '/9'"></tspan></text>
                                            </svg>

                                            {{-- Score cards --}}
                                            <div class="grid grid-cols-2 gap-2.5">
                                                <template x-for="[type, cardLabel] in [['TRL','TECHNOLOGY'],['MRL','MANUFACTURING'],['TMRL','TEAM & MGMT'],['SRL','SYSTEM / MARKET']]" :key="type">
                                                    <div class="rounded-lg border border-gray-200 p-2.5">
                                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500" x-text="cardLabel"></p>
                                                        <p class="text-sm font-extrabold text-gray-900">
                                                            <span x-text="type"></span>
                                                            <span x-text="(scoreFor(type) ?? 0) + '/9'"></span>
                                                        </p>
                                                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                                            <div class="h-full rounded-full bg-[#6D0D23]" :style="`width:${((scoreFor(type) ?? 0)/9)*100}%`"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <p class="mt-3 text-xs text-gray-500">
                                            Composite RLS score:
                                            <span class="font-bold text-gray-800" x-text="(currentStage.overall_score ?? '—') + (currentStage.overall_score !== null ? '/9' : '')"></span>
                                        </p>
                                    </div>
                                </template>
                                <template x-if="!currentStage">
                                    <p class="mt-3 rounded-xl border border-gray-200 p-4 text-sm text-gray-400">Not assessed yet.</p>
                                </template>

                                {{-- Team --}}
                                <p class="mt-6 flex items-center gap-1.5 text-sm font-bold text-[#11386A]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="9" cy="7" r="3" /><path stroke-linecap="round" d="M2 19c0-3 3-5 7-5s7 2 7 5" /><path stroke-linecap="round" d="M16 5.5a3 3 0 010 5.8M21 19c0-2.5-2-4.3-4.5-4.9" />
                                    </svg>
                                    Team
                                </p>
                                <div class="mt-2 grid grid-cols-2 gap-2.5">
                                    <template x-for="member in activeStartup.team" :key="member">
                                        <p class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm text-gray-700" x-text="member"></p>
                                    </template>
                                    <p x-show="!activeStartup.team.length" class="col-span-2 text-sm text-gray-400">No team members listed.</p>
                                </div>
                            </div>

                            {{-- Contact & Links --}}
                            <div class="h-fit rounded-xl border border-gray-200 p-4">
                                <p class="text-sm font-bold text-gray-900">Contact &amp; Links</p>
                                <div class="mt-3 space-y-2.5 text-sm text-gray-600">
                                    <p x-show="activeStartup.website" class="flex items-center gap-2 truncate">
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M3 12h18M12 3a14 14 0 010 18 14 14 0 010-18Z" /></svg>
                                        <span class="truncate" x-text="activeStartup.website"></span>
                                    </p>
                                    <p x-show="activeStartup.email" class="flex items-center gap-2 truncate">
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2" /><path stroke-linecap="round" d="m3 7 9 6 9-6" /></svg>
                                        <span class="truncate" x-text="activeStartup.email"></span>
                                    </p>
                                    <p x-show="activeStartup.phone" class="flex items-center gap-2 truncate">
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5c0 9 7 16 16 16l3-4-6-3-2 2c-3-1.5-5-3.5-6.5-6.5l2-2-3-6-4 .5Z" /></svg>
                                        <span x-text="activeStartup.phone"></span>
                                    </p>
                                    <p class="flex items-center gap-2 truncate">
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.69 18.933a.75.75 0 0 0 .62 0c.058-.026 8.19-3.86 8.19-9.933a8.5 8.5 0 1 0-17 0c0 6.073 8.132 9.907 8.19 9.933ZM10 12.5A3 3 0 1 0 10 6.5a3 3 0 0 0 0 6Z" clip-rule="evenodd" /></svg>
                                        <span x-text="activeStartup.location || '—'"></span>
                                    </p>
                                </div>

                                <a :href="`mailto:${activeStartup.email || ''}?subject=${encodeURIComponent('Pitch Deck Request — ' + activeStartup.name)}`"
                                    class="mt-4 block w-full rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-2.5 text-center text-sm font-bold text-white transition hover:opacity-95">
                                    Request Pitch Deck
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        function landingPage() {
            return {
                activeCohortIndex: 0,
                expandedCohorts: [],
                activeStartup: null,
                stageKey: null,
                modalOpen: false,

                scrollToCohorts() {
                    document.getElementById('cohorts')?.scrollIntoView({ behavior: 'smooth' });
                },

                isExpanded(cohortId) {
                    return this.expandedCohorts.includes(cohortId);
                },

                expandCohort(cohortId) {
                    this.expandedCohorts.push(cohortId);
                },

                openStartup(startup) {
                    this.activeStartup = startup;
                    this.stageKey = startup.default_stage;
                    this.modalOpen = true;
                },

                closeStartup() {
                    this.modalOpen = false;
                },

                get currentStage() {
                    return this.activeStartup?.stages?.[this.stageKey] ?? null;
                },

                scoreFor(type) {
                    const score = this.currentStage?.scores?.[type];
                    return score === null || score === undefined ? null : score;
                },

                // Angles: 0deg = top (TRL), 90 = right (MRL), 180 = bottom
                // (TMRL), 270 = left (SRL) — offset by -90 so 0deg plots
                // straight up instead of the trig default of straight right.
                radarPoint(type, angleDeg) {
                    const score = this.scoreFor(type) ?? 0;
                    const r = (score / 9) * 80;
                    const rad = (angleDeg - 90) * Math.PI / 180;
                    const x = 100 + r * Math.cos(rad);
                    const y = 100 + r * Math.sin(rad);
                    return `${x.toFixed(1)},${y.toFixed(1)}`;
                },

                get radarPolygon() {
                    return [
                        this.radarPoint('TRL', 0),
                        this.radarPoint('MRL', 90),
                        this.radarPoint('TMRL', 180),
                        this.radarPoint('SRL', 270),
                    ].join(' ');
                },
            };
        }
    </script>
</body>

</html>
