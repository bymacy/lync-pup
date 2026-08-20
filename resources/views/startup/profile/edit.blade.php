<x-layouts.founder title="Startup Profile">

    {{-- Cropper.js: circular crop for the startup avatar --}}
    <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js" defer></script>

    <style>
        /* Round the crop box so it previews as the circular avatar it becomes */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }
    </style>

    <div
        x-data="{
        editing: false,
        dirty: false,

        showLeaveModal: false,
        nextUrl: null,

        newMembers: [''],
        deletedMembers: []
    }"
        x-init="
        $watch('dirty', value => {
            $store.navigation.hasUnsavedChanges = value;
        });

        window.addEventListener('beforeunload', (e) => {
            if ($store.navigation.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
">

        <div class="flex items-center justify-between mb-6">

            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Startup Profile
                </h1>

                <p class="text-gray-500 mt-1">
                    Keep your Startup Profile fresh and accurate.
                </p>
            </div>

            <button
                type="button"
                @click="editing = !editing"
                class="flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition"
                :class="editing
        ? 'border border-gray-300 text-gray-700 hover:bg-gray-100'
        : 'bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white hover:opacity-90'">

                <span x-text="editing ? 'Cancel' : 'Edit Profile'"></span>
            </button>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- id lets the photo input in the sidebar (outside this form) submit with it --}}
                <form
                    method="POST"
                    id="startup-profile-form"
                    action="{{ route('startup.profile.update') }}"
                    enctype="multipart/form-data"
                    @submit="
        dirty = false;
        $store.navigation.hasUnsavedChanges = false;
    ">
                    @csrf
                    @method('PATCH')

                    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                        <h2 class="font-bold text-gray-900 mb-4">Startup Information</h2>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Startup Name</label>
                            <input

                                type="text"
                                name="company_name"
                                value="{{ old('company_name', $startup->company_name) }}"
                                :readonly="!editing"
                                :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                @input="dirty = true">

                            @error('company_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                                <input
                                    type="text"
                                    name="industry_sector"
                                    value="{{ old('industry_sector', $startup->industry_sector) }}"
                                    :readonly="!editing"
                                    :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                    class="w-full border rounded-lg px-3 py-2 text-sm"
                                    @input="dirty = true">

                                @error('industry_sector') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Portfolio Coordinator</label>
                                <div class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500">
                                    {{ $startup->activeCoordinatorAssignment?->coordinator?->name ?? 'Not yet assigned' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                                <div class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500">
                                    {{ $startup->batch_label }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Description</label>
                            {{-- No whitespace inside <textarea>: it becomes part of the value --}}
                            <textarea
                                name="business_description"
                                rows="4"
                                :readonly="!editing"
                                :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                @input="dirty = true">{{ old('business_description', $startup->informationSheet?->business_description) }}</textarea>

                            @error('business_description')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h2 class="font-bold text-gray-900 mb-4">Contact Information</h2>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Founder Name</label>
                            <input
                                type="text"
                                name="founder_name"
                                value="{{ old('founder_name', auth()->user()->name) }}"
                                :readonly="!editing"
                                :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                @input="dirty = true">
                            @error('founder_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500">
                                    {{ auth()->user()->email }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input
                                    type="text"
                                    name="contact_phone"
                                    value="{{ old('contact_phone', $startup->contact_phone) }}"
                                    :readonly="!editing"
                                    :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                    class="w-full border rounded-lg px-3 py-2 text-sm"
                                    @input="dirty = true">
                                @error('contact_phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <input
                                    type="text"
                                    name="website"
                                    value="{{ old('website', $startup->website) }}"
                                    placeholder="https://"
                                    :readonly="!editing"
                                    :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                    class="w-full border rounded-lg px-3 py-2 text-sm"
                                    @input="dirty = true">
                                @error('website') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <input
                                    type="text"
                                    name="location"
                                    value="{{ old('location', $startup->location) }}"
                                    :readonly="!editing"
                                    :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                    class="w-full border rounded-lg px-3 py-2 text-sm"
                                    @input="dirty = true">
                                @error('location') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
                        <h2 class="font-bold text-gray-900 mb-4">Team Members</h2>

                        <div class="space-y-3 mb-4">
                            @foreach($startup->teamMembers as $member)

                            <div
                                x-show="!deletedMembers.includes({{ $member->member_id }})"
                                class="grid grid-cols-[1fr_32px] gap-2 items-center">

                                <input
                                    type="text"
                                    name="team_members[{{ $member->member_id }}]"
                                    value="{{ old("team_members.$member->member_id", $member->full_name) }}"
                                    :readonly="!editing"
                                    :class="editing
                                        ? 'bg-white'
                                        : 'bg-gray-50 text-gray-600 cursor-default'"
                                    class="flex-1 border rounded-lg px-3 py-2 text-sm"
                                    @input="dirty = true">

                                <button
                                    x-show="editing"
                                    type="button"
                                    @click="
                                        deletedMembers.push({{ $member->member_id }});
                                        dirty = true;
                                    "
                                    class="text-red-600 hover:text-red-800 text-lg px-2">
                                    ×
                                </button>

                                <input
                                    type="hidden"
                                    x-bind:disabled="!deletedMembers.includes({{ $member->member_id }})"
                                    value="{{ $member->member_id }}"
                                    name="deleted_team_members[]">

                            </div>

                            @endforeach
                        </div>

                        <div x-show="editing" class="space-y-3 mt-4">

                            <template x-for="(member, index) in newMembers" :key="index">
                                <div class="grid grid-cols-[1fr_32px] gap-2 items-center">

                                    <input
                                        type="text"
                                        :name="'new_team_members[' + index + ']'"
                                        x-model="newMembers[index]"
                                        placeholder="New team member name"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        @input="dirty = true">

                                    <button
                                        type="button"
                                        @click="
                                            newMembers.splice(index,1);
                                            dirty = true;
                                        "
                                        x-show="newMembers.length > 1"
                                        class="text-red-600 hover:text-red-800 text-lg">
                                        ×
                                    </button>
                                </div>
                            </template>

                            <button
                                type="button"
                                @click="newMembers.push('');
                                dirty = true"
                                class="text-rose-900 text-sm font-medium">
                                + Add another member
                            </button>

                        </div>
                        @error('full_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div x-show="editing" x-transition class="mt-6 flex justify-end">

                        <button
                            type="submit"
                            class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-medium rounded-lg px-5 py-2.5">
                            Save Changes
                        </button>

                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6 h-fit">
                <h2 class="font-bold text-gray-900 mb-4">Startup Overview</h2>

                <div class="flex justify-center mb-4"
                    x-data="{
                        photoPreview: '',
                        cropOpen: false,
                        cropSrc: '',
                        cropper: null,

                        pickFile(event) {
                            const file = event.target.files[0];
                            if (! file) return;

                            this.cropSrc = URL.createObjectURL(file);
                            this.cropOpen = true;
                            this.$nextTick(() => this.startCropper());
                        },

                        startCropper() {
                            this.cropper?.destroy();
                            this.cropper = new Cropper(this.$refs.cropImage, {
                                aspectRatio: 1,
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.9,
                                background: false,
                                responsive: true,
                            });
                        },

                        applyCrop() {
                            this.cropper.getCroppedCanvas({
                                width: 512,
                                height: 512,
                                imageSmoothingQuality: 'high',
                            }).toBlob((blob) => {
                                const file = new File([blob], 'startup-photo.jpg', { type: 'image/jpeg' });

                                // Write the cropped file back into the real input so it
                                // submits with the form — no extra backend handling needed.
                                const dt = new DataTransfer();
                                dt.items.add(file);
                                this.$refs.photoInput.files = dt.files;

                                this.photoPreview = URL.createObjectURL(blob);
                                this.dirty = true;
                                this.closeCrop();
                            }, 'image/jpeg', 0.9);
                        },

                        closeCrop() {
                            this.cropper?.destroy();
                            this.cropper = null;
                            this.cropOpen = false;
                            URL.revokeObjectURL(this.cropSrc);
                            this.cropSrc = '';
                        },

                        cancelCrop() {
                            // Clear the input so a cancelled crop doesn't leave a
                            // stale file queued for submission
                            this.$refs.photoInput.value = '';
                            this.closeCrop();
                        },
                    }">

                    {{-- form="" ties this to the form above, which lives outside this div --}}
                    <input
                        x-ref="photoInput"
                        form="startup-profile-form"
                        type="file"
                        name="startup_photo"
                        accept="image/*"
                        class="hidden"
                        @change="pickFile($event)">

                    <div class="relative inline-block">
                        @if ($startup->startup_photo_path)
                        <img
                            :src="photoPreview || '{{ Storage::url($startup->startup_photo_path) }}'"
                            @click="editing && $refs.photoInput.click()"
                            :class="editing && 'cursor-pointer hover:brightness-90'"
                            class="h-24 w-24 rounded-full object-cover transition">
                        @else
                        {{-- No photo yet: initials circle, same click target, swaps to
                             the cropped preview once one is applied --}}
                        <template x-if="photoPreview">
                            <img
                                :src="photoPreview"
                                @click="editing && $refs.photoInput.click()"
                                :class="editing && 'cursor-pointer hover:brightness-90'"
                                class="h-24 w-24 rounded-full object-cover transition">
                        </template>

                        <div
                            x-show="!photoPreview"
                            @click="editing && $refs.photoInput.click()"
                            :class="editing && 'cursor-pointer hover:brightness-90'"
                            class="flex h-24 w-24 items-center justify-center rounded-full bg-purple-100 text-xl font-bold text-purple-600 transition">
                            {{ substr($startup->company_name, 0, 1) }}
                        </div>
                        @endif

                        {{-- Pencil, on both the photo and the initials branch --}}
                        <button
                            x-show="editing"
                            x-cloak
                            type="button"
                            @click="$refs.photoInput.click()"
                            aria-label="Change startup photo"
                            class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white shadow-lg transition hover:scale-105">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-4 w-4">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.463 4 19.5l1.037-4.25L16.862 3.487z" />
                            </svg>

                        </button>
                    </div>

                    {{-- Crop modal --}}
                    <template x-teleport="body">
                        <div x-show="cropOpen" x-cloak x-transition.opacity
                            @keydown.escape.window="cancelCrop()"
                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
                            style="display:none;">

                            <div @click.outside="cancelCrop()"
                                class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-xl bg-white shadow-2xl">

                                <div class="flex flex-shrink-0 items-center justify-between bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-4 text-white sm:px-8 sm:py-5">
                                    <h3 class="truncate text-sm font-bold sm:text-base">Crop Photo</h3>

                                    <button type="button" @click="cancelCrop()"
                                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-white text-white transition hover:border-transparent hover:bg-white hover:text-[#6D0D23] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                                        aria-label="Close">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="overflow-y-auto p-4 sm:p-5">
                                    <div class="mx-auto max-h-[55vh] bg-gray-100">
                                        <img x-ref="cropImage" :src="cropSrc" alt="" class="block max-w-full">
                                    </div>

                                    <p class="mt-3 text-center text-xs text-gray-500">
                                        Drag to reposition, scroll or pinch to zoom.
                                    </p>

                                    <div class="mt-4 grid grid-cols-2 gap-3">
                                        <button type="button" @click="cancelCrop()"
                                            class="h-10 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50">
                                            Cancel
                                        </button>

                                        <button type="button" @click="applyCrop()"
                                            class="h-10 w-full rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <p class="text-center font-bold text-gray-900">{{ $startup->company_name }}</p>
                <p class="text-center text-xs text-gray-500 mb-3">{{ $startup->industry_sector }} · {{ $startup->batch_label }}</p>
                <p class="text-xs text-gray-500 line-clamp-3 mb-3">{{ $startup->informationSheet?->business_description }}</p>
                <div class="text-xs text-gray-500 flex items-center justify-between border-t pt-3">
                    <span>{{ $startup->location }}</span>
                    @if ($startup->latestReadinessAssessment)
                    <span>RLS {{ number_format($startup->latestReadinessAssessment->overall_score, 1) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.founder>