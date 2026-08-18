@props(['mode', 'action', 'cohort' => null])

<div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between">
    <h3 class="font-bold">{{ $mode === 'edit' ? 'Edit Cohort' : 'Add Cohort' }}</h3>
</div>

<form method="POST" action="{{ $action }}" class="p-6 space-y-4">
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cohort Number</label>
        <input type="number" min="1" name="number" value="{{ old('number', $cohort?->number) }}"
               placeholder="e.g. 6" class="w-full border rounded-lg px-3 py-2 text-sm">
        @error('number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Label <span class="text-gray-400 font-normal">(Optional)</span></label>
        <input type="text" name="label" value="{{ old('label', $cohort?->label) }}"
               placeholder="e.g. Cohort 6 - AY 2026-2027" class="w-full border rounded-lg px-3 py-2 text-sm">
        @error('label') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm">
            @foreach (['Active', 'Inactive'] as $status)
                <option value="{{ $status }}" @selected(old('status', $cohort?->status ?? 'Active') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        @error('status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-3 pt-2">
        @if ($mode === 'edit')
            <button type="button" @click="editOpen = false" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
            <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Save Changes</button>
        @else
            <button type="button" @click="open = false" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
            <button type="submit" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg py-2.5 text-sm font-medium">Add Cohort</button>
        @endif
    </div>
</form>
