@props(['mode', 'action', 'coordinator' => null])

@php
$photoInputId = 'coordinator_photo_input_'.($coordinator?->coordinator_id ?? 'new');

// Same helper as the layout — components have isolated scope, so it can't be
// inherited from x-layouts.admin and has to be redeclared here.
$icon = function (string $name, string $class = 'w-4 h-4') {
$path = public_path('images/icons/' . $name);

if (! file_exists($path)) {
return '<span class="' . $class . ' inline-block"></span>';
}

$svg = file_get_contents($path);

$svg = preg_replace('/<svg([^>]*)>/', '<svg$1 class="' . $class . ' block">', $svg, 1);
        $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="currentColor"', $svg);
        $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="currentColor"', $svg);

        return $svg;
        };

        // ONE border definition, used on every field edge and every divider:
        // 1px solid gray-300. The icon slot and the input are two halves of a single
        // bordered box, so the inner control must carry border-0 — @tailwindcss/forms
        // gives inputs and selects their own 1px gray-500 border otherwise, which is
        // what makes the input look like a separate box nested inside the group.
        $edge = 'border-gray-300';
        $group = "flex h-10 items-stretch overflow-hidden rounded-md border $edge bg-white transition focus-within:border-[#9F1239] focus-within:ring-2 focus-within:ring-[#9F1239]/12";
        $slot_ = "flex w-10 flex-shrink-0 items-center justify-center border-r $edge bg-[#FAD4DE] text-[#9F1239]";
        $field = 'w-full border-0 bg-transparent px-3 text-sm text-gray-800 placeholder-gray-400 focus:border-0 focus:outline-none focus:ring-0';
        $plain = "h-10 w-full rounded-md border $edge px-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-[#9F1239] focus:outline-none focus:ring-2 focus:ring-[#9F1239]/12";
        $label = 'block text-[13px] font-semibold text-gray-800 mb-1.5';
        $chevron = 'pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500';
        $err = 'mt-1 text-xs text-red-600';

        // old()/$errors are global to the whole request, not scoped to this
        // particular modal instance. Without this guard, a failed
        // submission for ONE coordinator (or the Add modal) would leak its
        // typed values — and its error messages — into every OTHER
        // coordinator's Edit modal too, since they're all rendered from
        // this same component in the same page response and all share the
        // same field names. Only apply old()/@error() when this is
        // genuinely the record that failed (matched via the hidden
        // _coordinator_id field submitted with the form, empty string for
        // the Add modal).
        $isErroredRecord = $errors->any() && (string) old('_coordinator_id', '') === (string) ($coordinator?->coordinator_id ?? '');
        $oldFor = fn (string $field, $default = null) => $isErroredRecord ? old($field, $default) : $default;
        @endphp

        {{-- Header --}}
        <div class="flex flex-shrink-0 items-center justify-between bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-8 py-5 text-white">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex-shrink-0 text-white">
                    {!! $icon('upcoming-mentorship.svg', 'w-6 h-6') !!}
                </span>

                <h3 class="truncate text-base font-bold">
                    {{ $mode === 'edit' ? 'Edit Portfolio Coordinator' : 'Add Portfolio Coordinator' }}
                </h3>
            </div>

            <button
                type="button"
                @click="{{ $mode === 'edit' ? 'editOpen = false' : 'open = false' }}"
                class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                aria-label="Close">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data"
            class="flex flex-1 flex-col space-y-3 px-8 pb-6 pt-1">
            @csrf
            @if ($mode === 'edit')
            @method('PUT')
            @endif

            <input type="hidden" name="_coordinator_id" value="{{ $coordinator?->coordinator_id }}">

            {{-- Row 1: First Name / Last Name / Honorifics --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-x-6">
                <div>
                    <label class="{{ $label }}">First Name</label>

                    <div class="{{ $group }}">
                        <span class="{{ $slot_ }}">
                            {!! $icon('upcoming-mentorship.svg', 'w-4 h-4') !!}
                        </span>

                        <input type="text" name="first_name" value="{{ $oldFor('first_name', $coordinator?->first_name) }}"
                            placeholder="Coordinator First Name" class="{{ $field }}">
                    </div>

                    @if ($isErroredRecord) @error('first_name') <p class="{{ $err }}">{{ $message }}</p> @enderror @endif
                </div>

                <div>
                    <label class="{{ $label }}">Last Name</label>

                    <input type="text" name="last_name" value="{{ $oldFor('last_name', $coordinator?->last_name) }}"
                        placeholder="Coordinator Last Name" class="{{ $plain }}">

                    @if ($isErroredRecord) @error('last_name') <p class="{{ $err }}">{{ $message }}</p> @enderror @endif
                </div>

                <div>
                    <label class="{{ $label }}">Honorifics</label>

                    <div class="relative">
                        <select name="honorific" class="{{ $plain }} appearance-none bg-white pr-9">
                            {{-- disabled + hidden: a placeholder prompt, not a real
                                 selectable choice — it isn't a valid honorific and
                                 was previously left pickable, which just meant
                                 "honorific" failed validation with a confusing
                                 error if a user selected it and moved on. --}}
                            <option value="" disabled hidden @selected(! $oldFor('honorific', $coordinator?->honorific))>Coordinator Honorifics</option>
                            @foreach (['Sir', 'Ma\'am', 'Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.', 'Atty.', 'Engr.'] as $h)
                            <option value="{{ $h }}" @selected($oldFor('honorific', $coordinator?->honorific) === $h)>{{ $h }}</option>
                            @endforeach
                        </select>

                        <svg class="{{ $chevron }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    @if ($isErroredRecord) @error('honorific') <p class="{{ $err }}">{{ $message }}</p> @enderror @endif
                </div>
            </div>

            {{-- Email / Phone --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-x-6">
                <div>
                    <label class="{{ $label }}">Email</label>

                    <div class="{{ $group }}">
                        <span class="{{ $slot_ }}">
                            {!! $icon('mail.svg', 'w-4 h-4') !!}
                        </span>

                        <input type="email" name="email" value="{{ $oldFor('email', $coordinator?->email) }}"
                            placeholder="example@email.com" class="{{ $field }}">
                    </div>

                    @if ($isErroredRecord) @error('email') <p class="{{ $err }}">{{ $message }}</p> @enderror @endif
                </div>

                <div>
                    <label class="{{ $label }}">Phone Number</label>

                    <div class="{{ $group }}">
                        <span class="{{ $slot_ }}">
                            {!! $icon('phone.svg', 'w-4 h-4') !!}
                        </span>

                        <input type="text" name="phone" value="{{ $oldFor('phone', $coordinator?->phone) }}"
                            placeholder="09XX-XXX-XXXX" inputmode="numeric" maxlength="11"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                            class="{{ $field }}">
                    </div>

                    @if ($isErroredRecord) @error('phone') <p class="{{ $err }}">{{ $message }}</p> @enderror @endif
                </div>
            </div>

            {{-- Profile Photo: half width --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-x-6">
                <div x-data="{ photoPreview: '{{ $coordinator?->coordinator_photo_path ? Storage::url($coordinator->coordinator_photo_path) : '' }}' }">
                    <label class="{{ $label }}">Profile Photo</label>

                    <div class="flex items-stretch overflow-hidden rounded-md border {{ $edge }} bg-white"
                        @dragover.prevent
                        @drop.prevent="
                    const file = $event.dataTransfer.files[0];
                    if (file) {
                        $refs.photoInput.files = $event.dataTransfer.files;
                        photoPreview = URL.createObjectURL(file);
                    }
                ">

                        <span class="{{ $slot_ }}">
                            {!! $icon('camera.svg', 'w-4 h-4') !!}
                        </span>

                        {{-- No border-l: the slot's border-r already draws that divider --}}
                        <div class="min-w-0 flex-1 bg-[#FDF2F5]">

                            {{-- Preview --}}
                            <div x-show="photoPreview" x-cloak class="relative">
                                <img :src="photoPreview" class="h-20 w-full object-cover">

                                <button type="button"
                                    @click="photoPreview = ''; $refs.photoInput.value = ''"
                                    class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-xs text-white transition hover:bg-black/80">
                                    &times;
                                </button>

                                <label for="{{ $photoInputId }}"
                                    class="absolute bottom-2 right-2 cursor-pointer rounded bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-2.5 py-1 text-[11px] font-semibold text-white">
                                    Change Photo
                                </label>
                            </div>

                            {{-- Empty state --}}
                            <div x-show="!photoPreview" class="flex h-20 flex-col items-center justify-center px-4">
                                <svg class="h-5 w-5 text-[#9F1239]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                                </svg>

                                <p class="mt-0.5 text-[13px] text-gray-500">Drag-and-drop</p>

                                <label for="{{ $photoInputId }}"
                                    class="mt-1 inline-block cursor-pointer rounded bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-3 py-1 text-[11px] font-bold text-white">
                                    Browse Files
                                </label>
                            </div>

                            <input type="file" x-ref="photoInput" id="{{ $photoInputId }}" name="coordinator_photo" accept="image/*" class="hidden"
                                @change="
                            const file = $event.target.files[0];
                            if (file) { photoPreview = URL.createObjectURL(file); }
                        ">
                        </div>
                    </div>

                    @if ($isErroredRecord) @error('coordinator_photo') <p class="{{ $err }}">{{ $message }}</p> @enderror @endif
                </div>
            </div>

            {{-- Actions: full width, split evenly between the two buttons --}}
            <div class="flex flex-col gap-4 pt-4 sm:flex-row">
                @if ($mode === 'edit')
                <button type="button" @click="editOpen = false"
                    class="h-10 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50 sm:flex-1">
                    Cancel
                </button>

                <button type="submit"
                    class="h-10 w-full rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95 sm:flex-1">
                    Save Changes
                </button>
                @else
                <button type="reset"
                    class="h-10 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50 sm:flex-1">
                    Clear Form
                </button>

                <button type="submit"
                    class="h-10 w-full rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95 sm:flex-1">
                    Add Coordinator
                </button>
                @endif
            </div>
        </form>