<x-layouts.founder title="Startup Profile">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Startup Profile</h1>
        <p class="text-gray-500 mt-1">Keep your Startup Profile fresh and accurate.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('startup.profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
                    <h2 class="font-bold text-gray-900 mb-4">Startup Information</h2>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Startup Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $startup->company_name) }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('company_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                            <input type="text" name="industry_sector" value="{{ old('industry_sector', $startup->industry_sector) }}"
                                   class="w-full border rounded-lg px-3 py-2 text-sm">
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
                        <textarea name="business_description" rows="4"
                                  class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('business_description', $startup->informationSheet?->business_description) }}</textarea>
                        @error('business_description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="font-bold text-gray-900 mb-4">Contact Information</h2>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Founder Name</label>
                        <input type="text" name="founder_name" value="{{ old('founder_name', auth()->user()->name) }}"
                               class="w-full border rounded-lg px-3 py-2 text-sm">
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
                            <input type="text" name="contact_phone" value="{{ old('contact_phone', $startup->contact_phone) }}"
                                   class="w-full border rounded-lg px-3 py-2 text-sm">
                            @error('contact_phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                            <input type="text" name="website" value="{{ old('website', $startup->website) }}"
                                   placeholder="https://" class="w-full border rounded-lg px-3 py-2 text-sm">
                            @error('website') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input type="text" name="location" value="{{ old('location', $startup->location) }}"
                                   class="w-full border rounded-lg px-3 py-2 text-sm">
                            @error('location') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-4" x-data="{ photoPreview: '' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Startup Logo (optional update)</label>
                        <input type="file" name="startup_photo" accept="image/*"
                               @change="const f = $event.target.files[0]; if (f) photoPreview = URL.createObjectURL(f)"
                               class="text-sm">
                        <img x-show="photoPreview" :src="photoPreview" x-cloak class="mt-2 w-20 h-20 rounded-lg object-cover border">
                        @error('startup_photo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="mt-6 bg-rose-900 text-white text-sm font-medium rounded-lg px-5 py-2.5">
                        Save Changes
                    </button>
                </div>
            </form>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-bold text-gray-900 mb-4">Team Members</h2>

            <div class="space-y-3 mb-4">
                @forelse ($startup->teamMembers as $member)
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('startup.team-members.update', $member) }}" class="flex items-center gap-2 flex-1">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="full_name" value="{{ $member->full_name }}"
                                class="flex-1 border rounded-lg px-3 py-2 text-sm">
                            <button type="submit" class="text-gray-500 hover:text-gray-800 text-sm px-2">Save</button>
                        </form>

                        <form method="POST" action="{{ route('startup.team-members.destroy', $member) }}"
                            onsubmit="return confirm('Remove this team member?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-2">&times;</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No team members added yet.</p>
                @endforelse
            </div>

                <form method="POST" action="{{ route('startup.team-members.store') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="full_name" placeholder="New team member name"
                           class="flex-1 border rounded-lg px-3 py-2 text-sm">
                    <button type="submit" class="text-rose-900 text-sm font-medium">+ Add team member</button>
                </form>
                @error('full_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 h-fit">
            <h2 class="font-bold text-gray-900 mb-4">Startup Overview</h2>
            <div class="flex justify-center mb-4">
                @if ($startup->startup_photo_path)
                    <img src="{{ Storage::url($startup->startup_photo_path) }}" class="w-20 h-20 rounded-full object-cover">
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
</x-layouts.founder>