<x-layouts.admin title="Roadblock Management">
    @php
    // If the last submission (Assign/Edit/Reschedule) failed validation, figure out
    // which roadblock it was for so we can reopen the right modal on the right tab
    // instead of silently reloading with no visible sign anything went wrong.
    $erroredRoadblockId = $errors->any() ? (int) old('roadblock_id') : null;
    $erroredIsFailed = $erroredRoadblockId && $failed->contains('roadblock_id', $erroredRoadblockId);
    // A validation error always wins (surface it where it happened), otherwise
    // fall back to whatever tab/stage is in the URL — kept in sync client-side
    // so the 60s auto-refresh (and any manual reload) lands back where the
    // admin actually was instead of resetting to "Manage Roadblock".
    $initialTab = $erroredIsFailed ? 'archive' : request('tab', 'manage');
    $initialArchiveStage = $erroredIsFailed ? 'failed' : request('stage', 'assessment');
    @endphp
    <div x-data="{ tab: @js($initialTab), archiveStage: @js($initialArchiveStage) }"
        x-init="
            $watch('tab', value => setQueryParam('tab', value));
            $watch('archiveStage', value => setQueryParam('stage', value));
        ">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Roadblock Management</h1>
            <p class="text-gray-500 mt-1">Review startup roadblocks and assign experts.</p>
        </div>

        <div class="border-b border-gray-200 mb-6">
            {{-- Scrolls horizontally with shorter labels on phones instead of
            wrapping or overflowing the full desktop labels off-screen. --}}
            <nav class="flex gap-4 overflow-x-auto sm:gap-8">
                <button type="button" @click="tab = 'manage'" :class="tab === 'manage' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500'" class="whitespace-nowrap pb-3 border-b-2 text-sm font-medium sm:text-base">
                    <span class="sm:hidden">Manage</span>
                    <span class="hidden sm:inline">Manage Roadblock</span>
                </button>
                <button type="button" @click="tab = 'today'" :class="tab === 'today' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500'" class="whitespace-nowrap pb-3 border-b-2 text-sm font-medium sm:text-base">
                    <span class="sm:hidden">Today</span>
                    <span class="hidden sm:inline">Scheduled Today</span>
                </button>
                <button type="button" @click="tab = 'archive'" :class="tab === 'archive' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500'" class="whitespace-nowrap pb-3 border-b-2 text-sm font-medium sm:text-base">Archive</button>
            </nav>
        </div>

        {{-- ============ MANAGE ROADBLOCK ============ --}}
        <div x-show="tab === 'manage'">
            <h2 class="font-bold text-gray-900 mb-4">Pending Roadblock</h2>

            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                @forelse ($pending as $roadblock)
                @php $banners = ['from-purple-500 to-purple-700', 'from-blue-500 to-blue-700', 'from-teal-500 to-teal-700']; @endphp
                <div class="flex h-full flex-col overflow-hidden rounded-xl border"
                    x-data="{ viewOpen: false, assignOpen: @js($erroredRoadblockId === $roadblock->roadblock_id), previewImage: null }">

                    <div class="relative h-20 flex-shrink-0 bg-gradient-to-r {{ $banners[$roadblock->roadblock_id % count($banners)] }} sm:h-32">
                        <span class="absolute right-2 top-2 max-w-[calc(100%-1rem)] truncate rounded-full bg-white px-2 py-0.5 text-[10px] font-medium sm:right-3 sm:top-3 sm:px-3 sm:py-1 sm:text-xs">
                            {{ $roadblock->display_category }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col p-3 sm:p-4">
                        <p class="text-sm font-bold leading-snug sm:text-base">
                            {{ $roadblock->startup->company_name }}
                            <span class="text-rose-900">&bull;</span>
                            {{ $roadblock->display_category }}
                        </p>

                        <p class="mt-1 line-clamp-2 text-xs text-gray-500 sm:text-sm">{{ \Illuminate\Support\Str::limit($roadblock->description, 100) }}</p>

                        {{-- RLS score: guessing the relation name below, confirm/adjust if wrong --}}
                        <p class="mt-2 text-[11px] text-gray-500 sm:mt-3 sm:text-xs">RLS {{ $roadblock->startup->latestReadinessAssessment?->overall ?? 'N/A' }}</p>

                        <div class="mt-auto flex flex-col gap-2 pt-3 sm:flex-row sm:pt-4">
                            <button type="button" @click="viewOpen = true"
                                class="flex-1 rounded-lg border border-[#6D0D23] py-1.5 text-xs font-medium text-[#6D0D23] hover:bg-[#6D0D23]/5 sm:py-2 sm:text-sm">
                                View Details
                            </button>

                            {{-- solid red, no gradient --}}
                            <button type="button" @click="assignOpen = true"
                                class="flex-1 rounded-lg bg-[#6D0D23] py-1.5 text-xs font-medium text-white hover:bg-[#58091b] sm:py-2 sm:text-sm">
                                <span class="sm:hidden">Assign</span>
                                <span class="hidden sm:inline">Assign &amp; Schedule</span>
                            </button>
                        </div>
                    </div>

                    @php
                    // Same helper as the layout — component/partial scope doesn't inherit it.
                    // Guarded so it's harmless if this partial is included inside a loop.
                    if (! isset($icon)) {
                    $icon = function (string $name, string $class = 'w-4 h-4') {
                    $path = public_path('images/icons/' . $name);

                    if (! file_exists($path)) {
                    return '<span class="' . $class . ' inline-block"></span>';
                    }

                    $svg = file_get_contents($path);

                    $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 class="' . $class . ' block">', $svg, 1);
                            $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="currentColor"', $svg);
                            $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="currentColor"', $svg);

                            return $svg;
                            };
                            }

                            $card = 'rounded-lg border border-gray-300 p-4';
                            $section = 'mb-2 text-sm font-semibold text-gray-900';
                            $lbl = 'mb-1 block text-xs text-gray-500';
                            $pill = 'rounded bg-gray-100 px-3 py-2 text-sm text-gray-800';
                            @endphp

                            {{-- View Details modal --}}
                            <div
                                x-show="viewOpen"
                                x-cloak
                                @keydown.escape.window="viewOpen = false"
                                class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4 sm:p-6">

                                <div class="flex min-h-full items-center justify-center">
                                    <div
                                        class="relative flex w-[880px] max-w-full flex-col overflow-hidden rounded-xl bg-white text-left shadow-2xl"
                                        @click.outside="viewOpen = false">

                                        {{-- Header --}}
                                        <div class="flex flex-shrink-0 items-center justify-between bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-8 py-4 text-white">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <span class="flex-shrink-0 text-white">
                                                    {!! $icon('upcoming-mentorship.svg', 'w-6 h-6') !!}
                                                </span>

                                                <h3 class="truncate text-base font-bold">Roadblock</h3>
                                            </div>

                                            <button
                                                type="button"
                                                @click="viewOpen = false"
                                                class="flex-shrink-0 rounded-full text-white/85 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                                                aria-label="Close">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                                    <circle cx="12" cy="12" r="9.25" />
                                                    <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="flex flex-col gap-5 px-8 pb-6 pt-4">

                                            {{-- Startup Information --}}
                                            <section>
                                                <p class="{{ $section }}">Startup Information</p>

                                                <div class="{{ $card }}">
                                                    <div class="grid gap-4 sm:grid-cols-[1.15fr_1fr] sm:gap-6">

                                                        {{-- Left: name, category, batch --}}
                                                        <div class="space-y-3">
                                                            <div class="grid gap-3 sm:grid-cols-2">
                                                                <div>
                                                                    <label class="{{ $lbl }}">Startup Name</label>
                                                                    <p class="{{ $pill }} truncate">{{ $roadblock->startup->company_name }}</p>
                                                                </div>

                                                                <div>
                                                                    <label class="{{ $lbl }}">Category</label>
                                                                    <p class="{{ $pill }} truncate">{{ $roadblock->startup->industry_sector }}</p>
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">Batch</label>
                                                                <p class="{{ $pill }}">{{ $roadblock->startup->batch_label }}</p>
                                                            </div>
                                                        </div>

                                                        {{-- Right: team --}}
                                                        <div>
                                                            <label class="{{ $lbl }}">Team</label>

                                                            {{-- relation name is a guess: confirm teamMembers() on Startup --}}
                                                            <div class="grid grid-cols-2 gap-2.5">
                                                                @forelse ($roadblock->startup->teamMembers as $member)
                                                                <p class="{{ $pill }} truncate text-center">{{ $member->full_name }}</p>
                                                                @empty
                                                                <p class="col-span-2 text-sm text-gray-400">No team members listed.</p>
                                                                @endforelse
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>

                                            {{-- Roadblock Details --}}
                                            <section>
                                                <p class="{{ $section }}">Roadblock Details</p>

                                                <div class="{{ $card }} space-y-3">

                                                    {{-- Category / date stop short of the right edge, as in the design --}}
                                                    <div class="grid gap-3 sm:grid-cols-2 lg:w-[62%]">
                                                        <div>
                                                            <label class="{{ $lbl }}">Roadblock Category</label>
                                                            <p class="{{ $pill }} truncate">{{ $roadblock->display_category }}</p>
                                                        </div>

                                                        <div>
                                                            <label class="{{ $lbl }}">Date Submitted</label>
                                                            <p class="{{ $pill }}">{{ $roadblock->created_at->format('M d, Y') }}</p>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="{{ $lbl }}">Issue</label>
                                                        <p class="rounded bg-gray-100 px-3 py-3 text-sm leading-relaxed text-gray-800">
                                                            {{ $roadblock->description }}
                                                        </p>
                                                    </div>

                                                    @if ($roadblock->files->isNotEmpty())
                                                    <div>
                                                        <label class="{{ $lbl }}">Supporting Files</label>

                                                        <div class="space-y-2 sm:w-1/2">
                                                            @foreach ($roadblock->files as $file)
                                                            <div class="flex items-center justify-between gap-3 rounded-md border border-gray-300 px-3 py-2">
                                                                <span class="truncate text-sm text-[#9F1239]">{{ $file->original_filename }}</span>

                                                                <div class="flex flex-shrink-0 items-center gap-2.5 text-[#9F1239]">
                                                                    @if ($file->is_image)
                                                                    <button type="button" @click="previewImage = '{{ $file->url }}'"
                                                                        aria-label="Preview {{ $file->original_filename }}"
                                                                        class="transition hover:opacity-70">
                                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                        </svg>
                                                                    </button>
                                                                    @else
                                                                    <a href="{{ $file->url }}" target="_blank" rel="noopener"
                                                                        aria-label="Open {{ $file->original_filename }}"
                                                                        class="transition hover:opacity-70">
                                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                                        </svg>
                                                                    </a>
                                                                    @endif

                                                                    <a href="{{ $file->url }}" download="{{ $file->original_filename }}"
                                                                        aria-label="Download {{ $file->original_filename }}"
                                                                        class="transition hover:opacity-70">
                                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75V3m0 12.75l-3.75-3.75M12 15.75l3.75-3.75M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5" />
                                                                        </svg>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </section>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Assign & Schedule modal --}}
                            <div x-show="assignOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 sm:p-6" style="display:none;">
                                <div class="w-[880px] max-w-full rounded-xl bg-white overflow-hidden" @click.outside="assignOpen = false">
                                    <x-roadblock-assign-modal mode="assign" :roadblock="$roadblock" :mentors="$mentors" :coordinators="$coordinators" :action="route('admin.roadblocks.assign', $roadblock)" />
                                </div>
                            </div>

                            {{-- Image preview lightbox --}}
                            <div x-show="previewImage" x-cloak class="fixed inset-0 bg-black/80 flex items-center justify-center z-[60] p-6" style="display:none;"
                                @click.self="previewImage = null" @keydown.escape.window="previewImage = null">
                                <button type="button" @click="previewImage = null" aria-label="Close preview"
                                    class="absolute top-5 right-5 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white text-2xl hover:bg-white/20">&times;</button>
                                <img :src="previewImage" class="max-h-full max-w-full rounded-lg object-contain">
                            </div>
                </div>
                @empty
                <p class="text-gray-500 col-span-full">No pending roadblocks.</p>
                @endforelse
            </div>

            <h2 class="flex items-center gap-2 font-bold text-gray-900 mb-4">
                <img src="{{ asset('images/icons/upcoming-mentorship.svg') }}" alt="" class="w-6 h-6" aria-hidden="true">
                <span>Upcoming Mentorship</span>
            </h2>
            @include('admin.roadblocks._mentorship-table', ['rows' => $upcoming])
        </div>

        {{-- ============ SCHEDULED TODAY ============ --}}
        <div x-show="tab === 'today'">
            <h2 class="flex items-center gap-2 font-bold text-gray-900 mb-4">
                <img src="{{ asset('images/icons/upcoming-mentorship.svg') }}" alt="" class="w-6 h-6" aria-hidden="true">
                <span>Mentorship Today</span>
            </h2>
            @include('admin.roadblocks._mentorship-table', ['rows' => $scheduledToday])
        </div>

        {{-- ============ ARCHIVE ============ --}}
        <div x-show="tab === 'archive'">
            <h2 class="font-bold text-gray-900 mb-4">Mentorship Evaluation</h2>

            @php
            $stages = ['assessment' => 'Pending Review', 'resolved' => 'Resolved', 'failed' => 'Failed'];

            // Same fallback initials-avatar palette as the mentorship table, so a
            // startup shows the same color/letter everywhere it appears.
            $avatarPalette = ['bg-purple-600', 'bg-rose-600', 'bg-blue-600', 'bg-emerald-600', 'bg-teal-600', 'bg-indigo-600', 'bg-amber-600'];
            @endphp

            <div class="mb-6 flex items-center">
                <label class="text-sm font-medium mr-2">Stage:</label>

                <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    {{-- trigger --}}
                    <button type="button" @click="open = !open"
                        class="flex items-center justify-between gap-2 w-[160px] border border-gray-300 rounded-lg pl-3 pr-2 py-2 text-sm bg-white text-gray-700 hover:border-gray-400">
                        <span x-text="{{ Js::from($stages) }}[archiveStage]"></span>
                        <svg class="h-4 w-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- options --}}
                    <div x-show="open" x-cloak x-transition.origin.top
                        class="absolute left-0 top-full mt-1 w-[160px] rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden z-20"
                        style="display:none;">
                        @foreach ($stages as $value => $label)
                        {{-- Gradient is the ONLY accent color here, and only while actually
                        hovered/pressed — no separate "selected" color that stays on
                        afterward. Gated to `(hover: hover)` so a tap on a phone (no real
                        pointer to move away) can't leave the gradient stuck on until the
                        next tap elsewhere. --}}
                        <button type="button"
                            @click="archiveStage = '{{ $value }}'; open = false"
                            class="w-full text-left px-3 py-2 text-sm text-gray-700 transition [@media(hover:hover)]:hover:bg-gradient-to-r [@media(hover:hover)]:hover:from-[#6D0D23] [@media(hover:hover)]:hover:to-[#11386A] [@media(hover:hover)]:hover:text-white">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Assessment stage --}}
            <div x-show="archiveStage === 'assessment'">
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-sm">
                        <thead>
                            <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-left">
                                <th class="px-4 py-3 font-semibold">Startup</th>
                                <th class="px-4 py-3 font-semibold">Roadblock</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">Mentor</th>
                                <th class="px-4 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assessment as $roadblock)
                            @php $avatarColor = $avatarPalette[$roadblock->startup->startup_id % count($avatarPalette)]; @endphp
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($roadblock->startup->startup_photo_url)
                                        <img src="{{ $roadblock->startup->startup_photo_url }}" alt=""
                                            class="h-8 w-8 flex-shrink-0 rounded-full object-cover">
                                        @else
                                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-semibold text-white">
                                            {{ strtoupper(substr($roadblock->startup->company_name, 0, 1)) }}
                                        </span>
                                        @endif
                                        <span class="font-medium text-gray-900">{{ $roadblock->startup->company_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ $roadblock->display_category }}</td>
                                <td class="px-4 py-3">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">{{ $roadblock->assignee?->display_name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.roadblocks.fail', $roadblock) }}">
                                            @csrf
                                            <button type="submit" class="border border-[#6D0D23] text-[#6D0D23] rounded-lg px-3 py-1.5 hover:bg-[#6D0D23]/5">Failed</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.roadblocks.resolve', $roadblock) }}">
                                            @csrf
                                            <button type="submit" class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg px-3 py-1.5">Resolve</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">Nothing pending review.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            {{-- Resolved stage --}}
            <div x-show="archiveStage === 'resolved'">
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3 mb-4 text-sm">
                    These roadblocks have been successfully resolved.
                </div>
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-sm">
                        <thead>
                            <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-left">
                                <th class="px-4 py-3 font-semibold">Startup</th>
                                <th class="px-4 py-3 font-semibold">Roadblock</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">Mentor</th>
                                <th class="px-4 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($resolved as $roadblock)
                            @php $avatarColor = $avatarPalette[$roadblock->startup->startup_id % count($avatarPalette)]; @endphp
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($roadblock->startup->startup_photo_url)
                                        <img src="{{ $roadblock->startup->startup_photo_url }}" alt=""
                                            class="h-8 w-8 flex-shrink-0 rounded-full object-cover">
                                        @else
                                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-semibold text-white">
                                            {{ strtoupper(substr($roadblock->startup->company_name, 0, 1)) }}
                                        </span>
                                        @endif
                                        <span class="font-medium text-gray-900">{{ $roadblock->startup->company_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ $roadblock->display_category }}</td>
                                <td class="px-4 py-3">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">{{ $roadblock->assignee?->display_name }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.roadblocks.recover', $roadblock) }}">
                                        @csrf
                                        <button type="submit" class="border border-[#6D0D23] text-[#6D0D23] rounded-lg px-3 py-1.5 hover:bg-[#6D0D23]/5">Recover</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No resolved roadblocks.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            {{-- Failed stage --}}
            <div x-show="archiveStage === 'failed'">
                <div class="bg-rose-50 text-rose-800 border border-rose-200 rounded-lg px-4 py-3 mb-4 text-sm">
                    Efforts to resolve these roadblocks were unsuccessful.
                </div>
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-sm">
                        <thead>
                            <tr class="bg-gradient-to-b from-[#6D0D23] to-[#11386A] text-white text-left">
                                <th class="px-4 py-3 font-semibold">Startup</th>
                                <th class="px-4 py-3 font-semibold">Roadblock</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">Mentor</th>
                                <th class="px-4 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($failed as $roadblock)
                            @php $avatarColor = $avatarPalette[$roadblock->startup->startup_id % count($avatarPalette)]; @endphp
                            <tr class="border-b border-gray-100 last:border-0" x-data="{ deleteOpen: false, rescheduleOpen: @js($erroredRoadblockId === $roadblock->roadblock_id) }">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($roadblock->startup->startup_photo_url)
                                        <img src="{{ $roadblock->startup->startup_photo_url }}" alt=""
                                            class="h-8 w-8 flex-shrink-0 rounded-full object-cover">
                                        @else
                                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-semibold text-white">
                                            {{ strtoupper(substr($roadblock->startup->company_name, 0, 1)) }}
                                        </span>
                                        @endif
                                        <span class="font-medium text-gray-900">{{ $roadblock->startup->company_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ $roadblock->display_category }}</td>
                                <td class="px-4 py-3">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">{{ $roadblock->assignee?->display_name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button type="button" @click="deleteOpen = true" class="border border-[#6D0D23] text-[#6D0D23] rounded-lg px-3 py-1.5 hover:bg-[#6D0D23]/5">Delete</button>
                                        <button type="button" @click="rescheduleOpen = true" class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg px-3 py-1.5">Reschedule</button>
                                    </div>

                                    <div x-show="deleteOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                                        <div class="bg-white rounded-2xl w-full max-w-md p-8 text-center" @click.outside="deleteOpen = false">
                                            <h3 class="text-xl font-semibold mb-2">Delete Roadblock</h3>
                                            <p class="text-sm text-gray-600 mb-6">This action is permanent and cannot be undone.</p>
                                            <div class="flex gap-3 justify-center">
                                                <button type="button" @click="deleteOpen = false" class="border rounded-lg px-5 py-2.5 text-sm font-medium">Cancel</button>
                                                <form method="POST" action="{{ route('admin.roadblocks.destroy', $roadblock) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg px-5 py-2.5 text-sm font-medium">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div x-show="rescheduleOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 sm:p-6" style="display:none;">
                                        <div class="w-[880px] max-w-full rounded-xl bg-white overflow-hidden" @click.outside="rescheduleOpen = false">
                                            <x-roadblock-assign-modal mode="reschedule" :roadblock="$roadblock" :mentors="$mentors" :coordinators="$coordinators" :action="route('admin.roadblocks.assign', $roadblock)" />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No failed roadblocks.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // This page has no live/reactive data — "Edit" only becomes "Join" once
        // the meeting's start time has passed and the page is re-rendered by the
        // server (Roadblock::isLive() is computed at load time). Without this,
        // an admin sitting on the page past a meeting's start time would keep
        // seeing "Edit" until they manually refresh. Auto-reload every 60s to
        // keep that in sync, but skip the reload while any modal is open (any
        // x-cloak element currently visible) so it doesn't interrupt someone
        // mid-Assign/Edit/Reschedule, and skip it while the tab isn't visible
        // to avoid pointless background reloads.
        (function() {
            const REFRESH_MS = 60000;

            setInterval(function() {
                if (document.hidden) return;

                const aModalIsOpen = Array.from(document.querySelectorAll('[x-cloak]'))
                    .some((el) => el.style.display !== 'none');

                if (aModalIsOpen) return;

                window.location.reload();
            }, REFRESH_MS);
        })();
    </script>
</x-layouts.admin>