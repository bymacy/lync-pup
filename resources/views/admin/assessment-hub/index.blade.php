<x-layouts.admin title="Assessment Hub">
    @php
        $initialMainTab = in_array(request('main'), ['information-sheet', 'assessment']) ? request('main') : 'information-sheet';
    @endphp
    <div x-data="{ mainTab: @js($initialMainTab), addDayOpen: false }"
        x-init="$watch('mainTab', value => setQueryParam('main', value))">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Assessment Hub</h1>
                <p class="text-gray-500 mt-1">Manage Startup Information Sheets from submission to approval and the Assessment Documents</p>
            </div>

            <button type="button" x-show="mainTab === 'information-sheet'" x-cloak @click="addDayOpen = true"
                class="inline-flex shrink-0 items-center gap-2 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold rounded-lg px-4 py-2.5 transition hover:opacity-90">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Evaluation Day</span>
            </button>

            {{-- "Export Document" is visual-only for now — no export
                 endpoint exists yet in the app. Wire this up once a real
                 document-export feature is scoped. --}}
            <button type="button" x-show="mainTab === 'assessment'" x-cloak
                class="inline-flex shrink-0 items-center gap-2 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold rounded-lg px-4 py-2.5 transition hover:opacity-90">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3m0 12l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                </svg>
                <span>Export Document</span>
            </button>

            <div x-show="addDayOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none;">
                <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white">
                    <x-evaluation-schedule-modal mode="add"
                        close="addDayOpen = false"
                        :action="route('admin.assessment-hub.evaluations.store')"
                        :time-slots="$timeSlots" :booked-slots="$bookedSlots"
                        :startups="$pendingStartups" />
                </div>
            </div>
        </div>

        <div class="flex gap-8 border-b mb-6">
            <button type="button" @click="mainTab = 'information-sheet'"
                :class="mainTab === 'information-sheet' ? 'border-rose-900 text-rose-900 font-bold' : 'border-transparent text-gray-400 font-medium'"
                class="pb-3 -mb-px border-b-2 text-lg">
                Information Sheet
            </button>

            <button type="button" @click="mainTab = 'assessment'"
                :class="mainTab === 'assessment' ? 'border-rose-900 text-rose-900 font-bold' : 'border-transparent text-gray-400 font-medium'"
                class="pb-3 -mb-px border-b-2 text-lg">
                Assessment
            </button>
        </div>

        @php
        $initialTab = in_array(request('tab'), ['schedule', 'evaluation', 'approved']) ? request('tab') : 'schedule';
        $subTabs = ['schedule' => 'Schedule', 'evaluation' => 'Evaluation', 'approved' => 'Approved'];
        @endphp
        <div x-show="mainTab === 'information-sheet'" x-cloak x-data="{ subTab: @js($initialTab) }"
            x-init="$watch('subTab', value => setQueryParam('tab', value))">

            {{-- replaces the old border-b sub-tab row --}}
            <div class="mb-6 inline-flex rounded-lg bg-gray-100 p-1">
                @foreach ($subTabs as $key => $label)
                <button type="button" @click="subTab = '{{ $key }}'"
                    class="rounded-md px-4 py-1.5 text-sm font-medium transition"
                    :class="subTab === '{{ $key }}'
                    ? 'bg-white text-rose-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700'">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            <div x-show="subTab === 'schedule'">
                @include('admin.assessment-hub._schedule')
            </div>
            <div x-show="subTab === 'evaluation'" x-cloak>
                @include('admin.assessment-hub._evaluation')
            </div>
            <div x-show="subTab === 'approved'" x-cloak>
                @include('admin.assessment-hub._approved')
            </div>
        </div>

        <div x-show="mainTab === 'assessment'" x-cloak>
            @include('admin.assessment-hub._assessment')
        </div>
    </div>
</x-layouts.admin>