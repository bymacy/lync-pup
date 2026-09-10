@php
// Same avatar/category table styling as the rest of the Assessment Hub.
$avatar = function ($startup) {
$url = $startup->startup_photo_url ?? null;
$name = $startup->company_name ?? '?';
return $url
? '<img src="'.e($url).'" alt="" class="h-full w-full object-cover">'
: '<span class="text-[10px] font-bold text-gray-500">'.e(mb_strtoupper(mb_substr($name, 0, 1))).'</span>';
};

$allMeetingNames = $meetingsToday->concat($meetingsUpcoming)->concat($meetingsArchive)
    ->map(fn ($m) => mb_strlen($m->startup->company_name ?? ''))->max() ?: 12;
$meetingCell = 'width: calc(1.5rem + 0.5rem + '.min(max($allMeetingNames, 8), 26).'ch)';
@endphp

<div x-data="{ meetingTab: 'today', settingMeeting: false }">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="icon-mask h-7 w-7 text-rose-900" style="--icon: url('{{ asset('images/icons/cal.svg') }}')"></span>
            <span class="font-bold text-gray-900">Meetings</span>
        </div>

        <button type="button" @click="settingMeeting = true"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90 sm:text-sm">
            <img src="{{ asset('images/icons/cal.svg') }}" alt="" class="h-3.5 w-3.5 brightness-0 invert" aria-hidden="true">
            Set Meeting
        </button>
    </div>

    <div class="mb-5 inline-flex w-full gap-1 overflow-x-auto overflow-y-hidden rounded-lg bg-gray-100 p-1 sm:w-auto">
        <button type="button" @click="meetingTab = 'today'"
            class="flex-1 whitespace-nowrap rounded-md px-4 py-1.5 text-sm font-medium transition sm:flex-none"
            :class="meetingTab === 'today' ? 'bg-white text-rose-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
            Today
        </button>
        <button type="button" @click="meetingTab = 'upcoming'"
            class="flex-1 whitespace-nowrap rounded-md px-4 py-1.5 text-sm font-medium transition sm:flex-none"
            :class="meetingTab === 'upcoming' ? 'bg-white text-rose-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
            Upcoming
        </button>
        <button type="button" @click="meetingTab = 'archive'"
            class="flex-1 whitespace-nowrap rounded-md px-4 py-1.5 text-sm font-medium transition sm:flex-none"
            :class="meetingTab === 'archive' ? 'bg-white text-rose-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
            Archive
        </button>
    </div>

    @foreach ([
        'today' => ['rows' => $meetingsToday, 'actions' => ['start']],
        'upcoming' => ['rows' => $meetingsUpcoming, 'actions' => ['start', 'reschedule']],
        'archive' => ['rows' => $meetingsArchive, 'actions' => ['reschedule', 'delete']],
    ] as $tabKey => $tabData)
    <div x-show="meetingTab === '{{ $tabKey }}'" @if ($tabKey !== 'today') x-cloak @endif>
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] table-fixed text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-center text-white">
                            <th class="px-3 py-2.5 text-[11px] font-semibold tracking-wider whitespace-nowrap">Time</th>
                            <th class="px-3 py-2.5 text-left text-[11px] font-semibold tracking-wider whitespace-nowrap">Startup</th>
                            <th class="px-3 py-2.5 text-[11px] font-semibold tracking-wider whitespace-nowrap">Category</th>
                            <th class="px-3 py-2.5 text-[11px] font-semibold tracking-wider whitespace-nowrap">Document</th>
                            <th class="px-3 py-2.5 text-[11px] font-semibold tracking-wider whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tabData['rows'] as $meeting)
                        <tr x-data="{ rescheduling: false, confirmingDelete: false }" class="border-b border-gray-100 last:border-0">
                            <td class="px-3 py-2.5 text-center text-xs text-gray-600 whitespace-nowrap">{{ $meeting->time_range_label }}</td>
                            <td class="px-3 py-2.5 text-left">
                                <div class="inline-flex max-w-full items-center gap-2 text-left text-xs" style="{{ $meetingCell }}">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200">
                                        {!! $avatar($meeting->startup) !!}
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-xs font-medium text-gray-900" title="{{ $meeting->startup->company_name }}">{{ $meeting->startup->company_name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-center text-xs text-gray-600">{{ $meeting->startup->industry_sector ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="rounded-full border border-rose-300 px-2.5 py-1 text-[11px] font-semibold text-rose-800">{{ $meeting->stage_code }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    @if (in_array('start', $tabData['actions'], true))
                                    <a href="{{ route('admin.assessment-hub.index', ['main' => 'assessment', 'stage' => $meeting->stage, 'assessment_startup' => $meeting->startup_id]) }}"
                                        class="inline-flex h-7 items-center justify-center whitespace-nowrap rounded-md bg-[#6C0E24] px-3 text-[11px] font-semibold text-white transition hover:opacity-90">
                                        Start
                                    </a>
                                    @endif
                                    @if (in_array('reschedule', $tabData['actions'], true))
                                    <button type="button" @click="rescheduling = true"
                                        class="inline-flex h-7 items-center justify-center whitespace-nowrap rounded-md border border-[#6D0D23] px-3 text-[11px] font-semibold text-[#6D0D23] transition hover:bg-[#6D0D23]/5">
                                        Reschedule
                                    </button>
                                    @endif
                                    @if (in_array('delete', $tabData['actions'], true))
                                    <button type="button" @click="confirmingDelete = true"
                                        class="inline-flex h-7 items-center justify-center whitespace-nowrap rounded-md bg-red-700 px-3 text-[11px] font-semibold text-white transition hover:opacity-90">
                                        Delete
                                    </button>
                                    @endif
                                </div>

                                {{-- Reschedule modal --}}
                                <div x-show="rescheduling" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;"
                                    @click.self="rescheduling = false">
                                    <div class="w-full max-w-lg overflow-hidden rounded-xl bg-white text-left">
                                        <x-assessment-meeting-modal mode="edit" :meeting="$meeting"
                                            close="rescheduling = false"
                                            :action="route('admin.assessment-hub.meetings.update', $meeting)"
                                            :stages="$stages" />
                                    </div>
                                </div>

                                {{-- Delete confirm --}}
                                <div x-show="confirmingDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;"
                                    @click.self="confirmingDelete = false">
                                    <div class="w-full max-w-sm overflow-hidden rounded-xl bg-white text-center shadow-xl">
                                        <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-5 text-white">
                                            <p class="text-base font-bold">Delete Meeting</p>
                                        </div>
                                        <div class="px-6 pb-6 pt-5">
                                            <p class="text-sm text-gray-600">
                                                Remove this {{ $meeting->stage }} meeting with <strong>{{ $meeting->startup->company_name }}</strong>? This cannot be undone.
                                            </p>
                                            <form method="POST" action="{{ route('admin.assessment-hub.meetings.destroy', $meeting) }}" class="mt-5 flex gap-3">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" @click="confirmingDelete = false"
                                                    class="flex-1 rounded-lg border border-gray-300 bg-white py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                    class="flex-1 rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-2.5 text-sm font-semibold text-white transition hover:opacity-95">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">
                                @if ($tabKey === 'today') No meetings today.
                                @elseif ($tabKey === 'upcoming') No upcoming meetings.
                                @else No archived meetings.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Set Meeting modal --}}
    <div x-show="settingMeeting" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;"
        @click.self="settingMeeting = false">
        <div class="w-full max-w-lg overflow-hidden rounded-xl bg-white text-left">
            <x-assessment-meeting-modal mode="add"
                close="settingMeeting = false"
                :action="route('admin.assessment-hub.meetings.store')"
                :startups="$assessableStartups" :stages="$stages" />
        </div>
    </div>
</div>
