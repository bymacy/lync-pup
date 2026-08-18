@php
$gradient = 'bg-gradient-to-r from-[#6D0D23] to-[#11386A]';
$thead = 'sticky top-0 z-10 '.$gradient;
$th = 'px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-white';
$thC = $th;
$shell = 'w-full border border-gray-200 rounded-xl overflow-hidden bg-white';
$scroll = 'max-h-[60vh] overflow-y-auto overflow-x-auto';
$table = 'w-full text-sm';
$btn = 'inline-flex h-8 items-center justify-center whitespace-nowrap rounded-lg text-[11px] font-semibold transition';
@endphp

@php
$initialStage = in_array(request('stage'), ['unscheduled', 'today', 'upcoming', 'missed']) ? request('stage') : 'today';
@endphp
<div x-data="{ stage: @js($initialStage) }" x-init="$watch('stage', value => setQueryParam('stage', value))">
    @php $stages = ['unscheduled' => 'Unscheduled', 'today' => 'Today', 'upcoming' => 'Upcoming', 'missed' => 'Missed']; @endphp

    <div class="mb-6">
        <label class="text-sm font-medium block mb-1">Stage:</label>

        <div class="relative inline-block w-full max-w-xs" x-data="{ open: false }"
            @click.outside="open = false" @keydown.escape="open = false">

            <button type="button" @click="open = !open"
                class="flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-2 text-sm text-gray-700 transition hover:border-gray-400">
                <span x-text="{{ Js::from($stages) }}[stage]"></span>
                <svg class="h-4 w-4 shrink-0 text-gray-400 transition" :class="open && 'rotate-180'"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-cloak x-transition.origin.top
                class="absolute left-0 top-full z-30 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
                style="display:none;">
                @foreach ($stages as $value => $label)
                <button type="button"
                    x-show="stage !== '{{ $value }}'"
                    @click="stage = '{{ $value }}'; open = false"
                    class="w-full px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ==================== UNSCHEDULED ==================== --}}
    <div x-show="stage === 'unscheduled'" x-cloak>

        <div class="{{ $shell }}">
            <div class="{{ $scroll }}">
                <table class="{{ $table }}">
                    <thead class="{{ $thead }}">
                        <tr>
                            <th class="{{ $th }}">Startup</th>
                            <th class="{{ $th }}">Registration Date</th>
                            <th class="{{ $th }}">Cohort</th>
                            <th class="{{ $th }}">Category</th>
                            <th class="{{ $th }}">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingStartups as $startup)
                        <tr x-data="{ scheduleOpen: false }" class="border-b border-gray-100 last:border-0 hover:bg-gray-50/70">
                            <td class="px-4 py-3 text-center font-medium text-gray-900">{{ $startup->company_name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-gray-600">
                                {{ optional($startup->informationSheet?->submission_date ?? $startup->created_at)->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $startup->cohort_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $startup->industry_sector }}</td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" @click="scheduleOpen = true"
                                    class="{{ $btn }} w-[130px] gap-2 {{ $gradient }} text-white hover:opacity-90">
                                    <img src="{{ asset('images/icons/calendar.svg') }}" alt=""
                                        class="h-4 w-4 brightness-0 invert" aria-hidden="true">
                                    <span>Set Evaluation</span>
                                </button>

                                <div x-show="scheduleOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;">
                                    <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white" @click.outside="scheduleOpen = false">
                                        <x-evaluation-schedule-modal mode="add"
                                            :startup="$startup"
                                            close="scheduleOpen = false"
                                            :action="route('admin.assessment-hub.evaluations.store')"
                                            :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400">Naka-schedule na lahat ng startup.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================== TODAY ==================== --}}
    <div x-show="stage === 'today'">
        <p class="flex items-center gap-2 font-medium mb-3">
            <img src="{{ asset('images/icons/calendar.svg') }}" alt="" class="h-5 w-5" aria-hidden="true">
            {{ now()->format('F d, Y') }}
        </p>

        <div class="{{ $shell }}">
            <div class="{{ $scroll }}">
                <table class="{{ $table }}">
                    <thead class="{{ $thead }}">
                        <tr>
                            <th class="{{ $th }}">Time</th>
                            <th class="{{ $th }}">Startup</th>
                            <th class="{{ $th }}">Category</th>
                            <th class="{{ $th }}">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($todayEvaluations as $item)
                        <tr x-data="{ rescheduleOpen: false }" class="border-b border-gray-100 last:border-0 hover:bg-gray-50/70">
                            <td class="px-4 py-3 whitespace-nowrap text-center text-gray-600">{{ $item->time_range_label }}</td>
                            <td class="px-4 py-3 text-center font-medium text-gray-900">{{ $item->startup->company_name }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $item->startup->industry_sector }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.information-sheet.show', ['startup' => $item->startup, 'from' => 'assessment-hub', 'tab' => 'evaluation', 'stage' => 'today']) }}"
                                        class="{{ $btn }} w-[130px] text-white hover:opacity-90 bg-rose-900 hover:bg-rose-800">
                                        Start Evaluation
                                    </a>
                                    <button type="button" @click="rescheduleOpen = true"
                                        class="{{ $btn }} w-[110px] border border-[#6D0D23] text-[#6D0D23] hover:bg-[#6D0D23]/5">
                                        Reschedule
                                    </button>
                                </div>

                                <div x-show="rescheduleOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;">
                                    <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white" @click.outside="rescheduleOpen = false">
                                        <x-evaluation-schedule-modal mode="reschedule" :schedule="$item"
                                            close="rescheduleOpen = false"
                                            :action="route('admin.assessment-hub.evaluations.update', $item)"
                                            :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400">Nothing scheduled today.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================== UPCOMING ==================== --}}
    <div x-show="stage === 'upcoming'" x-cloak x-data="{ month: 'all' }">
        @php
        $months = $upcomingEvaluations->pluck('evaluation_date')
        ->map(fn ($d) => $d->format('Y-m'))->unique()->sort()
        ->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::createFromFormat('Y-m', $m)->format('F, Y')])
        ->prepend('All Months', 'all')->all();
        @endphp

        <div class="relative mb-4 inline-block w-full max-w-xs" x-data="{ open: false }"
            @click.outside="open = false" @keydown.escape="open = false">

            <button type="button" @click="open = !open"
                class="flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white py-2 pl-3 pr-2 text-sm text-gray-700 transition hover:border-gray-400">
                <span x-text="{{ Js::from($months) }}[month]"></span>
                <svg class="h-4 w-4 shrink-0 text-gray-400 transition" :class="open && 'rotate-180'"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-cloak x-transition.origin.top
                class="absolute left-0 top-full z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                style="display:none;">
                @foreach ($months as $value => $label)
                <button type="button"
                    x-show="month !== '{{ $value }}'"
                    @click="month = '{{ $value }}'; open = false"
                    class="w-full px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>

        <div class="{{ $shell }}">
            <div class="{{ $scroll }}">
                <table class="{{ $table }}">
                    <thead class="{{ $thead }}">
                        <tr>
                            <th class="{{ $th }}">Date &amp; Time</th>
                            <th class="{{ $th }}">Startup</th>
                            <th class="{{ $th }}">Category</th>
                            <th class="{{ $th }}">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($upcomingEvaluations as $item)
                        <tr x-data="{ viewOpen: false, editOpen: false, deleteConfirmOpen: false }"
                            x-show="month === 'all' || month === '{{ $item->evaluation_date->format('Y-m') }}'"
                            class="border-b border-gray-100 last:border-0 hover:bg-gray-50/70">

                            {{-- 1. Date & Time --}}
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="text-gray-900">{{ $item->evaluation_date->format('M d, Y') }}</span><br>
                                <span class="text-xs text-gray-400">{{ $item->time_range_label }}</span>
                            </td>

                            {{-- 2. Startup --}}
                            <td class="px-4 py-3 text-center font-medium text-gray-900">{{ $item->startup->company_name }}</td>

                            {{-- 3. Category --}}
                            <td class="px-4 py-3 text-center text-gray-600">{{ $item->startup->industry_sector }}</td>

                            {{-- 4. Action --}}
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button type="button" @click="viewOpen = true"
                                        class="{{ $btn }} w-[80px] border border-[#6D0D23] text-[#6D0D23] hover:bg-[#6D0D23]/5">
                                        View
                                    </button>
                                    <button type="button" @click="editOpen = true"
                                        class="{{ $btn }} w-[80px] bg-rose-900 text-white hover:bg-rose-800">
                                        Edit
                                    </button>
                                </div>

                                <div x-show="viewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;">
                                    <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white" @click.outside="viewOpen = false">
                                        <x-evaluation-schedule-modal mode="view" :schedule="$item"
                                            close="viewOpen = false" :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                    </div>
                                </div>

                                <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;">
                                    <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white" @click.outside="editOpen = false">
                                        <x-evaluation-schedule-modal mode="edit" :schedule="$item"
                                            close="editOpen = false"
                                            delete-trigger="editOpen = false; deleteConfirmOpen = true"
                                            :action="route('admin.assessment-hub.evaluations.update', $item)"
                                            :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                    </div>
                                </div>

                                <x-confirm-action-modal
                                    show="deleteConfirmOpen" close="deleteConfirmOpen = false"
                                    title="Delete Evaluation Day"
                                    message="Are you sure you want to delete this schedule? This action is permanent and cannot be undone."
                                    :action="route('admin.assessment-hub.evaluations.destroy', $item)"
                                    method="DELETE" confirm-label="Delete" icon="trash" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">No upcoming evaluations scheduled.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================== MISSED ==================== --}}
    <div x-show="stage === 'missed'" x-cloak>
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            These startups missed their scheduled evaluation or need to be rescheduled.
        </div>

        <div class="{{ $shell }}">
            <div class="{{ $scroll }}">
                <table class="{{ $table }}  ">
                    <thead class="{{ $thead }}">
                        <tr>
                            <th class="{{ $th }}">Startup</th>
                            <th class="{{ $th }}">Date &amp; Time</th>
                            <th class="{{ $th }}">Category</th>
                            <th class="{{ $thC }}">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($missedEvaluations as $item)
                        <tr x-data="{ rescheduleOpen: false }" class="border-b border-gray-100 last:border-0 hover:bg-gray-50/70">
                            <td class="px-4 py-3 text-center font-medium text-gray-900">{{ $item->startup->company_name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-gray-600">
                                <span class="text-gray-900">{{ $item->evaluation_date->format('M d, Y') }}</span><br>
                                <span class="text-xs text-gray-400">{{ $item->time_range_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $item->startup->industry_sector }}</td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" @click="rescheduleOpen = true"
                                    class="{{ $btn }} w-[110px] bg-rose-900 text-white hover:bg-rose-800">
                                    Reschedule
                                </button>
    

                                <div x-show="rescheduleOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;">
                                    <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white" @click.outside="rescheduleOpen = false">
                                        <x-evaluation-schedule-modal mode="reschedule" :schedule="$item"
                                            close="rescheduleOpen = false"
                                            :action="route('admin.assessment-hub.evaluations.update', $item)"
                                            :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">No missed evaluations.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>