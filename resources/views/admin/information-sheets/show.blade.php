<x-layouts.admin :title="$startup->company_name.' - Information Sheet'">
    @php $sheet = $startup->informationSheet; @endphp

    <div class="bg-white rounded-xl border border-gray-200 max-w-5xl mx-auto">
        <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h2 class="font-bold">{{ $startup->company_name }}</h2>
            <a href="{{ route('admin.startups.show', $startup) }}" class="text-white/70 hover:text-white">&times;</a>
        </div>

        <div class="p-6">
            <p class="text-center text-xs text-rose-800 font-medium mb-2">PUP-TBIDO FORM No.001</p>
            <h1 class="text-center font-bold text-lg mb-6">STARTUP INFORMATION SHEET</h1>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-gray-600 mb-6">
                Read the attached guide before accomplishing this form. Use capital letters, tick appropriate boxes, and indicate N/A if not applicable.
            </div>

            @php
                $field = fn ($label, $value) => "<div><p class='text-gray-500 text-xs mb-1'>{$label}</p><p class='border rounded px-3 py-2 bg-gray-50 text-sm'>".e($value ?? '—')."</p></div>";
            @endphp

            {{-- I. Founder's Information --}}
            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">I. FOUNDER'S INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    {!! $field('SURNAME', $sheet?->surname) !!}
                    {!! $field('FIRST NAME', $sheet?->first_name) !!}
                    {!! $field('MIDDLE NAME', $sheet?->middle_name) !!}
                    {!! $field('NAME EXTENSION', $sheet?->name_extension) !!}
                    {!! $field('SEX', $sheet?->sex) !!}
                    {!! $field('CIVIL STATUS', $sheet?->civil_status) !!}
                    {!! $field('HEIGHT (M)', $sheet?->height_m) !!}
                    {!! $field('WEIGHT (KG)', $sheet?->weight_kg) !!}
                    {!! $field('BLOOD TYPE', $sheet?->blood_type) !!}
                    {!! $field('CITIZENSHIP (BY BIRTH)', $sheet?->citizenship_by_birth) !!}
                    {!! $field('CITIZENSHIP (DUAL)', $sheet?->citizenship_dual) !!}
                    {!! $field('PLACE OF BIRTH', $sheet?->place_of_birth) !!}
                    {!! $field('DATE OF BIRTH', $sheet?->date_of_birth?->format('m/d/Y')) !!}
                    {!! $field('GSIS ID NO.', $sheet?->gsis_no) !!}
                    {!! $field('PAG-IBIG NO.', $sheet?->pagibig_no) !!}
                    {!! $field('PHILHEALTH NO.', $sheet?->philhealth_no) !!}
                    {!! $field('SSS NO.', $sheet?->sss_no) !!}
                    {!! $field('MOBILE NO.', $sheet?->mobile_no) !!}
                    {!! $field('EMAIL ADDRESS', $sheet?->founder_email) !!}
                    <div class="md:col-span-2">{!! $field('RESIDENTIAL ADDRESS', $sheet?->residential_address) !!}</div>
                    <div class="md:col-span-2">{!! $field('PERMANENT ADDRESS', $sheet?->permanent_address) !!}</div>
                </div>

                <div class="border border-t-0 p-4">
                    <p class="text-xs text-gray-500 mb-2 font-medium">22. EDUCATIONAL BACKGROUND</p>
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
                                    <td class="px-3 py-2">{{ $sheet?->{"{$key}_school"} ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $sheet?->{"{$key}_degree_course"} ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $sheet?->{"{$key}_highest_level_unit"} ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $sheet?->{"{$key}_year_graduated"} ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <p class="text-xs text-gray-500 mt-4 mb-1 font-medium">23. SCHOLARSHIP / ACADEMIC HONORS RECEIVED</p>
                    <p class="border rounded px-3 py-2 bg-gray-50 text-sm whitespace-pre-line">{{ $sheet?->scholarships_academic_honors ?? '—' }}</p>
                </div>
            </div>

            {{-- II. Core Team Formation --}}
            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">II. CORE TEAM FORMATION</h3>
                <div class="border border-t-0 rounded-b-lg overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Designation</th>
                                <th class="px-3 py-2">Phone No.</th>
                                <th class="px-3 py-2">Address</th>
                                <th class="px-3 py-2">Date of Birth</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2">Citizenship</th>
                                <th class="px-3 py-2">Sex</th>
                                <th class="px-3 py-2">Civil Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($startup->teamMembers as $member)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $member->full_name }}</td>
                                    <td class="px-3 py-2">{{ $member->designation ?? $member->role }}</td>
                                    <td class="px-3 py-2">{{ $member->phone }}</td>
                                    <td class="px-3 py-2">{{ $member->address }}</td>
                                    <td class="px-3 py-2">{{ $member->date_of_birth?->format('m/d/Y') }}</td>
                                    <td class="px-3 py-2">{{ $member->email }}</td>
                                    <td class="px-3 py-2">{{ $member->citizenship }}</td>
                                    <td class="px-3 py-2">{{ $member->sex }}</td>
                                    <td class="px-3 py-2">{{ $member->civil_status }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-2 text-gray-400" colspan="9">No team members listed.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- III. Incubation Involvement --}}
            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">III. INCUBATION INVOLVEMENT IN GOVERNMENT / NON-GOVERNMENT / PRIVATE / TECH ORGANIZATIONS</h3>
                <div class="border border-t-0 rounded-b-lg overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-3 py-2">Organization</th>
                                <th class="px-3 py-2">From</th>
                                <th class="px-3 py-2">To</th>
                                <th class="px-3 py-2">Hours</th>
                                <th class="px-3 py-2">Program/Focus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sheet?->incubationInvolvements ?? [] as $item)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $item->organization_name_address }}</td>
                                    <td class="px-3 py-2">{{ $item->date_from?->format('m/d/Y') }}</td>
                                    <td class="px-3 py-2">{{ $item->date_to?->format('m/d/Y') }}</td>
                                    <td class="px-3 py-2">{{ $item->number_of_hours }}</td>
                                    <td class="px-3 py-2">{{ $item->incubation_program_focus }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-2 text-gray-400" colspan="5">None listed.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- IV. L&D Interventions --}}
            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">IV. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS</h3>
                <div class="border border-t-0 rounded-b-lg overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-3 py-2">Title</th>
                                <th class="px-3 py-2">From</th>
                                <th class="px-3 py-2">To</th>
                                <th class="px-3 py-2">Hours</th>
                                <th class="px-3 py-2">Conducted/Sponsored By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sheet?->ldInterventions ?? [] as $item)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $item->title }}</td>
                                    <td class="px-3 py-2">{{ $item->date_from?->format('m/d/Y') }}</td>
                                    <td class="px-3 py-2">{{ $item->date_to?->format('m/d/Y') }}</td>
                                    <td class="px-3 py-2">{{ $item->number_of_hours }}</td>
                                    <td class="px-3 py-2">{{ $item->conducted_sponsored_by }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-2 text-gray-400" colspan="5">None listed.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- V. Startup Information --}}
            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">V. STARTUP INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    {!! $field('STARTUP NAME', $startup->company_name) !!}
                    {!! $field('SEC REGISTRATION', $sheet?->sec_registration) !!}
                    {!! $field('BUSINESS ID NUMBER', $sheet?->business_id_number) !!}
                    {!! $field('DTI REGISTRATION NUMBER', $sheet?->dti_registration_number) !!}
                    {!! $field('BUSINESS TIN', $sheet?->business_tin) !!}
                    <div class="md:col-span-2">{!! $field('STARTUP OVERVIEW', $sheet?->business_description) !!}</div>
                    <div class="md:col-span-2">{!! $field('TARGET MARKET', $sheet?->target_market) !!}</div>
                    <div class="md:col-span-2">{!! $field('PROBLEM STATEMENT', $sheet?->problem_statement) !!}</div>
                    <div class="md:col-span-2">{!! $field('SOLUTION OFFERED', $sheet?->solution_offered) !!}</div>
                    <div class="md:col-span-2">{!! $field('NON-ACADEMIC DISTINCTIONS / RECOGNITION / ELIGIBILITIES', $sheet?->non_academic_distinctions) !!}</div>
                    <div class="md:col-span-2">{!! $field('MEMBERSHIP IN ASSOCIATION/ORGANIZATION', $sheet?->membership_associations) !!}</div>
                </div>

                <div class="border border-t-0 p-4">
                    <p class="text-xs text-gray-500 mb-2 font-medium">35. REFERENCES</p>
                    <table class="w-full text-sm border">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Contact</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2">Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sheet?->references ?? [] as $ref)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $ref->name }}</td>
                                    <td class="px-3 py-2">{{ $ref->contact }}</td>
                                    <td class="px-3 py-2">{{ $ref->email }}</td>
                                    <td class="px-3 py-2">{{ $ref->address }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-2 text-gray-400" colspan="4">None listed.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 36. Declaration & Endorsement --}}
            <div class="mb-6 border rounded-lg p-4">
                <p class="text-xs text-gray-600 mb-4">
                    I declare that I have personally accomplished this Startup Information Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines.
                </p>

                <div class="grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">FOUNDER'S SIGNATURE</p>
                        @if ($sheet?->founder_signature_path)
                            <img src="{{ Storage::url($sheet->founder_signature_path) }}" class="border rounded h-20 object-contain">
                        @else
                            <div class="border rounded px-3 py-6 bg-gray-50 text-center text-gray-400 text-xs">No signature uploaded</div>
                        @endif
                        <p class="text-xs text-gray-500 mt-2">Date Accomplished: {{ $sheet?->date_accomplished?->format('m/d/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">DIRECTOR'S SIGNATURE</p>
                        @if ($sheet?->director_signature_path)
                            <img src="{{ Storage::url($sheet->director_signature_path) }}" class="border rounded h-20 object-contain">
                        @else
                            <div class="border rounded px-3 py-6 bg-gray-50 text-center text-gray-400 text-xs">No signature uploaded</div>
                        @endif
                        <p class="text-xs text-gray-500 mt-2">Date of Approval: {{ $sheet?->director_approval_date?->format('m/d/Y') ?? '—' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
                    {!! $field('PORTFOLIO MANAGER', $sheet?->portfolio_manager) !!}
                    {!! $field('COHORT NO.', $sheet?->cohort_no) !!}
                    {!! $field('ENDORSED BY', $sheet?->endorsed_by) !!}
                    {!! $field('ENDORSEMENT DATE', $sheet?->endorsement_date?->format('m/d/Y')) !!}
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex gap-3" x-data="{ rejecting: false }">
                <a href="{{ route('admin.startups.show', $startup) }}"
                   class="flex-1 text-center border rounded-lg py-3 text-sm font-medium text-blue-900 border-blue-900">
                    BACK
                </a>

                @if ($sheet?->approval_status === 'Pending')
                    <button type="button" @click="rejecting = !rejecting"
                            class="flex-1 border border-red-700 text-red-700 rounded-lg py-3 text-sm font-medium">
                        REJECT
                    </button>

                    <form method="POST" action="{{ route('admin.information-sheet.approve', $startup) }}" class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full bg-rose-900 text-white rounded-lg py-3 text-sm font-medium">
                            APPROVE
                        </button>
                    </form>
                @endif
            </div>

            <div x-data="{ rejecting: false }" x-show="rejecting" x-cloak class="mt-4">
                <form method="POST" action="{{ route('admin.information-sheet.reject', $startup) }}">
                    @csrf
                    @method('PATCH')
                    <textarea name="evaluator_remarks" rows="3" placeholder="Reason for rejection..."
                              class="w-full border rounded-lg p-3 text-sm"></textarea>
                    <button type="submit" class="mt-2 bg-red-700 text-white rounded-lg px-4 py-2 text-sm font-medium">
                        Confirm Rejection
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>