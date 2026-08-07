<div x-data="{ stage: 'today' }">
    <div class="mb-6 max-w-xs">
        <label class="text-sm font-medium mr-2 block mb-1">Stage:</label>
        <select x-model="stage" class="w-full border rounded-lg pl-3 pr-8 py-2 text-sm min-w-[160px]">
            <option value="today">Today</option>
            <option value="upcoming">Upcoming</option>
            <option value="missed">Missed</option>
        </select>
    </div>

    {{-- TODAY --}}
    <div x-show="stage === 'today'">
        <p class="flex items-center gap-2 font-medium mb-3">
            <span aria-hidden="true">&#128197;</span> {{ now()->format('F d, Y') }}
        </p>
        <table class="w-full text-sm border rounded-xl overflow-hidden">
            <thead>
                <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Startup</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($todayEvaluations as $item)
                    <tr class="border-b">
                        <td class="px-4 py-3">{{ $item->time_range_label }}</td>
                        <td class="px-4 py-3">{{ $item->startup->company_name }}</td>
                        <td class="px-4 py-3">{{ $item->startup->industry_sector }}</td>
                        <td class="px-4 py-3">{{ $item->status }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.information-sheet.show', $item->startup) }}"
                                class="inline-block bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-xs font-medium rounded-lg px-3 py-2">
                                Start Evaluation
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Nothing scheduled today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- UPCOMING --}}
    <div x-show="stage === 'upcoming'" x-cloak x-data="{ month: 'all' }">
        <div class="mb-4 max-w-xs">
            <select x-model="month" class="w-full border rounded-lg pl-3 pr-8 py-2 text-sm min-w-[160px]">
                <option value="all">All Months</option>
                @foreach ($upcomingEvaluations->pluck('evaluation_date')->map(fn ($d) => $d->format('Y-m'))->unique()->sort() as $m)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('F, Y') }}</option>
                @endforeach
            </select>
        </div>

        <table class="w-full text-sm border rounded-xl overflow-hidden">
            <thead>
                <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                    <th class="px-4 py-3">Date & Time</th>
                    <th class="px-4 py-3">Startup</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($upcomingEvaluations as $item)
                    <tr x-data="{ viewOpen: false, editOpen: false, deleteConfirmOpen: false }"
                        x-show="month === 'all' || month === '{{ $item->evaluation_date->format('Y-m') }}'"
                        class="border-b">
                        <td class="px-4 py-3">
                            {{ $item->evaluation_date->format('M d, Y') }}<br>
                            <span class="text-gray-400 text-xs">{{ $item->time_range_label }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $item->startup->company_name }}</td>
                        <td class="px-4 py-3">{{ $item->startup->industry_sector }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <button type="button" @click="viewOpen = true"
                                    class="border border-rose-900 text-rose-900 text-xs font-medium rounded-lg px-3 py-2">
                                    View
                                </button>
                                <button type="button" @click="editOpen = true"
                                    class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-xs font-medium rounded-lg px-3 py-2">
                                    Edit
                                </button>
                            </div>

                            <div x-show="viewOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                                <div class="bg-white rounded-xl w-full max-w-3xl overflow-hidden" @click.outside="viewOpen = false">
                                    <x-evaluation-schedule-modal mode="view" :schedule="$item"
                                        close="viewOpen = false" :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                </div>
                            </div>

                            <div x-show="editOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                                <div class="bg-white rounded-xl w-full max-w-3xl overflow-hidden" @click.outside="editOpen = false">
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
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No upcoming evaluations scheduled.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MISSED --}}
    <div x-show="stage === 'missed'" x-cloak>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-lg p-4 mb-4">
            These startups missed their scheduled evaluation or need to be rescheduled.
        </div>
        <table class="w-full text-sm border rounded-xl overflow-hidden">
            <thead>
                <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                    <th class="px-4 py-3">Startup</th>
                    <th class="px-4 py-3">Date & Time</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($missedEvaluations as $item)
                    <tr x-data="{ rescheduleOpen: false }" class="border-b">
                        <td class="px-4 py-3">{{ $item->startup->company_name }}</td>
                        <td class="px-4 py-3">{{ $item->evaluation_date->format('M d, Y') }} &middot; {{ $item->time_range_label }}</td>
                        <td class="px-4 py-3">{{ $item->startup->industry_sector }}</td>
                        <td class="px-4 py-3">
                            <button type="button" @click="rescheduleOpen = true"
                                class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-xs font-medium rounded-lg px-3 py-2">
                                Reschedule
                            </button>

                            <div x-show="rescheduleOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                                <div class="bg-white rounded-xl w-full max-w-3xl overflow-hidden" @click.outside="rescheduleOpen = false">
                                    <x-evaluation-schedule-modal mode="reschedule" :schedule="$item"
                                        close="rescheduleOpen = false"
                                        :action="route('admin.assessment-hub.evaluations.update', $item)"
                                        :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No missed evaluations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
