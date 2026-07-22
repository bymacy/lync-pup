<x-layouts.admin title="Coordinator Profile">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Coordinator Profile</h1>
            <p class="text-gray-500 mt-1">Review and manage weekly updates submitted by startup founders.</p>
        </div>

        <div x-data="{ open: false }">
            <button
                @click="open = true"
                class="flex items-center gap-2 bg-gradient-to-r from-[#6D0D23] to-[#11386A]
           hover:opacity-90 transition-all duration-200
           text-white text-sm font-medium rounded-lg px-4 py-2.5 shadow-md">
                <span class="text-lg leading-none">+</span>
                Add Coordinator
            </button>

            <div x-show="open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
                <div
                    class="relative bg-white rounded-xl w-full max-w-lg overflow-hidden"
                    @click.outside="open = false">
                    <!-- Close Button -->
                    <button
                        @click="open = false"
                        type="button"
                        class="absolute top-4 right-4 z-20
           flex h-8 w-8 items-center justify-center
           text-3xl text-gray-400 hover:text-gray-700
           transition-colors duration-200"
                        aria-label="Close">
                        <span class="-mt-2.5">&times;</span>
                    </button>

                    <x-coordinator-form-modal
                        mode="add"
                        :action="route('admin.coordinators.store')" />
                </div>
            </div>
        </div>
    </div>

    <h2 class="font-bold text-gray-900 mb-4">Portfolio Coordinator</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        @forelse ($coordinators as $coordinator)
        <div class="border rounded-xl overflow-hidden relative aspect-[3/4]" x-data="{ menuOpen: false, editOpen: false, deleteOpen: false }">
            <div class="absolute top-3 right-3 z-20">
                <button
                    @click="menuOpen = !menuOpen"
                    @click.outside="menuOpen = false"
                    class="flex items-center justify-center w-9 h-9 rounded-full text-gray-600 hover:bg-gray-100 hover:text-[#6D0D23] transition duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="5" r="2" />
                        <circle cx="12" cy="12" r="2" />
                        <circle cx="12" cy="19" r="2" />
                    </svg>
                </button>
                <div
                    x-show="menuOpen"
                    x-transition.origin.top.right
                    x-cloak
                    class="absolute right-0 top-10 z-50 w-40 overflow-hidden rounded-xl bg-white border border-gray-200 shadow-xl">
                    <button
                        @click="editOpen = true; menuOpen = false"
                        class="flex w-full items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-[#6D0D23]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.862 3.487a2.1 2.1 0 113 2.97L8.5 17.82l-4 1 1-4L16.862 3.487z" />
                        </svg>
                        Edit
                    </button>


                    <div class="border-t border-gray-100"></div>

                    <button
                        type="button"
                        @click="deleteOpen = true; menuOpen = false"
                        class="flex w-full items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7L5 7M10 11v6M14 11v6M6 7l1 13a2 2 0 002 2h6a2 2 0 002-2l1-13M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                        </svg>
                        Delete
                    </button>

                </div>

                <div
                    x-show="deleteOpen"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                    style="display: none;">
                    <div
                        @click.outside="deleteOpen = false"
                        class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                        <!-- Body -->
                        <div class="px-8 py-8 text-center">

                            <!-- Warning Icon -->
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-7 w-7 text-red-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                                </svg>
                            </div>

                            <!-- Title -->
                            <h2 class="mt-5 text-xl font-semibold text-gray-900">
                                Delete Coordinator
                            </h2>

                            <!-- Description -->
                            <p class="mt-3 text-sm leading-6 text-gray-600">
                                You're about to permanently delete
                                <span class="font-semibold text-gray-900">
                                    {{ $coordinator->display_name }}
                                </span>.
                                This action cannot be undone.
                            </p>

                            <!-- Buttons -->
                            <div class="mt-8 flex justify-center gap-3">
                                <button
                                    type="button"
                                    @click="deleteOpen = false"
                                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                                    Cancel
                                </button>

                                <form method="POST" action="{{ route('admin.coordinators.destroy', $coordinator) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 transition">
                                        Delete
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute inset-0 bg-gray-200">
                @if ($coordinator->coordinator_photo_path)
                <img src="{{ Storage::url($coordinator->coordinator_photo_path) }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">No Photo</div>
                @endif
            </div>

            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black via-black/70 to-transparent text-white p-4 pt-16">
                <p class="font-bold">{{ $coordinator->display_name }}</p>
                <p class="text-xs text-white/70 mb-2">{{ $coordinator->role_title }}</p>
                <div class="border-t border-white/20 pt-2 space-y-1 text-xs text-white/80">
                    <p>{{ $coordinator->phone ?? '—' }}</p>
                    <p>{{ $coordinator->email ?? '—' }}</p>
                    <p>{{ $coordinator->assigned_startups_count }} Startup</p>
                </div>
            </div>

            <div x-show="editOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
                <div class="bg-white rounded-xl w-full max-w-lg overflow-hidden" @click.outside="editOpen = false">
                    <x-coordinator-form-modal mode="edit" :coordinator="$coordinator" :action="route('admin.coordinators.update', $coordinator)" />
                </div>
            </div>
        </div>
        @empty
        <p class="text-gray-500 col-span-full">No coordinators added yet.</p>
        @endforelse
    </div>
</x-layouts.admin>