@props(['mode', 'action', 'roadblock', 'mentors', 'coordinators' => null])
@php $coordinators = $coordinators ?? collect(); @endphp
@php
    $formId = 'roadblock-assign-form-'.$roadblock->roadblock_id;
    $selectedAssignee = old('assignee', $roadblock->coordinator_id
        ? 'coordinator-'.$roadblock->coordinator_id
        : ($roadblock->mentor_id ? 'mentor-'.$roadblock->mentor_id : ''));
@endphp

<div class="relative bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center">
            <img src="{{ asset('images/icons/upcoming-mentorship.svg') }}" alt=""
                class="h-4 w-4 brightness-0 invert">
        </span>
        <h3 class="font-bold">
            @if ($mode === 'edit') Edit Assigned Mentor
            @elseif ($mode === 'reschedule') Reschedule
            @else Assign Mentor
            @endif
        </h3>
    </div>

    <button type="button"
        @click="{{ $mode === 'edit' ? 'editOpen = false' : ($mode === 'reschedule' ? 'rescheduleOpen = false' : 'assignOpen = false') }}"
        class="flex h-8 w-8 items-center justify-center rounded-full text-3xl text-white/70 transition hover:bg-white/10 hover:text-white"
        aria-label="Close">
        <span class="-mt-2">&times;</span>
    </button>
</div>

<div class="p-6">
    <form method="POST" action="{{ $action }}" id="{{ $formId }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="roadblock_id" value="{{ $roadblock->roadblock_id }}">

        <div class="grid grid-cols-2 gap-6">
            <div class="border rounded-xl p-4 relative" x-data="{ previewId: null }">
                <p class="font-medium mb-3">1. Assign Mentor or Coordinator</p>
                <select name="assignee" class="w-full border rounded-lg px-3 py-2 text-sm mb-4">
                    <option value="">Select Mentor</option>
                    @foreach ($mentors as $m)
                    <option value="mentor-{{ $m->mentor_id }}" @selected($selectedAssignee === 'mentor-'.$m->mentor_id)>
                        {{ $m->display_name }}
                    </option>
                    @endforeach
                    @if ($coordinators->isNotEmpty())
                    <option disabled>── Coordinators ──</option>
                    @foreach ($coordinators as $c)
                    <option value="coordinator-{{ $c->coordinator_id }}" @selected($selectedAssignee === 'coordinator-'.$c->coordinator_id)>
                        {{ $c->display_name }}
                    </option>
                    @endforeach
                    @endif
                </select>
                @error('mentor_id') <p class="text-xs text-red-600 mb-3">{{ $message }}</p> @enderror
                @error('coordinator_id') <p class="text-xs text-red-600 mb-3">{{ $message }}</p> @enderror

                <p class="text-sm font-medium text-gray-700 mb-2">Profile Preview</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($mentors as $m)
                    <button type="button" @click="previewId = 'mentor-{{ $m->mentor_id }}'"
                        class="border rounded-lg px-3 py-2 text-sm flex items-center gap-2 text-left transition hover:border-rose-900 hover:bg-gray-50">
                        <div class="w-6 h-6 rounded-full bg-gray-200 overflow-hidden shrink-0">
                            @if ($m->mentor_photo_path)
                            <img src="{{ Storage::url($m->mentor_photo_path) }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <span class="truncate">{{ $m->display_name }}</span>
                    </button>
                    @endforeach
                    @foreach ($coordinators as $c)
                    <button type="button" @click="previewId = 'coordinator-{{ $c->coordinator_id }}'"
                        class="border rounded-lg px-3 py-2 text-sm flex items-center gap-2 text-left transition hover:border-rose-900 hover:bg-gray-50">
                        <div class="w-6 h-6 rounded-full bg-gray-200 overflow-hidden shrink-0">
                            @if ($c->coordinator_photo_path)
                            <img src="{{ Storage::url($c->coordinator_photo_path) }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <span class="truncate">{{ $c->display_name }}</span>
                    </button>
                    @endforeach
                </div>

                {{-- Overlay: backdrop + card, scoped to this panel --}}
                <div x-show="previewId !== null" x-cloak
                    class="absolute inset-0 z-20 flex items-center justify-center rounded-xl bg-black/40 p-4"
                    @click.self="previewId = null" style="display: none;">

                    @foreach ($mentors as $m)
                    <div x-show="previewId === 'mentor-{{ $m->mentor_id }}'" x-cloak
                        class="relative w-full max-w-[15rem] aspect-[3/4] rounded-xl overflow-hidden shadow-2xl"
                        style="display: none;">

                        {{-- Photo fills the entire card, positioned absolutely as the base layer --}}
                        <div class="absolute inset-0 bg-gray-200">
                            @if ($m->mentor_photo_path)
                            <img src="{{ Storage::url($m->mentor_photo_path) }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">No Photo</div>
                            @endif
                        </div>

                        {{-- Text overlays ON TOP of the photo, anchored to the bottom --}}
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black via-black/70 to-transparent text-white p-4 pt-16">
                            <p class="font-bold">{{ $m->display_name }}</p>
                            <p class="text-xs text-white/70 mb-2">{{ $m->specialization }} Mentor</p>
                            <div class="border-t border-white/20 pt-2 space-y-1 text-xs text-white/80">
                                <p>{{ $m->contact_number ?? '—' }}</p>
                                <p>{{ $m->contact_email ?? '—' }}</p>
                                <p>{{ $m->cases_count }} Cases</p>
                            </div>
                        </div>

                        <button type="button" @click="previewId = null"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded-full bg-black/50 text-xl text-white/80 transition hover:bg-black/70 hover:text-white"
                            aria-label="Close preview">
                            <span class="-mt-1">&times;</span>
                        </button>
                    </div>
                    @endforeach

                    @foreach ($coordinators as $c)
                    <div x-show="previewId === 'coordinator-{{ $c->coordinator_id }}'" x-cloak
                        class="relative w-full max-w-[15rem] aspect-[3/4] rounded-xl overflow-hidden shadow-2xl"
                        style="display: none;">

                        <div class="absolute inset-0 bg-gray-200">
                            @if ($c->coordinator_photo_path)
                            <img src="{{ Storage::url($c->coordinator_photo_path) }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">No Photo</div>
                            @endif
                        </div>

                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black via-black/70 to-transparent text-white p-4 pt-16">
                            <p class="font-bold">{{ $c->display_name }}</p>
                            <p class="text-xs text-white/70 mb-2">{{ $c->role_title }} &bull; Coordinator</p>
                            <div class="border-t border-white/20 pt-2 space-y-1 text-xs text-white/80">
                                <p>{{ $c->phone ?? '—' }}</p>
                                <p>{{ $c->email ?? '—' }}</p>
                                <p>{{ $c->cases_count }} Cases</p>
                            </div>
                        </div>

                        <button type="button" @click="previewId = null"
                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded-full bg-black/50 text-xl text-white/80 transition hover:bg-black/70 hover:text-white"
                            aria-label="Close preview">
                            <span class="-mt-1">&times;</span>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="border rounded-xl p-4"
                x-data="{
                    meetingDate: @js(old('meeting_date', $roadblock->meeting_date?->format('Y-m-d'))),
                    todayStr: @js(now()->format('Y-m-d')),
                    nowTime: @js(now()->format('H:i')),
                }">
                <p class="font-medium mb-3">2. Set a Meeting</p>

                <label class="block text-xs text-gray-500 mb-1">Date</label>
                <input type="date" name="meeting_date" x-model="meetingDate" :min="todayStr"
                    value="{{ old('meeting_date', $roadblock->meeting_date?->format('Y-m-d')) }}"
                    class="w-full border rounded-lg px-3 py-2 text-sm mb-3">
                @error('meeting_date') <p class="text-xs text-red-600 mb-3">{{ $message }}</p> @enderror

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Start Time</label>
                        <input type="time" name="meeting_start_time" :min="meetingDate === todayStr ? nowTime : null"
                            value="{{ old('meeting_start_time', $roadblock->meeting_start_time) }}"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('meeting_start_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">End Time</label>
                        <input type="time" name="meeting_end_time" value="{{ old('meeting_end_time', $roadblock->meeting_end_time) }}"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('meeting_end_time') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="block text-xs text-gray-500 mb-1">Select Platform</label>
                <select name="meeting_platform" class="w-full border rounded-lg px-3 py-2 text-sm mb-3">
                    <option value="">Select Platform</option>
                    @foreach (['Google Meet', 'Zoom', 'Microsoft Teams', 'Other'] as $platform)
                    <option value="{{ $platform }}" @selected(old('meeting_platform', $roadblock->meeting_platform) === $platform)>{{ $platform }}</option>
                    @endforeach
                </select>
                @error('meeting_platform') <p class="text-xs text-red-600 mb-3">{{ $message }}</p> @enderror

                <label class="block text-xs text-gray-500 mb-1">Meeting Link / Location</label>
                <textarea name="meeting_link" rows="3" placeholder="Input Meeting Link / Address"
                    class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('meeting_link', $roadblock->meeting_link) }}</textarea>
                @error('meeting_link') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </form>

    <div class="flex gap-3 pt-6">
        @if ($mode === 'reschedule')
        <button type="button" @click="rescheduleOpen = false" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Cancel</button>
        @else
        <button type="reset" form="{{ $formId }}" class="flex-1 border rounded-lg py-2.5 text-sm font-medium">Clear Form</button>
        @endif

        @if ($mode === 'edit')
        <form method="POST" action="{{ route('admin.roadblocks.unassign', $roadblock) }}" class="flex-1">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full bg-gradient-to-r from-rose-900 to-rose-950 text-white rounded-lg py-2.5 text-sm font-medium">
                Delete Assignment
            </button>
        </form>
        @endif

        <button type="submit" form="{{ $formId }}" class="flex-1 bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-lg py-2.5 text-sm font-medium">
            @if ($mode === 'edit') Save Changes
            @elseif ($mode === 'reschedule') Save
            @else Assign Mentor
            @endif
        </button>
    </div>
</div>