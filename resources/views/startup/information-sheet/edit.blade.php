<x-layouts.founder title="Information Sheet">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Information Sheet</h1>
        <p class="text-gray-500 mt-1">View your details below.</p>
    </div>

    @php $sheet = $startup->informationSheet; @endphp

    <div
        class="bg-white rounded-xl border border-gray-200 max-w-6xl overflow-hidden"
        x-data="{
            editing: false,
            isLocked: {{ $sheet?->approval_status === 'Approved' ? 'true' : 'false' }},
            saving: false,
            async saveAll() {
                this.saving = true;
                try {
                    await window.submitInfoSheetForms(this.$root);
                    window.location.reload();
                } catch (e) {
                    this.saving = false;
                    alert('Something went wrong while saving. Please try again.');
                }
            }
        }">

        {{-- Startup Header Bar --}}
        <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white px-6 py-3 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h2 class="font-bold text-sm">{{ $startup->company_name }}</h2>
        </div>

        <div class="p-6">
            <div class="flex justify-center mb-3">
                <span class="border border-rose-800 text-rose-800 text-xs italic font-medium px-4 py-1 rounded-full">PUP-TBIDO FORM No.001</span>
            </div>
            <h1 class="text-center font-bold text-xl text-blue-950 mb-4">STARTUP INFORMATION SHEET</h1>

            {{-- Instruction box --}}
            <div class="flex gap-3 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 mb-6 text-xs text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-blue-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <ul class="space-y-0.5 italic">
                    <li>Read The Attached Guide to Filling Out the Startup Information Sheet Before Accomplishing the Pup-TBIDO Form No. 100.</li>
                    <li>Use Capital Letters and Print Legibly. Tick Appropriate Boxes and Use Separate Sheet if Necessary. Indicate N/A If Not Applicable. Do Not Abbreviate.</li>
                    <li>Date Format (mm/dd/yyyy)</li>
                </ul>
            </div>

            <form id="info-sheet-form" method="POST" action="{{ route('startup.information-sheet.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- I. FOUNDER'S INFORMATION --}}
                <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2 rounded-t-lg">I. FOUNDER'S INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 mb-6">
                    @php
                    // Renders a numbered, horizontally-aligned field: "N. LABEL: [input]"
                    // Presentational only — underlying name/value/disabled logic is unchanged.
                    $field = function ($name, $label, $number = null, $type = 'text') use ($sheet) {
                    $value = old($name, $sheet?->{$name});
                    $numHtml = $number ? "<span class='font-semibold'>{$number}.</span> " : '';
                    return "<div class='flex items-center gap-2 py-1.5 text-sm'>
                        <label class='w-48 flex-shrink-0 text-gray-800'>{$numHtml}".e($label).":</label>
                        <input type=\"{$type}\" name=\"{$name}\" value=\"".e($value)."\" form=\"info-sheet-form\"
                            :disabled=\"!editing\" placeholder=\"SAMPLE\"
                            class='flex-1 border rounded px-3 py-1.5 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300'>
                    </div>";
                    };
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                        {{-- Left column --}}
                        <div>
                            {!! $field('surname', 'SURNAME', 1) !!}
                            {!! $field('first_name', 'FIRST NAME', 2) !!}
                            {!! $field('middle_name', 'MIDDLE NAME', 3) !!}
                            {!! $field('name_extension', 'NAME EXTENSION', 4) !!}
                            {!! $field('height_m', 'HEIGHT (M)', 5) !!}
                            {!! $field('weight_kg', 'WEIGHT (KG)', 6) !!}
                            {!! $field('blood_type', 'BLOOD TYPE', 7) !!}
                            {!! $field('gsis_no', 'GSIS ID NO.', 8) !!}
                            {!! $field('pagibig_no', 'PAG-IBIG NO.', 9) !!}
                            {!! $field('philhealth_no', 'PHILHEALTH NO.', 10) !!}
                            {!! $field('sss_no', 'SSS NO.', 11) !!}
                            {!! $field('residential_address', 'RESIDENTIAL ADDRESS', 12) !!}
                            {!! $field('permanent_address', 'PERMANENT ADDRESS', 13) !!}
                        </div>

                        {{-- Right column --}}
                        <div>
                            {!! $field('sex', 'SEX', 15) !!}
                            {!! $field('civil_status', 'CIVIL STATUS', 16) !!}

                            <div class="flex items-start gap-2 py-1.5 text-sm">
                                <label class="w-48 flex-shrink-0 text-gray-800"><span class="font-semibold">17.</span> CITIZENSHIP:</label>
                                <div class="flex-1 space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-32 flex-shrink-0">&bull; By Birth</span>
                                        <input type="text" name="citizenship_by_birth" value="{{ e(old('citizenship_by_birth', $sheet?->citizenship_by_birth)) }}" form="info-sheet-form"
                                            :disabled="!editing" placeholder="SAMPLE"
                                            class="flex-1 border rounded px-3 py-1.5 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-32 flex-shrink-0">&bull; If Dual Citizenship</span>
                                        <input type="text" name="citizenship_dual" value="{{ e(old('citizenship_dual', $sheet?->citizenship_dual)) }}" form="info-sheet-form"
                                            :disabled="!editing" placeholder="SAMPLE"
                                            class="flex-1 border rounded px-3 py-1.5 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300">
                                    </div>
                                </div>
                            </div>

                            {!! $field('place_of_birth', 'PLACE OF BIRTH', 18) !!}
                            {!! $field('date_of_birth', 'DATE OF BIRTH', 18, 'date') !!}
                            {!! $field('mobile_no', 'MOBILE NO.', 20) !!}
                            {!! $field('founder_email', 'EMAIL ADDRESS', 21) !!}
                        </div>
                    </div>
                </div>

                {{-- 22. Educational Background --}}
                <div class="mb-6">
                    <p class="text-xs font-semibold text-gray-700 mb-1">22. EDUCATIONAL BACKGROUND</p>
                    <table class="w-full text-sm border border-collapse">
                        <thead class="bg-gray-50 text-left text-xs">
                            <tr>
                                <th class="border px-3 py-2">LEVEL</th>
                                <th class="border px-3 py-2">NAME OF SCHOOL</th>
                                <th class="border px-3 py-2">EDUCATIONAL/DEGREE/COURSE</th>
                                <th class="border px-3 py-2">HIGHEST LEVEL UNIT</th>
                                <th class="border px-3 py-2">YEAR GRADUATED</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['secondary' => 'SECONDARY', 'vocational' => 'VOCATIONAL/TRADE COURSE', 'college' => 'COLLEGE', 'graduate' => 'GRADUATE STUDIES'] as $key => $label)
                            <tr>
                                <td class="border px-3 py-2 font-medium text-xs align-top">{{ $label }}</td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_school" value="{{ old("{$key}_school", $sheet?->{"{$key}_school"}) }}" form="info-sheet-form" :disabled="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_degree_course" value="{{ old("{$key}_degree_course", $sheet?->{"{$key}_degree_course"}) }}" form="info-sheet-form" :disabled="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_highest_level_unit" value="{{ old("{$key}_highest_level_unit", $sheet?->{"{$key}_highest_level_unit"}) }}" form="info-sheet-form" :disabled="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_year_graduated" value="{{ old("{$key}_year_graduated", $sheet?->{"{$key}_year_graduated"}) }}" form="info-sheet-form" :disabled="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <p class="text-xs font-semibold text-gray-700 mt-4 mb-1">23. SCHOLARSHIP / ACADEMIC HONORS RECEIVED</p>
                    <textarea name="scholarships_academic_honors" rows="4" form="info-sheet-form" :disabled="!editing" placeholder="SAMPLE"
                        class="w-full border rounded px-3 py-2 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300">{{ old('scholarships_academic_honors', $sheet?->scholarships_academic_honors) }}</textarea>
                </div>
            </form>
            {{-- The main form closes here for layout purposes only; every field below that belongs to it
                 carries form="info-sheet-form" so it still submits together with everything above. --}}

            {{-- II. Core Team Formation --}}
            <div class="mt-8 rounded-lg overflow-hidden">
                <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2">II. CORE TEAM FORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4">
                    <p class="text-xs font-semibold text-gray-700 mb-2">24.</p>
                    {{--
                        NOTE ON NEW COLUMNS: the screenshot's team table has more columns (Address, Date of
                        Birth, Citizenship, Sex, Civil Status) than the team_members table currently has.
                        The inputs below are wired up and ready to submit (address, date_of_birth,
                        citizenship, sex, civil_status) — the update-details route/controller and the
                        team_members table will need matching columns added so these values persist.
                        full_name / designation / phone / email already persist today.
                    --}}
                    @php
                    // Single source of truth for column widths, so the header row and every
                    // data row line up exactly no matter how many team members there are.
                    // Widths are literal Tailwind classes (not runtime CSS) so there's nothing
                    // for an editor's CSS linter to misread, and Tailwind can still see them
                    // because the class strings appear verbatim in this file.
                    $teamCols = [
                    ['w' => 'w-[220px]', 'label' => 'NAME (SURNAME, FIRSTNAME, MIDDLE NAME, EXT)'],
                    ['w' => 'w-[140px]', 'label' => 'DESIGNATION'],
                    ['w' => 'w-[120px]', 'label' => 'PHONE NO.'],
                    ['w' => 'w-[180px]', 'label' => 'ADDRESS'],
                    ['w' => 'w-[130px]', 'label' => 'DATE OF BIRTH'],
                    ['w' => 'w-[180px]', 'label' => 'EMAIL'],
                    ['w' => 'w-[130px]', 'label' => 'CITIZENSHIP'],
                    ['w' => 'w-[70px]', 'label' => 'SEX'],
                    ['w' => 'w-[130px]', 'label' => 'CIVIL STATUS'],
                    ];
                    @endphp
                    <div class="border rounded-lg overflow-x-auto">
                        <div class="inline-flex flex-col">
                            {{-- Header --}}
                            <div class="flex bg-gray-50 text-[11px] font-semibold text-gray-600 border-b uppercase tracking-wide">
                                @foreach ($teamCols as $col)
                                <div class="px-3 py-2.5 flex-shrink-0 border-r last:border-r-0 leading-tight {{ $col['w'] }}">{{ $col['label'] }}</div>
                                @endforeach
                            </div>

                            {{-- Rows --}}
                            @forelse ($startup->teamMembers as $member)
                            <form method="POST" action="{{ route('startup.team-members.update-details', $member) }}"
                                class="js-subform flex text-sm border-b last:border-b-0 odd:bg-white even:bg-gray-50/50">
                                @csrf @method('PATCH')
                                <div class="flex-shrink-0 border-r {{ $teamCols[0]['w'] }}">
                                    <input type="text" name="full_name" value="{{ $member->full_name }}" placeholder="Name" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 border-r {{ $teamCols[1]['w'] }}">
                                    <input type="text" name="designation" value="{{ $member->designation }}" placeholder="Designation" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 border-r {{ $teamCols[2]['w'] }}">
                                    <input type="text" name="phone" value="{{ $member->phone }}" placeholder="Phone" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 border-r {{ $teamCols[3]['w'] }}">
                                    <input type="text" name="address" value="{{ $member->address ?? '' }}" placeholder="Address" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 border-r {{ $teamCols[4]['w'] }}">
                                    <input type="date" name="date_of_birth" value="{{ $member->date_of_birth ?? '' }}" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 border-r {{ $teamCols[5]['w'] }}">
                                    <input type="email" name="email" value="{{ $member->email }}" placeholder="Email" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 border-r {{ $teamCols[6]['w'] }}">
                                    <input type="text" name="citizenship" value="{{ $member->citizenship ?? '' }}" placeholder="Citizenship" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 border-r {{ $teamCols[7]['w'] }}">
                                    <input type="text" name="sex" value="{{ $member->sex ?? '' }}" placeholder="Sex" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                                <div class="flex-shrink-0 {{ $teamCols[8]['w'] }}">
                                    <input type="text" name="civil_status" value="{{ $member->civil_status ?? '' }}" placeholder="Civil Status" :disabled="!editing" class="w-full h-full px-3 py-2 focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                </div>
                            </form>
                            @empty
                            <p class="text-sm text-gray-400 p-3">None listed yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- III. Incubation Involvement --}}
            <div class="mt-8 rounded-lg overflow-hidden">
                <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2">
                    III. INCUBATION INVOLVEMENT IN GOVERNMENT / NON-GOVERNMENT / PRIVATE / TECH ORGANIZATIONS
                </h3>
                <div class="border border-t-0 rounded-b-lg p-4">
                    <p class="text-xs font-semibold text-gray-700 mb-2">25.</p>
                    {{--
                        Fixed column widths shared by the header, every saved row, and the "add new"
                        row, so the boxes line up instead of drifting between rows.
                        Layout: [Org name & address] [From] [To] [Hours] [Program/Focus] [delete]
                    --}}
                    <div class="border rounded-lg overflow-x-auto">
                        <div class="min-w-[820px]">
                            <div class="flex bg-gray-50 text-xs font-semibold text-gray-600 border-b uppercase tracking-wide">
                                <div class="flex-1 min-w-[220px] px-3 py-2.5 border-r">Name &amp; Address of Organization <span class="normal-case font-normal text-gray-400">(write in full)</span></div>
                                <div class="w-28 flex-shrink-0 px-3 py-2.5 border-r">From</div>
                                <div class="w-28 flex-shrink-0 px-3 py-2.5 border-r">To</div>
                                <div class="w-24 flex-shrink-0 px-3 py-2.5 border-r">Hours</div>
                                <div class="flex-1 min-w-[200px] px-3 py-2.5 border-r">Incubation Program / Focus</div>
                                <div class="w-10 flex-shrink-0"></div>
                            </div>

                            @forelse ($sheet?->incubationInvolvements ?? [] as $item)
                            <div class="flex items-stretch border-b last:border-b-0 odd:bg-white even:bg-gray-50/50">
                                <form method="POST" action="{{ route('startup.incubation.update', $item) }}" class="js-subform flex flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex-1 min-w-[220px] border-r">
                                        <input type="text" name="organization_name_address" value="{{ $item->organization_name_address }}"
                                            placeholder="Organization Name & Address" :disabled="!editing" class="w-full h-full px-3 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="w-28 flex-shrink-0 border-r">
                                        <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}"
                                            :disabled="!editing" class="w-full h-full px-2 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="w-28 flex-shrink-0 border-r">
                                        <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}"
                                            :disabled="!editing" class="w-full h-full px-2 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="w-24 flex-shrink-0 border-r">
                                        <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}"
                                            placeholder="Hours" :disabled="!editing" class="w-full h-full px-2 py-2 text-sm text-center focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="flex-1 min-w-[200px] border-r">
                                        <input type="text" name="incubation_program_focus" value="{{ $item->incubation_program_focus }}"
                                            placeholder="Program/Focus" :disabled="!editing" class="w-full h-full px-3 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('startup.incubation.destroy', $item) }}"
                                    onsubmit="return confirm('Remove this entry?')" x-show="editing" x-cloak
                                    class="w-10 flex-shrink-0 flex items-center justify-center">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-base leading-none">&times;</button>
                                </form>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 p-3">None listed yet.</p>
                            @endforelse

                            <form method="POST" action="{{ route('startup.incubation.store') }}" class="js-subform js-addform flex border-t bg-blue-50/40" x-show="editing" x-cloak>
                                <div class="flex-1 min-w-[220px] border-r">
                                    <input type="text" name="organization_name_address" placeholder="Organization Name & Address"
                                        class="w-full h-full px-3 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-28 flex-shrink-0 border-r">
                                    <input type="date" name="date_from" class="w-full h-full px-2 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-28 flex-shrink-0 border-r">
                                    <input type="date" name="date_to" class="w-full h-full px-2 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-24 flex-shrink-0 border-r">
                                    <input type="text" name="number_of_hours" placeholder="Hours" class="w-full h-full px-2 py-2 text-sm text-center bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="flex-1 min-w-[200px] border-r">
                                    <input type="text" name="incubation_program_focus" placeholder="Program/Focus" class="w-full h-full px-3 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-10 flex-shrink-0 flex items-center justify-center text-rose-900" title="Add entry">
                                    <button type="submit" class="text-lg leading-none">+</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @error('organization_name_address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- IV. Learning and Development Interventions --}}
            <div class="mt-8 rounded-lg overflow-hidden">
                <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2">
                    IV. LEARNING AND DEVELOPMENT (L&amp;D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED BY THE TEAM / FOUNDER
                </h3>
                <div class="border border-t-0 rounded-b-lg p-4">
                    <p class="text-xs font-semibold text-gray-700 mb-2">26.</p>
                    {{--
                        Fixed column widths shared by the header, every saved row, and the "add new"
                        row, so the boxes line up instead of drifting between rows.
                        Layout: [Title] [From] [To] [Hours] [Conducted/Sponsored By] [delete]
                    --}}
                    <div class="border rounded-lg overflow-x-auto">
                        <div class="min-w-[820px]">
                            <div class="flex bg-gray-50 text-xs font-semibold text-gray-600 border-b uppercase tracking-wide">
                                <div class="flex-1 min-w-[220px] px-3 py-2.5 border-r">Title of L&amp;D Intervention <span class="normal-case font-normal text-gray-400">(write in full)</span></div>
                                <div class="w-28 flex-shrink-0 px-3 py-2.5 border-r">From</div>
                                <div class="w-28 flex-shrink-0 px-3 py-2.5 border-r">To</div>
                                <div class="w-24 flex-shrink-0 px-3 py-2.5 border-r">Hours</div>
                                <div class="flex-1 min-w-[200px] px-3 py-2.5 border-r">Conducted / Sponsored By</div>
                                <div class="w-10 flex-shrink-0"></div>
                            </div>

                            @forelse ($sheet?->ldInterventions ?? [] as $item)
                            <div class="flex items-stretch border-b last:border-b-0 odd:bg-white even:bg-gray-50/50">
                                <form method="POST" action="{{ route('startup.ld.update', $item) }}" class="js-subform flex flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex-1 min-w-[220px] border-r">
                                        <input type="text" name="title" value="{{ $item->title }}"
                                            placeholder="Title" :disabled="!editing" class="w-full h-full px-3 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="w-28 flex-shrink-0 border-r">
                                        <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}"
                                            :disabled="!editing" class="w-full h-full px-2 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="w-28 flex-shrink-0 border-r">
                                        <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}"
                                            :disabled="!editing" class="w-full h-full px-2 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="w-24 flex-shrink-0 border-r">
                                        <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}"
                                            placeholder="Hours" :disabled="!editing" class="w-full h-full px-2 py-2 text-sm text-center focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                    <div class="flex-1 min-w-[200px] border-r">
                                        <input type="text" name="conducted_sponsored_by" value="{{ $item->conducted_sponsored_by }}"
                                            placeholder="Conducted/Sponsored By" :disabled="!editing" class="w-full h-full px-3 py-2 text-sm focus:outline-none focus:bg-blue-50 disabled:bg-transparent disabled:text-gray-500">
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('startup.ld.destroy', $item) }}"
                                    onsubmit="return confirm('Remove this entry?')" x-show="editing" x-cloak
                                    class="w-10 flex-shrink-0 flex items-center justify-center">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-base leading-none">&times;</button>
                                </form>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 p-3">None listed yet.</p>
                            @endforelse

                            <form method="POST" action="{{ route('startup.ld.store') }}" class="js-subform js-addform flex border-t bg-blue-50/40" x-show="editing" x-cloak>
                                <div class="flex-1 min-w-[220px] border-r">
                                    <input type="text" name="title" placeholder="Title" class="w-full h-full px-3 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-28 flex-shrink-0 border-r">
                                    <input type="date" name="date_from" class="w-full h-full px-2 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-28 flex-shrink-0 border-r">
                                    <input type="date" name="date_to" class="w-full h-full px-2 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-24 flex-shrink-0 border-r">
                                    <input type="text" name="number_of_hours" placeholder="Hours" class="w-full h-full px-2 py-2 text-sm text-center bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="flex-1 min-w-[200px] border-r">
                                    <input type="text" name="conducted_sponsored_by" placeholder="Conducted/Sponsored By" class="w-full h-full px-3 py-2 text-sm bg-transparent focus:outline-none focus:bg-white">
                                </div>
                                <div class="w-10 flex-shrink-0 flex items-center justify-center text-rose-900" title="Add entry">
                                    <button type="submit" class="text-lg leading-none">+</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- V. Startup Information --}}
            <h3 class="mt-8 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2 rounded-t-lg">V. STARTUP INFORMATION</h3>
            <div class="border border-t-0 rounded-b-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
                    <div>
                        <div class="flex items-center gap-2 py-1.5 text-sm">
                            <label class="w-40 flex-shrink-0 text-gray-800"><span class="font-semibold">27.</span> STARTUP NAME:</label>
                            <div class="flex-1 border rounded px-3 py-1.5 text-sm bg-gray-50 text-gray-500">{{ $startup->company_name }}</div>
                        </div>
                        {!! $field('sec_registration', 'SEC REGISTRATION', 28) !!}
                        {!! $field('business_id_number', 'BUSINESS ID NUMBER', 29) !!}
                        {!! $field('dti_registration_number', 'DTI REGISTRATION NUMBER', 30) !!}
                        {!! $field('business_tin', 'BUSINESS TIN', 31) !!}
                    </div>
                    <div>
                        <p class="text-sm text-gray-800 mb-1"><span class="font-semibold">33.</span> STARTUP OVERVIEW</p>
                        <div class="w-full border rounded px-3 py-2 text-sm bg-gray-50 text-gray-500 min-h-[9rem]">{{ $sheet?->business_description }}</div>
                        <p class="text-xs text-gray-400 mt-1">Edit this in Startup Profile.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-700 mb-1">31. NON-ACADEMIC DISTINCTIONS / RECOGNITION / ELIGIBILITIES</p>
                        <textarea name="non_academic_distinctions" rows="3" form="info-sheet-form" :disabled="!editing" placeholder="SAMPLE"
                            class="w-full border rounded px-3 py-2 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300">{{ old('non_academic_distinctions', $sheet?->non_academic_distinctions) }}</textarea>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-700 mb-1">34. MEMBERSHIP IN ASSOCIATION/ORGANIZATION</p>
                        <textarea name="membership_associations" rows="3" form="info-sheet-form" :disabled="!editing" placeholder="SAMPLE"
                            class="w-full border rounded px-3 py-2 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300">{{ old('membership_associations', $sheet?->membership_associations) }}</textarea>
                    </div>
                </div>

                {{-- 35. References --}}
                <div class="mt-6">
                    <p class="text-xs font-semibold text-gray-700 mb-2">35. REFERENCES</p>
                    <div class="border rounded overflow-hidden">
                        <div class="grid grid-cols-4 gap-0 bg-gray-50 text-xs font-medium border-b">
                            <div class="px-3 py-2 border-r">NAME</div>
                            <div class="px-3 py-2 border-r">CONTACT</div>
                            <div class="px-3 py-2 border-r">EMAIL ADDRESS</div>
                            <div class="px-3 py-2">ADDRESS</div>
                        </div>
                        @forelse ($sheet?->references ?? [] as $reference)
                        <div class="flex items-start gap-2 border-b last:border-b-0 px-2 py-1.5">
                            <form method="POST" action="{{ route('startup.references.update', $reference) }}" class="js-subform grid grid-cols-4 gap-2 text-sm flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="name" value="{{ $reference->name }}"
                                    placeholder="Name" :disabled="!editing" class="border rounded px-2 py-1.5 disabled:bg-gray-50 disabled:text-gray-500">
                                <input type="text" name="contact" value="{{ $reference->contact }}"
                                    placeholder="Contact" :disabled="!editing" class="border rounded px-2 py-1.5 disabled:bg-gray-50 disabled:text-gray-500">
                                <input type="email" name="email" value="{{ $reference->email }}"
                                    placeholder="Email" :disabled="!editing" class="border rounded px-2 py-1.5 disabled:bg-gray-50 disabled:text-gray-500">
                                <input type="text" name="address" value="{{ $reference->address }}"
                                    placeholder="Address" :disabled="!editing" class="border rounded px-2 py-1.5 disabled:bg-gray-50 disabled:text-gray-500">
                            </form>
                            <form method="POST" action="{{ route('startup.references.destroy', $reference) }}"
                                onsubmit="return confirm('Remove this reference?')" x-show="editing" x-cloak>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-2">&times;</button>
                            </form>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 p-3">None listed yet.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('startup.references.store') }}" class="js-subform js-addform grid grid-cols-4 gap-2 text-sm pt-3" x-show="editing" x-cloak>
                        @csrf
                        <input type="text" name="name" placeholder="Name" class="border rounded px-2 py-1.5">
                        <input type="text" name="contact" placeholder="Contact" class="border rounded px-2 py-1.5">
                        <input type="email" name="email" placeholder="Email" class="border rounded px-2 py-1.5">
                        <input type="text" name="address" placeholder="Address" class="border rounded px-2 py-1.5">
                    </form>
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- 36. Declaration --}}
            <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-xs font-semibold px-4 py-2 rounded-t-lg">DECLARATION</div>
            <div class="border border-t-0 rounded-b-lg p-4 mb-4">
                <p class="text-xs text-gray-600 mb-4">
                    <span class="font-semibold">36.</span> I declare that I have personally accomplished this Startup Information Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal case/s against me.
                </p>

                <div class="flex flex-col items-center">
                    @if ($sheet?->founder_signature_path)
                    <img src="{{ Storage::url($sheet->founder_signature_path) }}" class="h-16 mb-2 border rounded">
                    @endif
                    <div class="border-2 rounded w-full max-w-md h-16 mb-1 flex items-center justify-center">
                        <input type="file" name="founder_signature" accept="image/*" x-show="editing && !isLocked" x-cloak class="text-xs" form="info-sheet-form">
                    </div>
                    @error('founder_signature') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-500">Founder's Signature (Sign Inside the box)</p>
                </div>

                <div class="flex flex-col items-center mt-6">
                    <input type="date" name="date_accomplished" value="{{ old('date_accomplished', $sheet?->date_accomplished?->format('Y-m-d')) }}"
                        :disabled="!editing" form="info-sheet-form" class="border-0 border-b text-sm text-center disabled:bg-transparent disabled:text-gray-500 w-64">
                    <p class="text-xs text-gray-500 mt-1">Date Accomplished</p>
                </div>
            </div>

            <div class="flex gap-3">
                <template x-if="isLocked">
                    <div class="flex-1 text-center bg-gray-100 text-gray-500 rounded-lg py-2.5 text-sm font-medium">
                        Approved &amp; Locked — contact your Coordinator for changes
                    </div>
                </template>
                <template x-if="!isLocked">
                    <div x-show="!editing"
                        class="flex-1 rounded-lg p-[1.5px] bg-gradient-to-r from-[#6D0D23] to-[#11386A]">

                        <button
                            type="button"
                            @click="editing = !editing"
                            class="w-full rounded-[7px] bg-white py-2.5 text-sm font-semibold text-[#11386A]
                   transition-all duration-200
                   hover:bg-slate-50
                   hover:shadow-sm">
                            Edit
                        </button>

                    </div>
                </template>
                <button type="button" x-show="editing && !isLocked" x-cloak @click="saveAll()" :disabled="saving"
                    class="flex-1 bg-rose-900 text-white rounded-lg py-2.5 text-sm font-semibold disabled:opacity-60">
                    <span x-text="saving ? 'Saving…' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- One Save button now submits every section (Founder's Info, Educational Background,
         Startup Info, Declaration, Core Team, Incubation, L&D, References) together.
         Each section still POSTs to its own existing Laravel route/controller — nothing on
         the backend changes — this just chains those existing requests behind a single click
         instead of showing a Save button per row. --}}
    <script>
        window.submitInfoSheetForms = async function(root) {
            root = root || document;

            const isBlank = (form) => {
                const data = new FormData(form);
                for (const [key, val] of data.entries()) {
                    if (['_token', '_method'].includes(key)) continue;
                    if (typeof val === 'string' && val.trim() !== '') return false;
                    if (val instanceof File && val.size > 0) return false;
                }
                return true;
            };

            const mainForm = root.querySelector('#info-sheet-form') || document.getElementById('info-sheet-form');
            const subForms = Array.from(root.querySelectorAll('form.js-subform'));

            const forms = [mainForm, ...subForms].filter(Boolean).filter((form) => {
                // Skip empty "add new entry" rows so we don't create blank records.
                if (form.classList.contains('js-addform') && isBlank(form)) return false;
                return true;
            });

            for (const form of forms) {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });
                if (!response.ok) {
                    throw new Error('Request to ' + form.action + ' failed with status ' + response.status);
                }
            }
        };
    </script>
</x-layouts.founder>