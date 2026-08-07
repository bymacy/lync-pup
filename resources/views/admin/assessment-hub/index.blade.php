<x-layouts.admin title="Assessment Hub">
    <div x-data="{ mainTab: 'information-sheet' }">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Assessment Hub</h1>
            <p class="text-gray-500 mt-1">Manage Startup Information Sheets from submission to approval and the Assessment Documents</p>
        </div>

        <div class="flex gap-8 border-b mb-6">
            <button type="button" @click="mainTab = 'information-sheet'"
                :class="mainTab === 'information-sheet' ? 'border-b-2 border-rose-900 text-rose-900 font-bold' : 'text-gray-400 font-medium'"
                class="pb-3 text-lg">
                Information Sheet
            </button>
            <button type="button" @click="mainTab = 'assessment'"
                :class="mainTab === 'assessment' ? 'border-b-2 border-rose-900 text-rose-900 font-bold' : 'text-gray-400 font-medium'"
                class="pb-3 text-lg">
                Assessment
            </button>
        </div>

        @php
            $initialTab = in_array(request('tab'), ['schedule', 'evaluation', 'approved']) ? request('tab') : 'schedule';
        @endphp
        <div x-show="mainTab === 'information-sheet'" x-data="{ subTab: @js($initialTab) }">
            <div class="flex gap-8 border-b mb-6 text-sm font-medium">
                <button type="button" @click="subTab = 'schedule'"
                    :class="subTab === 'schedule' ? 'border-b-2 border-rose-900 text-rose-900' : 'text-gray-500'"
                    class="pb-3">
                    Schedule
                </button>
                <button type="button" @click="subTab = 'evaluation'"
                    :class="subTab === 'evaluation' ? 'border-b-2 border-rose-900 text-rose-900' : 'text-gray-500'"
                    class="pb-3">
                    Evaluation
                </button>
                <button type="button" @click="subTab = 'approved'"
                    :class="subTab === 'approved' ? 'border-b-2 border-rose-900 text-rose-900' : 'text-gray-500'"
                    class="pb-3">
                    Approved
                </button>
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
            <div class="border border-dashed rounded-xl p-12 text-center text-gray-400">
                Assessment scoring (TRL / MRL / TMRL / SRL) is coming in a future update.
            </div>
        </div>
    </div>
</x-layouts.admin>
