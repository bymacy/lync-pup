<x-layouts.founder title="Startup Profile">

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

                <form
                    method="POST"
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
                            <textarea
                                name="business_description"
                                rows="4"
                                :readonly="!editing"
                                :class="editing ? 'bg-white' : 'bg-gray-50 text-gray-600 cursor-default'"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                @input="dirty = true">{{ old('business_description', $startup->informationSheet?->business_description) }}

                            </textarea>

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
                <div
                    class="flex justify-center mb-4"
                    x-data="{ photoPreview: '' }">
                    <input
                        x-ref="photoInput"
                        type="file"
                        name="startup_photo"
                        accept="image/*"
                        class="hidden"
                        @change="
        dirty = true;
        const file = $event.target.files[0];
        if (file) {
            photoPreview = URL.createObjectURL(file);
        }
    ">
                    @if ($startup->startup_photo_path)
                    <div class="relative inline-block">

                        <img
                            :src="photoPreview || '{{ Storage::url($startup->startup_photo_path) }}'"
                            @click="editing && $refs.photoInput.click()"
                            class="w-24 h-24 rounded-full object-cover cursor-pointer hover:brightness-90 transition">

                        <button
                            x-show="editing"
                            type="button"
                            @click="$refs.photoInput.click()"
                            class="absolute bottom-0 right-0 h-8 w-8 rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white shadow-lg flex items-center justify-center hover:scale-105 transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="w-4 h-4">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.862 3.487a2.25 2.25 0 113.182 3.182L8.25 18.463 4 19.5l1.037-4.25L16.862 3.487z" />
                            </svg>

                        </button>

                    </div>
                    @else
                    <div class="w-20 h-20 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-xl">
                        {{ substr($startup->company_name, 0, 1) }}
                    </div>
                    @endif
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