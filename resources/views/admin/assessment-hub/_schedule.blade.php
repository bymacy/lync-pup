@php
$gradient = 'bg-gradient-to-r from-[#6D0D23] to-[#11386A]';

// works whether the controller passed a paginator or a plain collection
$isPaginated = $pendingStartups instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;

// avatar renderer — falls back to an initial if there's no logo
$avatar = function ($startup) {
$path = $startup->logo_path ?? $startup->company_logo ?? null;
$name = $startup->company_name ?? '?';
return $path
? '<img src="'.e(\Illuminate\Support\Facades\Storage::url($path)).'" alt="" class="h-full w-full object-cover">'
: '<span class="text-[10px] font-bold text-gray-500">'.e(mb_strtoupper(mb_substr($name, 0, 1))).'</span>';
};
@endphp

<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

    {{-- ==================== LEFT: EVALUATION DAY ==================== --}}
    <div class="xl:col-span-7">
        <div class="flex items-center gap-2 mb-3">
            <img src="{{ asset('images/icons/calendar.svg') }}" alt="" class="h-5 w-5" aria-hidden="true">
            <h2 class="font-bold text-gray-900">Awaiting Schedule</h2>
        </div>

        <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-sm">
                    <thead>
                        <tr class="{{ $gradient }} text-white text-center">
                            <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider">Startup</th>
                            <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider">Registration Date</th>
                            <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider">Cohort</th>
                            <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingStartups as $startup)
                        @php
                        $hasSchedule = (bool) ($startup->latestEvaluationSchedule ?? false);
                        @endphp
                        <tr x-data="{ scheduleOpen: false }" class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200">
                                        {!! $avatar($startup) !!}
                                    </span>
                                    <span class="font-medium text-gray-900">{{ $startup->company_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                {{ optional($startup->informationSheet?->submission_date ?? $startup->created_at)->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $startup->cohort_number ?? '—' }}</td>
    
                            <td class="px-4 py-3">
                                <button type="button" @click="scheduleOpen = true"
                                    @class([ 'inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg text-xs font-semibold whitespace-nowrap transition' ,
                                    $gradient.' text-white hover:opacity-90'=> ! $hasSchedule,
                                    'bg-gray-200 text-gray-400 hover:bg-gray-300' => $hasSchedule,
                                    ])>
                                    <img src="{{ asset('images/icons/calendar.svg') }}" alt=""
                                        @class(['h-4 w-4', 'brightness-0 invert'=> ! $hasSchedule, 'opacity-50' => $hasSchedule])
                                    aria-hidden="true">
                                    <span>Set Evaluation</span>
                                </button>

                                <div x-show="scheduleOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;">
                                    <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white text-center" @click.outside="scheduleOpen = false">
                                        @if ($hasSchedule)
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
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400">No startups waiting on evaluation.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- pagination footer --}}
            @if ($isPaginated && $pendingStartups->total() > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3">
                <div class="flex items-center gap-1.5">
                    <a href="{{ $pendingStartups->previousPageUrl() ?? '#' }}"
                        @class([ 'flex h-8 w-8 items-center justify-center rounded-md border text-sm transition' , 'border-gray-200 text-gray-400 pointer-events-none opacity-50'=> $pendingStartups->onFirstPage(),
                        'border-gray-300 text-gray-600 hover:bg-gray-50' => ! $pendingStartups->onFirstPage(),
                        ])>&lsaquo;</a>

                    @foreach (range(1, $pendingStartups->lastPage()) as $page)
                    <a href="{{ $pendingStartups->url($page) }}"
                        @class([ 'flex h-8 w-8 items-center justify-center rounded-md border text-sm font-medium transition' , 'border-transparent bg-[#6D0D23] text-white'=> $page === $pendingStartups->currentPage(),
                        'border-gray-300 text-gray-600 hover:bg-gray-50' => $page !== $pendingStartups->currentPage(),
                        ])>{{ $page }}</a>
                    @endforeach

                    <a href="{{ $pendingStartups->nextPageUrl() ?? '#' }}"
                        @class([ 'flex h-8 w-8 items-center justify-center rounded-md border text-sm transition' , 'border-gray-200 text-gray-400 pointer-events-none opacity-50'=> ! $pendingStartups->hasMorePages(),
                        'border-gray-300 text-gray-600 hover:bg-gray-50' => $pendingStartups->hasMorePages(),
                        ])>&rsaquo;</a>
                </div>

                <form method="GET" class="flex items-center gap-2">
                    @foreach (request()->except(['per_page', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <label for="per_page" class="text-xs text-gray-500">Items per page</label>
                    <select id="per_page" name="per_page" onchange="this.form.submit()"
                        class="rounded-md border border-gray-300 py-1 pl-2 pr-6 text-xs text-gray-700">
                        @foreach ([4, 8, 12, 20] as $n)
                        <option value="{{ $n }}" @selected(request('per_page', $pendingStartups->perPage()) == $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @endif
        </div>
    </div>

    {{-- ==================== RIGHT: SCHEDULED TODAY ==================== --}}
    <div class="xl:col-span-5 xl:sticky xl:top-6">
        {{-- spacer keeps this card's top edge level with the left table --}}
        <div class="hidden xl:block h-8"></div>

        <div class="rounded-2xl border border-gray-200 bg-white p-2.5">
            <h2 class="py-3 text-center text-xl font-bold text-[#6D0D23]">Scheduled Today</h2>

            <div class="overflow-hidden rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[380px] text-sm">
                        <thead>
                            <tr class="{{ $gradient }} text-white text-left">
                                <th class="px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider">Time</th>
                                <th class="px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider">Startup</th>
                                <th class="px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($scheduledToday as $item)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-gray-600">
                                    {{ $item->time_range_label ?? '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200">
                                            {!! $item->startup ? $avatar($item->startup) : '' !!}
                                        </span>
                                        <span class="whitespace-nowrap text-xs font-medium text-gray-900">
                                            {{ $item->startup->company_name ?? '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <a href="{{ $item->startup ? route('admin.information-sheet.show', $item->startup) : '#' }}"
                                        class="inline-flex h-8 items-center whitespace-nowrap rounded-lg bg-[#6D0D23] px-3 text-[11px] font-semibold text-white transition hover:bg-[#58091b]">
                                        Start Evaluation
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-3 py-10 text-center text-gray-400">Nothing scheduled today.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>