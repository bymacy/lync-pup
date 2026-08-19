@php
// Same helper as the layout — partial scope doesn't inherit it.
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

        // Shared with the Roadblock view modal so both stay identical
        $card = 'rounded-lg border border-gray-300 p-4';
        $section = 'mb-2 text-sm font-semibold text-gray-900';
        $lbl = 'mb-1 block text-xs text-gray-500';
        $pill = 'rounded bg-gray-100 px-3 py-2 text-sm text-gray-800';
        @endphp

        <div class="mb-10 overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-left text-white">
                            <th class="px-4 py-3 font-semibold">Startup</th>
                            <th class="px-4 py-3 font-semibold">Roadblock</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Meeting Schedule</th>
                            <th class="px-4 py-3 font-semibold">Mentor / Coordinator</th>
                            <th class="px-4 py-3 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $erroredRoadblockId = $errors->any() ? (int) old('roadblock_id') : null;

                        // Fallback initials-avatar palette for startups with no uploaded photo.
                        $avatarPalette = ['bg-purple-600', 'bg-rose-600', 'bg-blue-600', 'bg-emerald-600', 'bg-teal-600', 'bg-indigo-600', 'bg-amber-600'];

                        // Row tint + status wording key off the same tone, so the color always
                        // matches what the status column says.
                        $rowTintByTone = [
                            'live' => 'bg-rose-50',
                            'today' => 'bg-green-50',
                            'tomorrow' => 'bg-yellow-50',
                        ];

                        // File name (in public/images/icons) for each meeting platform's logo.
                        $platformIcons = [
                            'Google Meet' => 'gmeet.png',
                            'Zoom' => 'zoom.png',
                            'Microsoft Teams' => 'msteams.png',
                        ];
                        @endphp

                        @forelse ($rows as $roadblock)
                        @php
                        $avatarColor = $avatarPalette[$roadblock->startup->startup_id % count($avatarPalette)];
                        $rowTint = $rowTintByTone[$roadblock->meeting_status_tone] ?? '';
                        $platformIcon = $platformIcons[$roadblock->meeting_platform] ?? null;

                        // Split "Live (In-Session)" into a bold main word and a lighter sub-label
                        // so the status column renders on two lines, as in the design.
                        $statusMain = $roadblock->meeting_status_label;
                        $statusSub = null;
                        if (preg_match('/^(.*?)\s*(\(.*\))$/', $roadblock->meeting_status_label, $statusParts)) {
                        $statusMain = $statusParts[1];
                        $statusSub = $statusParts[2];
                        }
                        @endphp
                        <tr class="border-b border-gray-100 last:border-0 {{ $rowTint }}"
                            x-data="{ viewOpen: false, editOpen: @js($erroredRoadblockId === $roadblock->roadblock_id) }">

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
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900">{{ $statusMain }}</p>
                                @if ($statusSub)
                                <p class="text-xs text-gray-500">{{ $statusSub }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-gray-700">
                                    <span class="flex-shrink-0 text-gray-400">{!! $icon('cal.svg', 'w-3.5 h-3.5') !!}</span>
                                    <span>{{ $roadblock->meeting_date?->format('M j, Y') }}</span>
                                </div>

                                <div class="mt-1 flex items-center gap-1.5 text-gray-700">
                                    <span class="flex-shrink-0 text-gray-400">{!! $icon('clock.svg', 'w-3.5 h-3.5') !!}</span>
                                    <span>{{ $roadblock->meeting_time_range_label }}</span>
                                </div>

                                <div class="mt-1 flex items-center gap-1.5">
                                    @if ($platformIcon)
                                    <img src="{{ asset('images/icons/' . $platformIcon) }}" alt="{{ $roadblock->meeting_platform }}"
                                        class="h-3.5 w-3.5 flex-shrink-0">
                                    @endif

                                    <a href="{{ $roadblock->meeting_link }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-blue-600 hover:underline">
                                        Join meeting
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 7L7 17M17 7H9M17 7v8" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $roadblock->assignee?->display_name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button type="button" @click="viewOpen = true"
                                        class="rounded-lg border border-[#6D0D23] px-3 py-1.5 text-[#6D0D23] hover:bg-[#6D0D23]/5">View</button>

                                    @if ($roadblock->isLive())
                                    <a href="{{ $roadblock->meeting_link }}" target="_blank"
                                        class="rounded-lg bg-[#6D0D23] px-3 py-1.5 text-white hover:bg-[#58091b]">Join</a>
                                    @else
                                    <button type="button" @click="editOpen = true"
                                        class="rounded-lg border border-[#6D0D23] px-3 py-1.5 text-[#6D0D23] hover:bg-[#6D0D23]/5">Edit</button>
                                    @endif
                                </div>

                                {{-- ============ Mentorship view modal ============ --}}
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

                                                    <h3 class="truncate text-base font-bold">Mentorship</h3>
                                                </div>

                                                <button type="button"
                                                    @click="viewOpen = false"
                                                    class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white/70 bg-white/10 transition hover:bg-white/25 focus:outline-none"
                                                    aria-label="Close">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="3" stroke-linecap="round">
                                                        <path d="M6 6l12 12M18 6L6 18" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="flex flex-col gap-5 px-8 pb-6 pt-4">

                                                {{-- Schedule --}}
                                                <section>
                                                    <p class="{{ $section }}">Schedule</p>

                                                    <div class="{{ $card }} space-y-3">
                                                        <div class="grid gap-3 sm:grid-cols-3 lg:w-[80%]">
                                                            <div>
                                                                <label class="{{ $lbl }}">Platform</label>
                                                                <p class="{{ $pill }} truncate">{{ $roadblock->meeting_platform }}</p>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">Date</label>
                                                                <p class="{{ $pill }}">{{ $roadblock->meeting_date?->format('M j, Y') }}</p>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">Time Slot</label>
                                                                <p class="{{ $pill }} truncate">{{ $roadblock->meeting_time_range_label }}</p>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label class="{{ $lbl }}">Meeting Link / Location</label>
                                                            <p class="truncate rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600">
                                                                {{ $roadblock->meeting_link }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </section>

                                                @php
                                                $assignee = $roadblock->assignee;
                                                $isCoordinatorAssignee = $roadblock->coordinator_id !== null;
                                                $assigneeLabel = $isCoordinatorAssignee ? 'Coordinator' : 'Mentor';
                                                @endphp

                                                {{-- Assignee --}}
                                                <section>
                                                    <p class="{{ $section }}">Assigned {{ $assigneeLabel }}</p>

                                                    <div class="{{ $card }}">
                                                        <div class="grid gap-3 sm:grid-cols-2 sm:gap-x-6 lg:w-[80%]">
                                                            <div>
                                                                <label class="{{ $lbl }}">{{ $assigneeLabel }}</label>
                                                                <p class="{{ $pill }} truncate">{{ $assignee?->display_name ?? '—' }}</p>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">{{ $isCoordinatorAssignee ? 'Role' : 'Expertise' }}</label>
                                                                <p class="{{ $pill }} truncate">
                                                                    {{ ($isCoordinatorAssignee ? $assignee?->role_title : $assignee?->specialization) ?? '—' }}
                                                                </p>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">Email</label>
                                                                <p class="{{ $pill }} truncate">
                                                                    {{ ($isCoordinatorAssignee ? $assignee?->email : $assignee?->contact_email) ?? '—' }}
                                                                </p>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">Contact Number</label>
                                                                <p class="{{ $pill }} truncate">
                                                                    {{ ($isCoordinatorAssignee ? $assignee?->phone : $assignee?->contact_number) ?? '—' }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </section>

                                                {{-- Roadblock Details --}}
                                                <section>
                                                    <p class="{{ $section }}">Roadblock Details</p>

                                                    <div class="{{ $card }} space-y-3">
                                                        <div class="grid gap-3 sm:grid-cols-3 lg:w-[80%]">
                                                            <div>
                                                                <label class="{{ $lbl }}">Startup Name</label>
                                                                <p class="{{ $pill }} truncate">{{ $roadblock->startup->company_name }}</p>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">Category</label>
                                                                <p class="{{ $pill }} truncate">{{ $roadblock->startup->industry_sector }}</p>
                                                            </div>

                                                            <div>
                                                                <label class="{{ $lbl }}">Batch</label>
                                                                <p class="{{ $pill }} truncate">{{ $roadblock->startup->batch_label }}</p>
                                                            </div>

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
                                                            <label class="{{ $lbl }}">Team</label>

                                                            {{-- relation name is a guess: confirm teamMembers() on Startup --}}
                                                            <div class="grid grid-cols-2 gap-2.5 sm:w-1/2">
                                                                @forelse ($roadblock->startup->teamMembers as $member)
                                                                <p class="{{ $pill }} truncate text-center">{{ $member->full_name }}</p>
                                                                @empty
                                                                <p class="col-span-2 text-sm text-gray-400">No team members listed.</p>
                                                                @endforelse
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label class="{{ $lbl }}">Issue</label>
                                                            <p class="rounded bg-gray-100 px-3 py-3 text-sm leading-relaxed text-gray-800">
                                                                {{ $roadblock->description }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </section>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Edit modal --}}
                                <div
                                    x-show="editOpen"
                                    x-cloak
                                    @keydown.escape.window="editOpen = false"
                                    class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4 sm:p-6">

                                    <div class="flex min-h-full items-center justify-center">
                                        <div
                                            class="relative flex w-[880px] max-w-full flex-col overflow-hidden rounded-xl bg-white text-left shadow-2xl"
                                            @click.outside="editOpen = false">

                                            <x-roadblock-assign-modal
                                                mode="edit"
                                                :roadblock="$roadblock"
                                                :mentors="$mentors"
                                                :coordinators="$coordinators"
                                                :action="route('admin.roadblocks.assign', $roadblock)" />
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Nothing scheduled.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>