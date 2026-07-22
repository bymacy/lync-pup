<x-layouts.founder title="Information Sheet">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Information Sheet</h1>
        <p class="text-gray-500 mt-1">View your details below.</p>
    </div>

    @php $sheet = $startup->informationSheet; @endphp

    <div class="bg-white rounded-xl border border-gray-200 max-w-5xl" x-data="{ editing: false }">
        <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4">
            <h2 class="font-bold">{{ $startup->company_name }}</h2>
        </div>

        <div class="p-6">
            <p class="text-center text-xs text-rose-800 font-medium mb-2">PUP-TBIDO FORM No.001</p>
            <h1 class="text-center font-bold text-lg mb-6">STARTUP INFORMATION SHEET</h1>

            <form method="POST" action="{{ route('startup.information-sheet.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- I. FOUNDER'S INFORMATION --}}
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">I. FOUNDER'S INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-6">
                    @php
                        $field = function ($name, $label, $type = 'text') use ($sheet) {
                            $value = old($name, $sheet?->{$name});
                            return "<div><label class='block text-xs text-gray-500 mb-1'>{$label}</label>
                                <input type=\"{$type}\" name=\"{$name}\" value=\"".e($value)."\"
                                    :disabled=\"!editing\"
                                    class='w-full border rounded px-3 py-2 text-sm disabled:bg-gray-50 disabled:text-gray-500'></div>";
                        };
                    @endphp

                    {!! $field('surname', 'Surname') !!}
                    {!! $field('first_name', 'First Name') !!}
                    {!! $field('middle_name', 'Middle Name') !!}
                    {!! $field('name_extension', 'Name Extension') !!}
                    {!! $field('sex', 'Sex') !!}
                    {!! $field('civil_status', 'Civil Status') !!}
                    {!! $field('height_m', 'Height (M)') !!}
                    {!! $field('weight_kg', 'Weight (KG)') !!}
                    {!! $field('blood_type', 'Blood Type') !!}
                    {!! $field('citizenship_by_birth', 'Citizenship (By Birth)') !!}
                    {!! $field('citizenship_dual', 'Citizenship (If Dual)') !!}
                    {!! $field('place_of_birth', 'Place of Birth') !!}
                    {!! $field('date_of_birth', 'Date of Birth', 'date') !!}
                    {!! $field('gsis_no', 'GSIS ID No.') !!}
                    {!! $field('pagibig_no', 'Pag-IBIG No.') !!}
                    {!! $field('philhealth_no', 'PhilHealth No.') !!}
                    {!! $field('sss_no', 'SSS No.') !!}
                    {!! $field('mobile_no', 'Mobile No.') !!}
                    {!! $field('founder_email', 'Email Address') !!}
                    <div class="md:col-span-2">{!! $field('residential_address', 'Residential Address') !!}</div>
                    <div class="md:col-span-2">{!! $field('permanent_address', 'Permanent Address') !!}</div>
                </div>

                {{-- 22. Educational Background --}}
                <div class="border rounded-lg p-4 mb-6">
                    <p class="text-xs font-medium text-gray-500 mb-2">22. EDUCATIONAL BACKGROUND</p>
                    <table class="w-full text-sm border">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-3 py-2">Level</th>
                                <th class="px-3 py-2">Name of School</th>
                                <th class="px-3 py-2">Degree/Course</th>
                                <th class="px-3 py-2">Highest Level Unit</th>
                                <th class="px-3 py-2">Year Graduated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['secondary' => 'Secondary', 'vocational' => 'Vocational/Trade', 'college' => 'College', 'graduate' => 'Graduate Studies'] as $key => $label)
                                <tr class="border-t">
                                    <td class="px-3 py-2 font-medium">{{ $label }}</td>
                                    <td class="px-1 py-1"><input type="text" name="{{ $key }}_school" value="{{ old("{$key}_school", $sheet?->{"{$key}_school"}) }}" :disabled="!editing" class="w-full border rounded px-2 py-1.5 text-sm disabled:bg-gray-50"></td>
                                    <td class="px-1 py-1"><input type="text" name="{{ $key }}_degree_course" value="{{ old("{$key}_degree_course", $sheet?->{"{$key}_degree_course"}) }}" :disabled="!editing" class="w-full border rounded px-2 py-1.5 text-sm disabled:bg-gray-50"></td>
                                    <td class="px-1 py-1"><input type="text" name="{{ $key }}_highest_level_unit" value="{{ old("{$key}_highest_level_unit", $sheet?->{"{$key}_highest_level_unit"}) }}" :disabled="!editing" class="w-full border rounded px-2 py-1.5 text-sm disabled:bg-gray-50"></td>
                                    <td class="px-1 py-1"><input type="text" name="{{ $key }}_year_graduated" value="{{ old("{$key}_year_graduated", $sheet?->{"{$key}_year_graduated"}) }}" :disabled="!editing" class="w-full border rounded px-2 py-1.5 text-sm disabled:bg-gray-50"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <p class="text-xs font-medium text-gray-500 mt-4 mb-1">23. SCHOLARSHIP / ACADEMIC HONORS RECEIVED</p>
                    <textarea name="scholarships_academic_honors" rows="3" :disabled="!editing"
                              class="w-full border rounded px-3 py-2 text-sm disabled:bg-gray-50">{{ old('scholarships_academic_honors', $sheet?->scholarships_academic_honors) }}</textarea>
                </div>

                {{-- V. Startup Information (scalar part only; name/overview shown read-only, edited via Startup Profile) --}}
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">V. STARTUP INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Startup Name</label>
                        <div class="w-full border rounded px-3 py-2 text-sm bg-gray-50 text-gray-500">{{ $startup->company_name }}</div>
                    </div>
                    {!! $field('sec_registration', 'SEC Registration') !!}
                    {!! $field('business_id_number', 'Business ID Number') !!}
                    {!! $field('dti_registration_number', 'DTI Registration Number') !!}
                    {!! $field('business_tin', 'Business TIN') !!}
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Startup Overview</label>
                        <div class="w-full border rounded px-3 py-2 text-sm bg-gray-50 text-gray-500">{{ $sheet?->business_description }}</div>
                        <p class="text-xs text-gray-400 mt-1">Edit this in Startup Profile.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Non-Academic Distinctions / Recognition / Eligibilities</label>
                        <textarea name="non_academic_distinctions" rows="2" :disabled="!editing"
                                  class="w-full border rounded px-3 py-2 text-sm disabled:bg-gray-50">{{ old('non_academic_distinctions', $sheet?->non_academic_distinctions) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Membership in Association/Organization</label>
                        <textarea name="membership_associations" rows="2" :disabled="!editing"
                                  class="w-full border rounded px-3 py-2 text-sm disabled:bg-gray-50">{{ old('membership_associations', $sheet?->membership_associations) }}</textarea>
                    </div>
                </div>

                {{-- 36. Declaration --}}
                <div class="border rounded-lg p-4 mb-6">
                    <p class="text-xs text-gray-600 mb-4">
                        I declare that I have personally accomplished this Startup Information Sheet which is a true, correct and complete statement...
                    </p>
                    <label class="block text-xs text-gray-500 mb-1">Founder's Signature</label>
                    @if ($sheet?->founder_signature_path)
                        <img src="{{ Storage::url($sheet->founder_signature_path) }}" class="h-16 mb-2 border rounded">
                    @endif
                    <input type="file" name="founder_signature" accept="image/*" x-show="editing" x-cloak class="text-sm mb-3">
                    @error('founder_signature') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <label class="block text-xs text-gray-500 mb-1 mt-3">Date Accomplished</label>
                    <input type="date" name="date_accomplished" value="{{ old('date_accomplished', $sheet?->date_accomplished?->format('Y-m-d')) }}"
                           :disabled="!editing" class="border rounded px-3 py-2 text-sm disabled:bg-gray-50">
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="editing = !editing" x-show="!editing" class="flex-1 border rounded-lg py-2.5 text-sm font-medium text-blue-900 border-blue-900">
                        Edit
                    </button>
                    <button type="submit" x-show="editing" class="flex-1 bg-rose-900 text-white rounded-lg py-2.5 text-sm font-medium">
                        Save
                    </button>
                </div>
            </form>

            {{-- II. Core Team Formation — always-editable, reuses team_members --}}
            <div class="mt-8 border rounded-lg overflow-hidden">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2">II. CORE TEAM FORMATION</h3>
                <div class="p-4 space-y-3">
                    @foreach ($startup->teamMembers as $member)
                        <form method="POST" action="{{ route('startup.team-members.update-details', $member) }}" class="grid grid-cols-4 gap-2 text-sm">
                            @csrf @method('PATCH')
                            <input type="text" name="full_name" value="{{ $member->full_name }}" placeholder="Name" class="border rounded px-2 py-1.5">
                            <input type="text" name="designation" value="{{ $member->designation }}" placeholder="Designation" class="border rounded px-2 py-1.5">
                            <input type="text" name="phone" value="{{ $member->phone }}" placeholder="Phone" class="border rounded px-2 py-1.5">
                            <input type="email" name="email" value="{{ $member->email }}" placeholder="Email" class="border rounded px-2 py-1.5">
                            <button type="submit" class="col-span-4 text-right text-xs text-rose-800">Save</button>
                        </form>
                    @endforeach
                </div>
            </div>

            {{-- III. Incubation Involvement --}}
            <div class="mt-8 border rounded-lg overflow-hidden">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2">
                    III. INCUBATION INVOLVEMENT IN GOVERNMENT / NON-GOVERNMENT / PRIVATE / TECH ORGANIZATIONS
                </h3>
                <div class="p-4 space-y-3">
                    @forelse ($sheet?->incubationInvolvements ?? [] as $item)
                        <div class="flex items-start gap-2">
                            <form method="POST" action="{{ route('startup.incubation.update', $item) }}" class="grid grid-cols-5 gap-2 text-sm flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="organization_name_address" value="{{ $item->organization_name_address }}"
                                    placeholder="Organization Name & Address" class="border rounded px-2 py-1.5 col-span-2">
                                <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}"
                                    class="border rounded px-2 py-1.5">
                                <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}"
                                    class="border rounded px-2 py-1.5">
                                <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}"
                                    placeholder="Hours" class="border rounded px-2 py-1.5">
                                <input type="text" name="incubation_program_focus" value="{{ $item->incubation_program_focus }}"
                                    placeholder="Program/Focus" class="border rounded px-2 py-1.5 col-span-4">
                                <button type="submit" class="text-xs text-rose-800 text-right">Save</button>
                            </form>
                            <form method="POST" action="{{ route('startup.incubation.destroy', $item) }}"
                                onsubmit="return confirm('Remove this entry?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-2">&times;</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">None listed yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('startup.incubation.store') }}" class="grid grid-cols-5 gap-2 text-sm pt-2 border-t">
                        @csrf
                        <input type="text" name="organization_name_address" placeholder="Organization Name & Address"
                            class="border rounded px-2 py-1.5 col-span-2">
                        <input type="date" name="date_from" class="border rounded px-2 py-1.5">
                        <input type="date" name="date_to" class="border rounded px-2 py-1.5">
                        <input type="text" name="number_of_hours" placeholder="Hours" class="border rounded px-2 py-1.5">
                        <input type="text" name="incubation_program_focus" placeholder="Program/Focus" class="border rounded px-2 py-1.5 col-span-3">
                        <button type="submit" class="text-rose-900 text-sm font-medium col-span-1">+ Add entry</button>
                    </form>
                    @error('organization_name_address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- IV. Learning and Development Interventions --}}
            <div class="mt-8 border rounded-lg overflow-hidden">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2">
                    IV. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED BY THE TEAM / FOUNDER
                </h3>
                <div class="p-4 space-y-3">
                    @forelse ($sheet?->ldInterventions ?? [] as $item)
                        <div class="flex items-start gap-2">
                            <form method="POST" action="{{ route('startup.ld.update', $item) }}" class="grid grid-cols-5 gap-2 text-sm flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="title" value="{{ $item->title }}"
                                    placeholder="Title" class="border rounded px-2 py-1.5 col-span-2">
                                <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}"
                                    class="border rounded px-2 py-1.5">
                                <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}"
                                    class="border rounded px-2 py-1.5">
                                <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}"
                                    placeholder="Hours" class="border rounded px-2 py-1.5">
                                <input type="text" name="conducted_sponsored_by" value="{{ $item->conducted_sponsored_by }}"
                                    placeholder="Conducted/Sponsored By" class="border rounded px-2 py-1.5 col-span-4">
                                <button type="submit" class="text-xs text-rose-800 text-right">Save</button>
                            </form>
                            <form method="POST" action="{{ route('startup.ld.destroy', $item) }}"
                                onsubmit="return confirm('Remove this entry?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-2">&times;</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">None listed yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('startup.ld.store') }}" class="grid grid-cols-5 gap-2 text-sm pt-2 border-t">
                        @csrf
                        <input type="text" name="title" placeholder="Title" class="border rounded px-2 py-1.5 col-span-2">
                        <input type="date" name="date_from" class="border rounded px-2 py-1.5">
                        <input type="date" name="date_to" class="border rounded px-2 py-1.5">
                        <input type="text" name="number_of_hours" placeholder="Hours" class="border rounded px-2 py-1.5">
                        <input type="text" name="conducted_sponsored_by" placeholder="Conducted/Sponsored By" class="border rounded px-2 py-1.5 col-span-3">
                        <button type="submit" class="text-rose-900 text-sm font-medium col-span-1">+ Add entry</button>
                    </form>
                    @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- 35. References --}}
            <div class="mt-8 border rounded-lg overflow-hidden">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2">35. REFERENCES</h3>
                <div class="p-4 space-y-3">
                    @forelse ($sheet?->references ?? [] as $reference)
                        <div class="flex items-start gap-2">
                            <form method="POST" action="{{ route('startup.references.update', $reference) }}" class="grid grid-cols-4 gap-2 text-sm flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="name" value="{{ $reference->name }}"
                                    placeholder="Name" class="border rounded px-2 py-1.5">
                                <input type="text" name="contact" value="{{ $reference->contact }}"
                                    placeholder="Contact" class="border rounded px-2 py-1.5">
                                <input type="email" name="email" value="{{ $reference->email }}"
                                    placeholder="Email" class="border rounded px-2 py-1.5">
                                <input type="text" name="address" value="{{ $reference->address }}"
                                    placeholder="Address" class="border rounded px-2 py-1.5">
                                <button type="submit" class="col-span-4 text-right text-xs text-rose-800">Save</button>
                            </form>
                            <form method="POST" action="{{ route('startup.references.destroy', $reference) }}"
                                onsubmit="return confirm('Remove this reference?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-2">&times;</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">None listed yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('startup.references.store') }}" class="grid grid-cols-4 gap-2 text-sm pt-2 border-t">
                        @csrf
                        <input type="text" name="name" placeholder="Name" class="border rounded px-2 py-1.5">
                        <input type="text" name="contact" placeholder="Contact" class="border rounded px-2 py-1.5">
                        <input type="email" name="email" placeholder="Email" class="border rounded px-2 py-1.5">
                        <input type="text" name="address" placeholder="Address" class="border rounded px-2 py-1.5">
                        <button type="submit" class="col-span-4 text-rose-900 text-sm font-medium text-left">+ Add reference</button>
                    </form>
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>
</x-layouts.founder>