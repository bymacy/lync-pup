<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
            <span aria-hidden="true">&#128197;</span> Evaluation Day
        </h2>

        <table class="w-full text-sm border rounded-xl overflow-hidden">
            <thead>
                <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                    <th class="px-4 py-3">Startup</th>
                    <th class="px-4 py-3">Date Started</th>
                    <th class="px-4 py-3">Cohort</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingStartups as $startup)
                    <tr x-data="{ scheduleOpen: false }" class="border-b">
                        <td class="px-4 py-3">{{ $startup->company_name }}</td>
                        <td class="px-4 py-3">{{ ($startup->informationSheet?->submission_date ?? $startup->created_at)->format('M d, Y') }}</td>
                        <td class="px-4 py-3">{{ $startup->cohort_number ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-3 py-1 rounded-full text-xs border
                                {{ match ($startup->evaluation_status) {
                                    'Completed' => 'border-green-500 text-green-700',
                                    'In Progress' => 'border-blue-500 text-blue-700',
                                    default => 'border-gray-300 text-gray-500',
                                } }}">
                                {{ $startup->evaluation_status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" @click="scheduleOpen = true"
                                class="flex items-center gap-2 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-xs font-medium rounded-lg px-3 py-2">
                                <span aria-hidden="true">&#128197;</span> Set Evaluation
                            </button>

                            <div x-show="scheduleOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                                <div class="bg-white rounded-xl w-full max-w-3xl overflow-hidden" @click.outside="scheduleOpen = false">
                                    @if ($startup->evaluation_status === 'In Progress' && $startup->latestEvaluationSchedule)
                                        <x-evaluation-schedule-modal mode="edit"
                                            :schedule="$startup->latestEvaluationSchedule"
                                            close="scheduleOpen = false"
                                            :action="route('admin.assessment-hub.evaluations.update', $startup->latestEvaluationSchedule)"
                                            :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                    @else
                                        <x-evaluation-schedule-modal mode="add"
                                            :startup="$startup"
                                            close="scheduleOpen = false"
                                            :action="route('admin.assessment-hub.evaluations.store')"
                                            :time-slots="$timeSlots" :booked-slots="$bookedSlots" />
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No startups waiting on evaluation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border rounded-xl overflow-hidden h-fit">
        <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-center py-4 font-bold text-lg">
            Scheduled Today
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                    <th class="px-3 py-2">Time</th>
                    <th class="px-3 py-2">Startup</th>
                    <th class="px-3 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($scheduledToday as $item)
                    <tr class="border-b">
                        <td class="px-3 py-3">{{ $item->time_range_label }}</td>
                        <td class="px-3 py-3">{{ $item->startup->company_name }}</td>
                        <td class="px-3 py-3">
                            <a href="{{ route('admin.information-sheet.show', $item->startup) }}"
                                class="inline-block bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-xs font-medium rounded-lg px-3 py-2">
                                Start Evaluation
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-3 py-6 text-center text-gray-400">Nothing scheduled today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
