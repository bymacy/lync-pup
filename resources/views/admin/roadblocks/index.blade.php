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

    // Archive tables share these: two columns fold away below lg and reappear
    // under the startup name, so a phone doesn't need a 700px scroll.
    $foldCol = 'hidden lg:table-cell';
    $archiveBtn = 'w-full whitespace-nowrap rounded-lg px-3 py-1.5 text-center sm:w-auto';
    @endphp
    <div x-data="{ tab: @js($initialTab), archiveStage: @js($initialArchiveStage) }"
        x-init="
            $watch('tab', value => setQueryParam('tab', value));
            $watch('archiveStage', value => setQueryParam('stage', value));
        ">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Roadblock Management</h1>
            <p class="mt-1 text-sm text-gray-500 sm:text-base">Review startup roadblocks and assign experts.</p>
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

            <div class="mb-10 grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 md:grid-cols-3 md:gap-6">
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

                            $card = 'rounded-lg border border-gray-300 p-3 sm:p-4';
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
                                        class="relative flex max-h-[90vh] w-[880px] max-w-full flex-col overflow-hidden rounded-xl bg-white text-left shadow-2xl"
                                        >

                                        {{-- Header --}}
                                        <div class="flex flex-shrink-0 items-center justify-between bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-4 text-white sm:px-8 sm:py-5">
                                            <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                                                <span class="flex-shrink-0 text-white">
                                                    {!! $icon('upcoming-mentorship.svg', 'w-5 h-5 sm:w-6 sm:h-6') !!}
                                                </span>

                                                <h3 class="truncate text-sm font-bold sm:text-base">Roadblock</h3>
                                            </div>

                                            {{-- STANDARD close button, dark-background variant --}}
                                            <button
                                                type="button"
                                                @click="viewOpen = false"
                                                class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                                                aria-label="Close">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- Body scrolls; the header stays put --}}
                                        <div class="flex flex-col gap-4 overflow-y-auto px-5 pb-5 pt-4 sm:gap-5 sm:px-8 sm:pb-6">

                                            {{-- Startup Information --}}
                                            <section>
                                                <p class="{{ $section }}">Startup Information</p>

                                                <div class="{{ $card }}">
                                                    <div class="grid gap-4 md:grid-cols-[1.15fr_1fr] sm:gap-6">

                                                        {{-- Left: name, category, batch --}}
                                                        <div class="space-y-3">
                                                            <div class="grid gap-3 md:grid-cols-2">
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
                                                            <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2">
                                                                @forelse ($roadblock->startup->teamMembers as $member)
                                                                <p class="{{ $pill }} truncate text-center">{{ $member->full_name }}</p>
                                                                @empty
                                                                <p class="text-sm text-gray-400 md:col-span-2">No team members listed.</p>
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
                                                    <div class="grid gap-3 md:grid-cols-2 lg:w-[62%]">
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

                                                        <div class="space-y-2 md:w-1/2">
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
                            <div x-show="assignOpen" x-cloak
                                @keydown.escape.window="assignOpen = false"
                                class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4 sm:p-6" style="display:none;">
                                <div class="flex min-h-full items-center justify-center">
                                    <div class="relative flex max-h-[90vh] w-[880px] max-w-full flex-col overflow-y-auto rounded-xl bg-white shadow-2xl"
                                        >
                                        <x-roadblock-assign-modal mode="assign" :roadblock="$roadblock" :mentors="$mentors" :coordinators="$coordinators" :action="route('admin.roadblocks.assign', $roadblock)" />
                                    </div>
                                </div>
                            </div>

                            {{-- Image preview lightbox --}}
                            <div x-show="previewImage" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4 sm:p-6" style="display:none;"
                                @click.self="previewImage = null" @keydown.escape.window="previewImage = null">
                                <button type="button" @click="previewImage = null" aria-label="Close preview"
                                    class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20 sm:right-5 sm:top-5">&times;</button>
                                <img :src="previewImage" class="max-h-full max-w-full rounded-lg object-contain">
                            </div>
                </div>
                @empty
                <p class="text-gray-500 col-span-full">No pending roadblocks.</p>
                @endforelse
            </div>

            <h2 class="mb-4 flex items-center gap-2 font-bold text-gray-900">
                <img src="{{ asset('images/icons/upcoming-mentorship.svg') }}" alt="" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                <span>Upcoming Mentorship</span>
            </h2>
            {{-- gateOnExactTime: stays editable until the meeting's actual start
                 time — these rows can be hours/days out, so swapping to "Join"
                 the moment the day arrives would be misleading here. --}}
            @include('admin.roadblocks._mentorship-table', ['rows' => $upcoming, 'gateOnExactTime' => true])
        </div>

        {{-- ============ SCHEDULED TODAY ============ --}}
        <div x-show="tab === 'today'">
            <h2 class="mb-4 flex items-center gap-2 font-bold text-gray-900">
                <img src="{{ asset('images/icons/upcoming-mentorship.svg') }}" alt="" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                <span>Mentorship Today</span>
            </h2>
            {{-- gateOnExactTime omitted (defaults to day-based isJoinable()):
                 already the meeting's day, so "Join" shows early to let hosts
                 get in before the exact start time. --}}
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

            <div class="mb-6 flex items-center gap-2">
                <label class="flex-shrink-0 text-sm font-medium">Stage:</label>

                <div class="relative inline-block" x-data="{ open: false }" @keydown.escape="open = false">
                    {{-- trigger --}}
                    <button type="button" @click="open = !open"
                        class="flex w-[140px] items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-2 text-sm text-gray-700 hover:border-gray-400 sm:w-[160px]">
                        <span class="truncate" x-text="{{ Js::from($stages) }}[archiveStage]"></span>
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- options --}}
                    <div x-show="open" x-cloak x-transition.origin.top
                        class="absolute left-0 top-full z-20 mt-1 w-[140px] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg sm:w-[160px]"
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
                        <table class="w-full min-w-[440px] text-sm lg:min-w-[700px]">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-left">
                                    <th class="px-3 py-3 font-semibold sm:px-4">Startup</th>
                                    <th class="{{ $foldCol }} px-4 py-3 font-semibold">Roadblock</th>
                                    <th class="px-3 py-3 font-semibold sm:px-4">Date</th>
                                    <th class="{{ $foldCol }} px-4 py-3 font-semibold">Mentor</th>
                                    <th class="px-3 py-3 font-semibold sm:px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assessment as $roadblock)
                                @php $avatarColor = $avatarPalette[$roadblock->startup->startup_id % count($avatarPalette)]; @endphp
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="px-3 py-3 align-top sm:px-4">
                                        <div class="flex items-start gap-2.5 sm:items-center sm:gap-3">
                                            @if ($roadblock->startup->startup_photo_url)
                                            <img src="{{ $roadblock->startup->startup_photo_url }}" alt=""
                                                class="h-7 w-7 flex-shrink-0 rounded-full object-cover sm:h-8 sm:w-8">
                                            @else
                                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-semibold text-white sm:h-8 sm:w-8">
                                                {{ strtoupper(substr($roadblock->startup->company_name, 0, 1)) }}
                                            </span>
                                            @endif

                                            <div class="min-w-0">
                                                <span class="font-medium text-gray-900">{{ $roadblock->startup->company_name }}</span>
                                                <p class="mt-0.5 text-xs text-gray-500 lg:hidden">{{ $roadblock->display_category }}</p>
                                                @if ($roadblock->assignee)
                                                <p class="text-xs text-gray-500 lg:hidden">{{ $roadblock->assignee->display_name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="{{ $foldCol }} px-4 py-3 align-top">{{ $roadblock->display_category }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 align-top sm:px-4">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                    <td class="{{ $foldCol }} px-4 py-3 align-top">{{ $roadblock->assignee?->display_name }}</td>
                                    <td class="px-3 py-3 align-top sm:px-4">
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <form method="POST" action="{{ route('admin.roadblocks.fail', $roadblock) }}">
                                                @csrf
                                                <button type="submit" class="{{ $archiveBtn }} border border-[#6D0D23] text-[#6D0D23] hover:bg-[#6D0D23]/5">Failed</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.roadblocks.resolve', $roadblock) }}">
                                                @csrf
                                                <button type="submit" class="{{ $archiveBtn }} bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white transition hover:opacity-95">Resolve</button>
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
                        <table class="w-full min-w-[440px] text-sm lg:min-w-[700px]">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-left">
                                    <th class="px-3 py-3 font-semibold sm:px-4">Startup</th>
                                    <th class="{{ $foldCol }} px-4 py-3 font-semibold">Roadblock</th>
                                    <th class="px-3 py-3 font-semibold sm:px-4">Date</th>
                                    <th class="{{ $foldCol }} px-4 py-3 font-semibold">Mentor</th>
                                    <th class="px-3 py-3 font-semibold sm:px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($resolved as $roadblock)
                                @php $avatarColor = $avatarPalette[$roadblock->startup->startup_id % count($avatarPalette)]; @endphp
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="px-3 py-3 align-top sm:px-4">
                                        <div class="flex items-start gap-2.5 sm:items-center sm:gap-3">
                                            @if ($roadblock->startup->startup_photo_url)
                                            <img src="{{ $roadblock->startup->startup_photo_url }}" alt=""
                                                class="h-7 w-7 flex-shrink-0 rounded-full object-cover sm:h-8 sm:w-8">
                                            @else
                                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-semibold text-white sm:h-8 sm:w-8">
                                                {{ strtoupper(substr($roadblock->startup->company_name, 0, 1)) }}
                                            </span>
                                            @endif

                                            <div class="min-w-0">
                                                <span class="font-medium text-gray-900">{{ $roadblock->startup->company_name }}</span>
                                                <p class="mt-0.5 text-xs text-gray-500 lg:hidden">{{ $roadblock->display_category }}</p>
                                                @if ($roadblock->assignee)
                                                <p class="text-xs text-gray-500 lg:hidden">{{ $roadblock->assignee->display_name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="{{ $foldCol }} px-4 py-3 align-top">{{ $roadblock->display_category }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 align-top sm:px-4">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                    <td class="{{ $foldCol }} px-4 py-3 align-top">{{ $roadblock->assignee?->display_name }}</td>
                                    <td class="px-3 py-3 align-top sm:px-4">
                                        <form method="POST" action="{{ route('admin.roadblocks.recover', $roadblock) }}">
                                            @csrf
                                            <button type="submit" class="{{ $archiveBtn }} border border-[#6D0D23] text-[#6D0D23] hover:bg-[#6D0D23]/5">Recover</button>
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
                        <table class="w-full min-w-[440px] text-sm lg:min-w-[700px]">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-left">
                                    <th class="px-3 py-3 font-semibold sm:px-4">Startup</th>
                                    <th class="{{ $foldCol }} px-4 py-3 font-semibold">Roadblock</th>
                                    <th class="px-3 py-3 font-semibold sm:px-4">Date</th>
                                    <th class="{{ $foldCol }} px-4 py-3 font-semibold">Mentor</th>
                                    <th class="px-3 py-3 font-semibold sm:px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($failed as $roadblock)
                                @php $avatarColor = $avatarPalette[$roadblock->startup->startup_id % count($avatarPalette)]; @endphp
                                <tr class="border-b border-gray-100 last:border-0" x-data="{ deleteOpen: false, rescheduleOpen: @js($erroredRoadblockId === $roadblock->roadblock_id) }">
                                    <td class="px-3 py-3 align-top sm:px-4">
                                        <div class="flex items-start gap-2.5 sm:items-center sm:gap-3">
                                            @if ($roadblock->startup->startup_photo_url)
                                            <img src="{{ $roadblock->startup->startup_photo_url }}" alt=""
                                                class="h-7 w-7 flex-shrink-0 rounded-full object-cover sm:h-8 sm:w-8">
                                            @else
                                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full {{ $avatarColor }} text-xs font-semibold text-white sm:h-8 sm:w-8">
                                                {{ strtoupper(substr($roadblock->startup->company_name, 0, 1)) }}
                                            </span>
                                            @endif

                                            <div class="min-w-0">
                                                <span class="font-medium text-gray-900">{{ $roadblock->startup->company_name }}</span>
                                                <p class="mt-0.5 text-xs text-gray-500 lg:hidden">{{ $roadblock->display_category }}</p>
                                                @if ($roadblock->assignee)
                                                <p class="text-xs text-gray-500 lg:hidden">{{ $roadblock->assignee->display_name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="{{ $foldCol }} px-4 py-3 align-top">{{ $roadblock->display_category }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 align-top sm:px-4">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                    <td class="{{ $foldCol }} px-4 py-3 align-top">{{ $roadblock->assignee?->display_name }}</td>
                                    <td class="px-3 py-3 align-top sm:px-4">
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <button type="button" @click="deleteOpen = true" class="{{ $archiveBtn }} border border-[#6D0D23] text-[#6D0D23] hover:bg-[#6D0D23]/5">Delete</button>
                                            <button type="button" @click="rescheduleOpen = true" class="{{ $archiveBtn }} bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white transition hover:opacity-95">Reschedule</button>
                                        </div>

                                        {{-- STANDARD delete confirmation --}}
                                        <div x-show="deleteOpen" x-cloak x-transition.opacity
                                            @keydown.escape.window="deleteOpen = false"
                                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none;">

                                            <div"
                                                class="relative w-full max-w-lg rounded-2xl bg-white px-5 pb-5 pt-8 text-center shadow-2xl sm:px-6">

                                                <button type="button" @click="deleteOpen = false"
                                                    class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full border border-gray-900 text-gray-900 transition hover:border-transparent hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white"
                                                    aria-label="Close">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                                                    </svg>
                                                </button>

                                                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A]">
                                                    <img src="{{ asset('images/icons/trash.svg') }}" alt="" class="h-5 w-5">
                                                </div>

                                                <h2 class="mt-2.5 bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-base font-bold text-transparent sm:text-lg">
                                                    Delete Roadblock
                                                </h2>

                                                <p class="mt-1.5 text-xs leading-5 text-gray-600">
                                                    Are you sure you want to delete this roadblock?<br>
                                                    This action is permanent and cannot be undone.
                                                </p>

                                                <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4">
                                                    <button type="button" @click="deleteOpen = false"
                                                        class="h-10 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50">
                                                        Cancel
                                                    </button>

                                                    <form method="POST" action="{{ route('admin.roadblocks.destroy', $roadblock) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="h-10 w-full rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div x-show="rescheduleOpen" x-cloak
                                            @keydown.escape.window="rescheduleOpen = false"
                                            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4 sm:p-6" style="display:none;">
                                            <div class="flex min-h-full items-center justify-center">
                                                <div class="relative flex max-h-[90vh] w-[880px] max-w-full flex-col overflow-y-auto rounded-xl bg-white shadow-2xl"
                                                   >
                                                    <x-roadblock-assign-modal mode="reschedule" :roadblock="$roadblock" :mentors="$mentors" :coordinators="$coordinators" :action="route('admin.roadblocks.assign', $roadblock)" />
                                                </div>
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

    {{--
        Auto-reload removed per request — this page used to reload itself
        every 60s so the Edit/Join swap and status labels stayed in sync
        with the clock without a manual refresh. That's also what caused
        the "changes up to a minute late" complaint: the swap only ever
        happened on the next reload tick, not at the exact scheduled time.
        Removing the timer trades that laggy auto-update for no
        auto-update at all — reopen this page (or navigate back to it) to
        see current Join/Edit state and status labels.
    --}}
</x-layouts.admin>