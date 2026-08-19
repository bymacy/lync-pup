<x-layouts.admin title="Mentor Profile">

    @php
    // One class string so both triggers stay identical
    $addBtn = 'items-center gap-2 whitespace-nowrap rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-3 py-2.5 text-sm font-medium text-white transition hover:from-[#5A0A1D] hover:to-[#0D2F59] sm:px-4';
    @endphp

    {{-- Alpine root wraps both triggers so they share one `open` state and one modal --}}
    <div x-data="{ open: false }">

        {{-- Page header --}}
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Mentor Profile</h1>
                <p class="mt-1 text-sm text-gray-500 sm:text-base">Review startup roadblocks and assign experts.</p>
            </div>

            {{-- Desktop trigger: top right, beside the title --}}
            <button @click="open = true" class="{{ $addBtn }} hidden flex-shrink-0 sm:flex">
                <span class="text-lg leading-none">+</span> Add Mentor
            </button>
        </div>

        {{-- Manage Mentor row: on phones the trigger sits on this line --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="font-bold text-gray-900">Manage Mentor</h2>

            {{-- Phone trigger --}}
            <button @click="open = true" class="{{ $addBtn }} flex flex-shrink-0 sm:hidden">
                <span class="text-lg leading-none">+</span> Add Mentor
            </button>
        </div>

        {{-- 2 per row on phones, scaling up to 5 on wide screens --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 lg:gap-5 xl:grid-cols-5 xl:gap-6">

            @forelse ($mentors as $mentor)
            <div class="relative aspect-[3/4] overflow-hidden rounded-xl border"
                x-data="{ menuOpen: false, editOpen: false, deleteOpen: false }">

                <div class="absolute right-2 top-2 z-20 sm:right-3 sm:top-3">
                    <button
                        @click="menuOpen = !menuOpen"
                        @click.outside="menuOpen = false"
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-black/25 text-white backdrop-blur-sm transition duration-200 hover:bg-white hover:text-[#6D0D23] sm:h-9 sm:w-9">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="5" r="2" />
                            <circle cx="12" cy="12" r="2" />
                            <circle cx="12" cy="19" r="2" />
                        </svg>
                    </button>

                    <div
                        x-show="menuOpen"
                        x-transition.origin.top.right
                        x-cloak
                        class="absolute right-0 top-10 z-50 w-36 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl sm:w-40">

                        <button
                            @click="editOpen = true; menuOpen = false"
                            class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white sm:gap-3 sm:px-4 sm:py-3">

                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.862 3.487a2.1 2.1 0 113 2.97L8.5 17.82l-4 1 1-4L16.862 3.487z" />
                            </svg>

                            Edit
                        </button>

                        <div class="border-t border-gray-100"></div>

                        <button
                            type="button"
                            @click="deleteOpen = true; menuOpen = false"
                            class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm font-medium text-red-600 transition hover:bg-gradient-to-r hover:from-red-600 hover:to-red-800 hover:text-white sm:gap-3 sm:px-4 sm:py-3">

                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7L5 7M10 11v6M14 11v6M6 7l1 13a2 2 0 002 2h6a2 2 0 002-2l1-13M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                            </svg>

                            Delete
                        </button>
                    </div>

                    {{-- Delete confirmation --}}
                    <div
                        x-show="deleteOpen"
                        x-cloak
                        x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">

                        <div
                            @click.outside="deleteOpen = false"
                            class="w-full max-w-md rounded-2xl bg-white shadow-2xl">

                            <div class="px-6 py-7 text-center sm:px-8 sm:py-8">

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-red-600"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                                    </svg>
                                </div>

                                <h2 class="mt-5 text-lg font-semibold text-gray-900 sm:text-xl">
                                    Delete Mentor
                                </h2>

                                <p class="mt-3 text-sm leading-6 text-gray-600">
                                    You're about to permanently delete
                                    <span class="font-semibold text-gray-900">
                                        {{ $mentor->display_name }}
                                    </span>.
                                    This action cannot be undone.
                                </p>

                                <div class="mt-7 flex flex-col justify-center gap-3 sm:mt-8 sm:flex-row">
                                    <button
                                        type="button"
                                        @click="deleteOpen = false"
                                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                                        Cancel
                                    </button>

                                    <form method="POST" action="{{ route('admin.mentors.destroy', $mentor) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="w-full rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-2.5 text-sm font-medium text-white shadow-md transition hover:opacity-90 sm:w-auto">
                                            Delete
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- Photo fills the entire card, positioned absolutely as the base layer --}}
                <div class="absolute inset-0 bg-gray-200">
                    @if ($mentor->mentor_photo_path)
                    <img src="{{ Storage::url($mentor->mentor_photo_path) }}" class="h-full w-full object-cover">
                    @else
                    <div class="flex h-full w-full items-center justify-center text-xs text-gray-400 sm:text-sm">No Photo</div>
                    @endif
                </div>

                {{-- Text overlay, anchored bottom, gradient fading up into the photo --}}
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-10 text-white sm:p-4 sm:pt-16">
                    <p class="truncate text-sm font-bold sm:text-base">{{ $mentor->display_name }}</p>
                    <p class="mb-1.5 truncate text-[10px] text-white/70 sm:mb-2 sm:text-xs">{{ $mentor->specialization }} Mentor</p>

                    <div class="space-y-0.5 border-t border-white/20 pt-1.5 text-[10px] text-white/80 sm:space-y-1 sm:pt-2 sm:text-xs">
                        <p class="truncate">{{ $mentor->contact_number ?? '—' }}</p>
                        <p class="truncate">{{ $mentor->contact_email ?? '—' }}</p>
                        <p class="truncate">{{ $mentor->cases_count }} Cases</p>
                    </div>
                </div>

                {{-- Edit modal --}}
                <div
                    x-show="editOpen"
                    x-cloak
                    @keydown.escape.window="editOpen = false"
                    class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4 sm:p-6">

                    <div class="flex min-h-full items-center justify-center">
                        <div
                            class="relative flex w-[880px] max-w-full flex-col overflow-hidden rounded-xl bg-white text-left shadow-2xl"
                            @click.outside="editOpen = false">

                            <x-mentor-form-modal
                                mode="edit"
                                :mentor="$mentor"
                                :action="route('admin.mentors.update', $mentor)" />
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <p class="col-span-full text-gray-500">No mentors added yet.</p>
            @endforelse
        </div>

        {{-- Add modal: rendered once, driven by whichever trigger is visible --}}
        <div
            x-show="open"
            x-cloak
            @keydown.escape.window="open = false"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4 sm:p-6">

            <div class="flex min-h-full items-center justify-center">
                <div
                    class="relative flex w-[880px] max-w-full flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
                    @click.outside="open = false">

                    <x-mentor-form-modal
                        mode="add"
                        :action="route('admin.mentors.store')" />
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>