<x-layouts.admin title="Mentor Profile">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Mentor Profile</h1>
            <p class="text-gray-500 mt-1">Review startup roadblocks and assign experts.</p>
        </div>

        <div x-data="{ open: false }">
            <button @click="open = true" class="flex items-center gap-2 bg-rose-900 hover:bg-rose-950 text-white text-sm font-medium rounded-lg px-4 py-2.5">
                <span class="text-lg leading-none">+</span> Add Mentor
            </button>

            <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
                <div class="bg-white rounded-xl w-full max-w-lg overflow-hidden" @click.outside="open = false">
                    <x-mentor-form-modal mode="add" :action="route('admin.mentors.store')" />
                </div>
            </div>
        </div>
    </div>

    <h2 class="font-bold text-gray-900 mb-4">Manage Mentor</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        @forelse ($mentors as $mentor)
            <div class="border rounded-xl overflow-hidden relative" x-data="{ menuOpen: false, editOpen: false }">
                <div class="absolute top-3 right-3 z-10">
                    <button @click="menuOpen = !menuOpen" @click.outside="menuOpen = false" class="text-white text-xl leading-none">&#8942;</button>
                    <div x-show="menuOpen" x-cloak class="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg overflow-hidden border" style="display: none;">
                        <button @click="editOpen = true; menuOpen = false" class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50">
                            Edit
                        </button>
                        <form method="POST" action="{{ route('admin.mentors.destroy', $mentor) }}" onsubmit="return confirm('Remove this mentor?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-700 bg-rose-900 hover:bg-rose-950 hover:text-white">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

                <div class="h-48 bg-gray-200">
                    @if ($mentor->mentor_photo_path)
                        <img src="{{ Storage::url($mentor->mentor_photo_path) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">No Photo</div>
                    @endif
                </div>

                <div class="bg-gray-900 text-white p-4">
                    <p class="font-bold">{{ $mentor->display_name }}</p>
                    <p class="text-xs text-white/70 mb-2">{{ $mentor->specialization }} Mentor</p>
                    <div class="border-t border-white/20 pt-2 space-y-1 text-xs text-white/80">
                        <p>{{ $mentor->contact_number ?? '—' }}</p>
                        <p>{{ $mentor->contact_email ?? '—' }}</p>
                        <p>{{ $mentor->cases_count }} Cases</p>
                    </div>
                </div>

                <div x-show="editOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
                    <div class="bg-white rounded-xl w-full max-w-lg overflow-hidden" @click.outside="editOpen = false">
                        <x-mentor-form-modal mode="edit" :mentor="$mentor" :action="route('admin.mentors.update', $mentor)" />
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500 col-span-full">No mentors added yet.</p>
        @endforelse
    </div>
</x-layouts.admin>