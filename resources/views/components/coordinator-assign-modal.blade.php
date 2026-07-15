@props(['startup'])

<div x-data="{ open: false, step: 1, selected: null, coordinators: [] }"
     x-init="coordinators = @js($coordinators = \App\Models\Coordinator::all(['coordinator_id', 'name', 'role_title', 'email', 'phone']))">

    <button type="button" @click="open = true; step = 1"
            class="mt-3 bg-rose-900 text-white text-sm font-medium rounded-lg px-4 py-2 hover:bg-rose-950">
        Assign Coordinator
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-xl w-full max-w-md overflow-hidden" @click.outside="open = false">
            <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white p-5 flex items-center justify-between">
                <h3 class="font-bold">Assign Portfolio Coordinator</h3>
                <button type="button" @click="open = false" class="text-white/70 hover:text-white">&times;</button>
            </div>

            <div class="p-5" x-show="step === 1">
                <p class="text-sm font-medium text-gray-700 mb-3">Select Portfolio Coordinator</p>
                <div class="border rounded-lg divide-y max-h-64 overflow-y-auto">
                    <template x-for="c in coordinators" :key="c.coordinator_id">
                        <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50">
                            <input type="radio" :value="c.coordinator_id" x-model="selected" class="text-rose-800">
                            <div>
                                <p class="text-sm font-medium" x-text="c.name"></p>
                                <p class="text-xs text-gray-500" x-text="c.role_title"></p>
                            </div>
                        </label>
                    </template>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="button" @click="open = false" class="flex-1 border rounded-lg py-2 text-sm font-medium">Cancel</button>
                    <button type="button" :disabled="!selected" @click="step = 2"
                            class="flex-1 bg-rose-900 text-white rounded-lg py-2 text-sm font-medium disabled:opacity-40">
                        Confirm
                    </button>
                </div>
            </div>

            <div class="p-5" x-show="step === 2">
                <p class="text-sm font-medium text-gray-700 mb-2">Select Portfolio Coordinator</p>
                <div class="bg-gray-100 rounded-lg px-4 py-3 mb-4">
                    <p class="text-sm font-semibold">{{ $startup->company_name }}</p>
                    <p class="text-xs text-gray-500">Cohort {{ $startup->cohort_number }}</p>
                </div>

                <p class="text-sm font-medium text-gray-700 mb-2">New Portfolio Coordinator</p>
                <template x-for="c in coordinators" :key="c.coordinator_id">
                    <div class="bg-gray-100 rounded-lg px-4 py-3 mb-4" x-show="String(c.coordinator_id) === String(selected)">
                        <p class="text-sm font-semibold" x-text="c.name"></p>
                        <p class="text-xs text-gray-500" x-text="c.email"></p>
                        <p class="text-xs text-gray-500" x-text="c.phone"></p>
                    </div>
                </template>

                <form method="POST" action="{{ route('admin.startups.coordinator.store', $startup) }}">
                    @csrf
                    <input type="hidden" name="coordinator_id" :value="selected">
                    <div class="flex gap-3">
                        <button type="button" @click="step = 1" class="flex-1 border rounded-lg py-2 text-sm font-medium">Back</button>
                        <button type="submit" class="flex-1 bg-rose-900 text-white rounded-lg py-2 text-sm font-medium">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>