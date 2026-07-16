@props(['mode', 'action', 'mentor' => null])

<div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between">
    <h3 class="font-bold">{{ $mode === 'edit' ? 'Edit Mentor' : 'Add Mentor' }}</h3>
</div>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="p-6 space-y-4">
    @csrf
    @if ($mode === 'edit')
        @method('PUT')
    @endif

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name', $mentor?->first_name) }}"
                   placeholder="Mentor First Name" class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('first_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <input type="text" name="last_name" value="{{ old('last_name', $mentor?->last_name) }}"
                   placeholder="Mentor Last Name" class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('last_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Honorifics</label>
            <select name="honorific" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Mentor Honorifics</option>
                @foreach (['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.', 'Atty.', 'Engr.'] as $h)
                    <option value="{{ $h }}" @selected(old('honorific', $mentor?->honorific) === $h)>{{ $h }}</option>
                @endforeach
            </select>
            @error('honorific') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Expertise</label>
        <select name="specialization" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option value="">Select Expertise</option>
            @foreach (['Engineering', 'Business', 'Marketing', 'Legal', 'Finance', 'Technology'] as $exp)
                <option value="{{ $exp }}" @selected(old('specialization', $mentor?->specialization) === $exp)>{{ $exp }}</option>
            @endforeach
        </select>
        @error('specialization') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $mentor?->contact_email) }}"
                   placeholder="Email Address" class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('contact_email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number', $mentor?->contact_number) }}"
                   placeholder="Phone Number" class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('contact_number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
        <div class="border-2 border-dashed rounded-lg p-6 text-center bg-rose-50">
            <p class="text-sm text-gray-500 mb-2">Drag-and-drop</p>
            <label class="inline-block bg-rose-900 text-white text-sm rounded-lg px-4 py-2 cursor-pointer">
                Browse Files
                <input type="file" name="mentor_photo" accept="image/*" class="hidden">
            </label>
        </div>
        @error('mentor_photo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-3 pt-2">
        @if ($mode === 'edit')
            <button type="button" @click="editOpen = false" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
            <button type="submit" class="flex-1 bg-rose-900 text-white rounded-lg py-2.5 text-sm font-medium">Save Changes</button>
        @else
            <button type="reset" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Clear Form</button>
            <button type="submit" class="flex-1 bg-rose-900 text-white rounded-lg py-2.5 text-sm font-medium">Add Mentor</button>
        @endif
    </div>
</form>