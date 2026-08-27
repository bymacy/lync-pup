@props(['mode', 'action', 'cohort' => null])

<div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between">
    <h3 class="font-bold">{{ $mode === 'edit' ? 'Edit Cohort' : 'Create Cohort' }}</h3>
</div>

<form method="POST" action="{{ $action }}" class="p-6 space-y-4">
    @csrf
    @if ($mode === 'edit')
        @method('PATCH')
        {{-- Lets the cohorts index page know which row's Edit modal to
             reopen if validation fails and the page redirects back. --}}
        <input type="hidden" name="_cohort_id" value="{{ $cohort?->cohort_id }}">
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cohort Name</label>
        <input type="text" name="label" value="{{ old('label', $cohort?->label) }}"
               placeholder="e.g. Cohort 6 - AY 2026-2027" class="w-full border rounded-lg px-3 py-2 text-sm">
        @error('label') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ old('start_date', optional($cohort?->start_date)->format('Y-m-d')) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('start_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ old('end_date', optional($cohort?->end_date)->format('Y-m-d')) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('end_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('description', $cohort?->description) }}</textarea>
        @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-3 pt-2">
        @if ($mode === 'edit')
            <button type="button" @click="editOpen = false" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
            <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Save Changes</button>
        @else
            <button type="button" @click="open = false" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
            <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Create Cohort</button>
        @endif
    </div>
</form>
