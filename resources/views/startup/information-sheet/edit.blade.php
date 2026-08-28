<x-layouts.founder title="Information Sheet">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Information Sheet</h1>
        <p class="text-gray-500 mt-1">View your details below.</p>
    </div>

    @php
        $sheet = $startup->informationSheet;
        $lockReason = $sheet?->approval_status === 'Approved'
            ? 'Approved & Locked — contact your Coordinator for changes'
            : ($startup->evaluationDayLockActive()
                ? 'Locked — your evaluation day has started. Contact your Coordinator for changes'
                : null);
    @endphp


    <div
        class="bg-white rounded-xl border border-gray-200 max-w-6xl overflow-hidden"
        x-data="{
    editing: false,
    isLocked: {{ $lockReason ? 'true' : 'false' }},
    saving: false,
    dirty: false,
    lastClickedInput: null,
 
    newRows: { team: [], inc: [], ld: [], ref: [] },
    nextRowId: 1,

    addRow(section) {
        this.newRows[section].push({ id: this.nextRowId++ });
    },

    // Iba ito sa toggleRemoval(): walang ipapadalang DELETE, burado agad.
    discardRow(section, id) {
        this.newRows[section] = this.newRows[section].filter(r => r.id !== id);
    },

pendingRemoval: [],
 
    isRemoving(key) {
        return this.pendingRemoval.includes(key);
    },
 
    toggleRemoval(key) {
        this.pendingRemoval = this.isRemoving(key)
            ? this.pendingRemoval.filter(k => k !== key)
            : [...this.pendingRemoval, key];
 
        this.dirty = true;
    },
 
    removalCount(prefix) {
        return this.pendingRemoval.filter(k => k.startsWith(prefix)).length;
    },
 
    restoreRemovals(prefix) {
        this.pendingRemoval = this.pendingRemoval.filter(k => !k.startsWith(prefix));
    },
 
    async saveAll() {
        this.saving = true;
 
        try {
            const result = await window.submitInfoSheetForms(this.$root);
 
            // Clear the guard before anything navigates, or beforeunload prompts
            // on a save the user just confirmed.
            this.dirty = false;
            this.$store.navigation.hasUnsavedChanges = false;
 
            // New rows exist only in the database; removed rows still exist in
            // this DOM. Either way the page is out of date, so reload.
            if (result?.created > 0 || result?.removed > 0) {
                sessionStorage.setItem('infoSheetSaved', '1');
                window.location.reload();
                return;
            }
 
            // Edits only: no reload, keeps scroll position.
            this.editing = false;
            this.saving = false;
            this.newRows = { team: [], inc: [], ld: [], ref: [] };
            this.pendingRemoval = [];
 
            Alpine.store('toast').success(
                'Information Sheet Saved',
                'Your changes have been saved successfully.'
            );
 
        } catch (e) {
            this.saving = false;
 
            console.error('Info sheet save failed:', e);
 
            Alpine.store('toast').error(
                'Save Failed',
                e?.message || 'Something went wrong while saving. Please try again.'
            );
        }
    }
}"
        @click.capture="
        if (!editing && $event.target.matches('input, textarea, select')) {
            lastClickedInput = $event.target.name;
 
            $nextTick(() => {
                $refs.editButton?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            });
        }
    "

        x-init="
        $watch('dirty', value => {
            $store.navigation.hasUnsavedChanges = value;
        });

        $watch('editing', value => {
            if (!value) newRows = { team: [], inc: [], ld: [], ref: [] };
        });

        window.addEventListener('beforeunload', (e) => {
            if ($store.navigation.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
">

        {{-- Startup Header Bar --}}
        <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white px-6 py-3 flex items-center gap-2">
            <img src="{{ asset('images/icons/3person.svg') }}" alt=""
                class="h-5 w-5 flex-shrink-0 brightness-0 invert">
            <h2 class="font-bold text-sm">{{ $startup->company_name }}</h2>
        </div>

        <div class="p-6">
            <div class="flex justify-center mb-3">
                <span class="border border-rose-800 text-rose-800 text-xs italic font-medium px-4 py-1 rounded-md">PUP-TBIDO FORM No.001</span>
            </div>
            <h1 class="text-center font-bold text-xl text-blue-950 mb-4">STARTUP INFORMATION SHEET</h1>

            {{-- Instruction box --}}
            <div class="flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 mb-6 text-xs text-[#11386A]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="7" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <ul class="list-disc pl-4 space-y-0.5 italic marker:text-[#11386A]">
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
                            :readonly=\"!editing\" placeholder=\"SAMPLE\"
                            class='flex-1 border rounded px-3 py-1.5 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300'
                            @click=\"if(!editing){ lastClickedInput=\$el.name }\" @input=\"dirty=true\">
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
                                            :readonly="!editing" placeholder="SAMPLE"
                                            class="flex-1 border rounded px-3 py-1.5 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300"
                                            @click="if(!editing){ lastClickedInput = $el.name }"
                                            @input="dirty = true">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-32 flex-shrink-0">&bull; If Dual Citizenship</span>
                                        <input type="text" name="citizenship_dual" value="{{ e(old('citizenship_dual', $sheet?->citizenship_dual)) }}" form="info-sheet-form"
                                            :readonly="!editing" placeholder="SAMPLE"
                                            class="flex-1 border rounded px-3 py-1.5 text-sm disabled:bg-gray-50 disabled:text-gray-500 placeholder:text-gray-300"
                                            @click="if(!editing){ lastClickedInput = $el.name }"
                                            @input="dirty = true">
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
                                <td class="border p-1"><input type="text" name="{{ $key }}_school" value="{{ old("{$key}_school", $sheet?->{"{$key}_school"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_degree_course" value="{{ old("{$key}_degree_course", $sheet?->{"{$key}_degree_course"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_highest_level_unit" value="{{ old("{$key}_highest_level_unit", $sheet?->{"{$key}_highest_level_unit"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_year_graduated" value="{{ old("{$key}_year_graduated", $sheet?->{"{$key}_year_graduated"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="SAMPLE" class="w-full border-0 px-2 py-1.5 text-sm disabled:bg-transparent disabled:text-gray-500 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <p class="text-xs font-semibold text-gray-700 mt-4 mb-1">23. SCHOLARSHIP / ACADEMIC HONORS RECEIVED</p>
                    <textarea name="scholarships_academic_honors" rows="4" form="info-sheet-form" :readonly="!editing" placeholder="SAMPLE"
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
            NOTE ON NEW COLUMNS: address, date_of_birth, citizenship, sex and civil_status
            are wired up and submitting, but the team_members table doesn't have them yet.
            The migration is at the bottom of 5-team-member-backend.php.
 
            NOTE ON ADD / DELETE: this section has no store or destroy route yet, only
            update-details, so both are wrapped in Route::has() guards.
        --}}
                    @php
                    // Single source of truth for column widths, so the header row and every
                    // data row line up exactly no matter how many team members there are.
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

                    $canAddTeam = Route::has('startup.team-members.store');
                    $canDeleteTeam = Route::has('startup.team-members.destroy');

                    $teamCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 text-sm focus:outline-none focus:bg-blue-50 readonly:bg-transparent readonly:text-gray-500';
                    @endphp
                    <div class="overflow-x-auto">
                        <div class="w-max min-w-full border border-gray-200 rounded-md overflow-hidden divide-y divide-gray-200 bg-white">

                            {{-- Header --}}
                            <div class="flex bg-gray-50/70 text-[11px] font-semibold text-gray-800 uppercase tracking-wide">
                                @foreach ($teamCols as $col)
                                <div class="px-3 py-3 flex-shrink-0 leading-tight {{ $loop->last ? '' : 'border-r border-gray-200' }} {{ $col['w'] }}">{{ $col['label'] }}</div>
                                @endforeach
                                {{-- gutter, always present so columns don't shift between view and edit mode --}}
                                <div class="w-10 flex-shrink-0"></div>
                            </div>

                            {{-- Rows --}}
                            @forelse ($startup->teamMembers as $member)
                            @php $rowKey = 'team-' . $member->getKey(); @endphp
                            <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">
                                <form method="POST" action="{{ route('startup.team-members.update-details', $member) }}"
                                    class="js-subform flex items-stretch text-sm"
                                    :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                    @csrf @method('PATCH')
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[0]['w'] }}">
                                        <input type="text" name="full_name" value="{{ $member->full_name }}" placeholder="Name" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[1]['w'] }}">
                                        <input type="text" name="designation" value="{{ $member->designation }}" placeholder="Designation" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[2]['w'] }}">
                                        <input type="text" name="phone" value="{{ $member->phone }}" placeholder="Phone" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[3]['w'] }}">
                                        <input type="text" name="address" value="{{ $member->address ?? '' }}" placeholder="Address" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[4]['w'] }}">
                                        <input type="date" name="date_of_birth" value="{{ $member->date_of_birth ?? '' }}" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[5]['w'] }}">
                                        <input type="email" name="email" value="{{ $member->email }}" placeholder="Email" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[6]['w'] }}">
                                        <input type="text" name="citizenship" value="{{ $member->citizenship ?? '' }}" placeholder="Citizenship" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[7]['w'] }}">
                                        <input type="text" name="sex" value="{{ $member->sex ?? '' }}" placeholder="Sex" :readonly="!editing" class="{{ $teamCell }} text-center" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 {{ $teamCols[8]['w'] }}">
                                        <input type="text" name="civil_status" value="{{ $member->civil_status ?? '' }}" placeholder="Civil Status" :readonly="!editing" class="{{ $teamCell }}" @input="dirty = true">
                                    </div>
                                </form>

                                @if ($canDeleteTeam)
                                {{-- Deferred DELETE: hidden, no submit button, and only joins the save
                         queue once :class adds js-subform. --}}
                                <form method="POST" action="{{ route('startup.team-members.destroy', $member) }}"
                                    class="js-deleteform hidden"
                                    :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif

                                <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                    @if ($canDeleteTeam)
                                    <button type="button" x-show="editing" x-cloak
                                        @click="toggleRemoval('{{ $rowKey }}')"
                                        title="Remove entry"
                                        aria-label="Remove entry"
                                        class="text-red-600 hover:text-red-800 text-base leading-none">
                                        &times;
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 px-3 py-3">None listed yet.</p>
                            @endforelse

                            {{-- Add new --}}
                            @if ($canAddTeam)
                            <template x-for="row in newRows.team" :key="row.id">
                                <div class="flex items-stretch" x-show="editing">
                                    <form method="POST" action="{{ route('startup.team-members.store') }}"
                                        class="js-subform js-addform flex items-stretch text-sm">
                                        @csrf
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[0]['w'] }}">
                                            <input type="text" name="full_name" placeholder="Name" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[1]['w'] }}">
                                            <input type="text" name="designation" placeholder="Designation" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[2]['w'] }}">
                                            <input type="text" name="phone" placeholder="Phone" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[3]['w'] }}">
                                            <input type="text" name="address" placeholder="Address" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[4]['w'] }}">
                                            <input type="date" name="date_of_birth" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[5]['w'] }}">
                                            <input type="email" name="email" placeholder="Email" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[6]['w'] }}">
                                            <input type="text" name="citizenship" placeholder="Citizenship" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 border-r border-gray-200 {{ $teamCols[7]['w'] }}">
                                            <input type="text" name="sex" placeholder="Sex" class="{{ $teamCell }} text-center" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 {{ $teamCols[8]['w'] }}">
                                            <input type="text" name="civil_status" placeholder="Civil Status" class="{{ $teamCell }}" @input="dirty = true">
                                        </div>
                                    </form>

                                    {{-- Pareho ng gutter ng saved rows para hindi mag-shift ang columns --}}
                                    <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                        <button type="button"
                                            @click="discardRow('team', row.id)"
                                            title="Discard entry"
                                            aria-label="Discard entry"
                                            class="text-red-600 hover:text-red-800 text-base leading-none">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </template>
                            @endif
                        </div>
                    </div>

                    {{-- Footer actions, outside the scroll box so they stay visible --}}
                    <div class="mt-2 flex items-center gap-4" x-show="editing" x-cloak>
                        @if ($canAddTeam)
                        <button type="button" @click="addRow('team')"
                            class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                            + Add Entry
                        </button>
                        @endif

                        @if ($canDeleteTeam)
                        <span x-show="removalCount('team-') > 0" x-cloak class="text-xs text-gray-500">
                            <span x-text="removalCount('team-')"></span> marked for removal on save.
                            <button type="button" @click="restoreRemovals('team-')" class="underline hover:text-gray-700">Restore</button>
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-lg overflow-hidden">
                <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2">
                    III. INCUBATION INVOLVEMENT IN GOVERNMENT / NON-GOVERNMENT / PRIVATE / TECH ORGANIZATIONS
                </h3>
                <div class="border border-t-0 rounded-b-lg p-4">
                    <p class="text-xs font-semibold text-gray-700 mb-2">25.</p>
                    @php
                    $incubationCols = [
                    'w-[340px]',
                    'w-[120px]',
                    'w-[120px]',
                    'w-[90px]',
                    'w-[300px]',
                    ];

                    $incCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 text-sm focus:outline-none focus:bg-blue-50 readonly:bg-transparent readonly:text-gray-500';
                    @endphp
                    <div class="overflow-x-auto">
                        <div class="w-max min-w-full border border-gray-200 rounded-md overflow-hidden divide-y divide-gray-200 bg-white">

                            {{-- Header --}}
                            <div class="flex bg-gray-50/70 text-left text-[11px] uppercase tracking-wide font-semibold text-gray-800">
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 {{ $incubationCols[0] }}">
                                    Name &amp; Address of Organization
                                    <span class="normal-case font-normal text-gray-400">(write in full)</span>
                                </div>
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 {{ $incubationCols[1] }}">From</div>
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 {{ $incubationCols[2] }}">To</div>
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 {{ $incubationCols[3] }}">Hours</div>
                                <div class="px-3 py-3 flex-shrink-0 {{ $incubationCols[4] }}">Incubation Program / Focus</div>
                                <div class="w-10 flex-shrink-0"></div>
                            </div>

                            {{-- Saved rows --}}
                            @forelse ($sheet?->incubationInvolvements ?? [] as $item)
                            @php $rowKey = 'inc-' . $item->id; @endphp {{-- ← NEW (1 of 4): the row's queue key --}}

                            {{-- ← NEW (2 of 4): x-show makes the row vanish the moment × is clicked --}}
                            <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">

                                {{-- ← NEW (3 of 4): js-skip keeps Save from PATCHing a row it's about to delete --}}
                                <form method="POST" action="{{ route('startup.incubation.update', $item) }}"
                                    class="js-subform flex"
                                    :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                    @csrf
                                    @method('PATCH')
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[0] }}">
                                        <input type="text" name="organization_name_address" value="{{ $item->organization_name_address }}"
                                            placeholder="Organization Name & Address" :readonly="!editing" class="{{ $incCell }}" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[1] }}">
                                        <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}"
                                            :readonly="!editing" class="{{ $incCell }}" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[2] }}">
                                        <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}"
                                            :readonly="!editing" class="{{ $incCell }}" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[3] }}">
                                        <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}"
                                            placeholder="Hours" :readonly="!editing" class="{{ $incCell }} text-center" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 {{ $incubationCols[4] }}">
                                        <input type="text" name="incubation_program_focus" value="{{ $item->incubation_program_focus }}"
                                            placeholder="Program/Focus" :readonly="!editing" class="{{ $incCell }}" @input="dirty = true">
                                    </div>
                                </form>

                                {{--
                        ← NEW (4 of 4): the deferred DELETE.
 
                        It has no submit button and stays hidden. It only gains js-subform
                        when the row is marked, and js-subform is the only thing
                        submitInfoSheetForms looks for — so this fires on Save and never
                        before. It sits inside the vanished row on purpose: display:none
                        doesn't hide it from querySelectorAll or FormData.
                    --}}
                                <form method="POST" action="{{ route('startup.incubation.destroy', $item) }}"
                                    class="js-deleteform hidden"
                                    :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                    @csrf
                                    @method('DELETE')
                                </form>

                                <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                    <button type="button" x-show="editing" x-cloak
                                        @click="toggleRemoval('{{ $rowKey }}')"
                                        title="Remove entry"
                                        aria-label="Remove entry"
                                        class="text-red-600 hover:text-red-800 text-base leading-none">
                                        &times;
                                    </button>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 px-3 py-3">None listed yet.</p>
                            @endforelse

                            {{-- Add new. No submit button: js-addform means Save picks it up, and the
                     existing isBlank() guard skips it when nothing was typed. --}}
                            <template x-for="row in newRows.inc" :key="row.id">
                                <div class="flex items-stretch" x-show="editing">
                                    <form method="POST" action="{{ route('startup.incubation.store') }}"
                                        class="js-subform js-addform flex">
                                        @csrf
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[0] }}">
                                            <input type="text" name="organization_name_address" placeholder="Organization Name & Address" class="{{ $incCell }}" @input="dirty = true">
                                        </div>
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[1] }}">
                                            <input type="date" name="date_from" class="{{ $incCell }}" @input="dirty = true">
                                        </div>
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[2] }}">
                                            <input type="date" name="date_to" class="{{ $incCell }}" @input="dirty = true">
                                        </div>
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $incubationCols[3] }}">
                                            <input type="text" name="number_of_hours" placeholder="Hours" class="{{ $incCell }} text-center" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 {{ $incubationCols[4] }}">
                                            <input type="text" name="incubation_program_focus" placeholder="Program/Focus" class="{{ $incCell }}" @input="dirty = true">
                                        </div>
                                    </form>
                                    <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                        <button type="button" @click="discardRow('inc', row.id)" title="Discard entry" aria-label="Discard entry"
                                            class="text-red-600 hover:text-red-800 text-base leading-none">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    {{-- Footer actions, outside the scroll box so they stay visible --}}
                    <div class="mt-2 flex items-center gap-4" x-show="editing" x-cloak>
                        <button type="button" @click="addRow('inc')"
                            class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                            + Add Entry
                        </button>

                        {{-- Safety net: the row is gone from the table, so this is the only way
                             back from a misclick short of cancelling the whole page. --}}
                        <span x-show="removalCount('inc-') > 0" x-cloak class="text-xs text-gray-500">
                            <span x-text="removalCount('inc-')"></span> marked for removal on save.
                            <button type="button" @click="restoreRemovals('inc-')" class="underline hover:text-gray-700">Restore</button>
                        </span>
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
                    {{-- Widths deliberately match Section III, so the two tables read as one grid. --}}
                    @php
                    $ldCols = [
                    'w-[340px]',
                    'w-[120px]',
                    'w-[120px]',
                    'w-[90px]',
                    'w-[300px]',
                    ];

                    $ldCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 text-sm focus:outline-none focus:bg-blue-50 readonly:bg-transparent readonly:text-gray-500';
                    @endphp
                    <div class="overflow-x-auto">
                        <div class="w-max min-w-full border border-gray-200 rounded-md overflow-hidden divide-y divide-gray-200 bg-white">

                            {{-- Header --}}
                            <div class="flex bg-gray-50/70 text-[11px] font-semibold text-gray-800 uppercase tracking-wide">
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 leading-tight {{ $ldCols[0] }}">
                                    Title of L&amp;D Intervention
                                    <span class="normal-case font-normal text-gray-400">(write in full)</span>
                                </div>
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 {{ $ldCols[1] }}">From</div>
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 {{ $ldCols[2] }}">To</div>
                                <div class="px-3 py-3 border-r border-gray-200 flex-shrink-0 {{ $ldCols[3] }}">Hours</div>
                                <div class="px-3 py-3 flex-shrink-0 {{ $ldCols[4] }}">Conducted / Sponsored By</div>
                                <div class="w-10 flex-shrink-0"></div>
                            </div>

                            {{-- Saved rows --}}
                            @forelse ($sheet?->ldInterventions ?? [] as $item)
                            @php $rowKey = 'ld-' . $item->id; @endphp
                            <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">
                                <form method="POST" action="{{ route('startup.ld.update', $item) }}"
                                    class="js-subform flex"
                                    :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                    @csrf
                                    @method('PATCH')
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[0] }}">
                                        <input type="text" name="title" value="{{ $item->title }}"
                                            placeholder="Title" :readonly="!editing" class="{{ $ldCell }}" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[1] }}">
                                        <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}"
                                            :readonly="!editing" class="{{ $ldCell }}" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[2] }}">
                                        <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}"
                                            :readonly="!editing" class="{{ $ldCell }}" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[3] }}">
                                        <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}"
                                            placeholder="Hours" :readonly="!editing" class="{{ $ldCell }} text-center" @input="dirty = true">
                                    </div>
                                    <div class="flex-shrink-0 {{ $ldCols[4] }}">
                                        <input type="text" name="conducted_sponsored_by" value="{{ $item->conducted_sponsored_by }}"
                                            placeholder="Conducted/Sponsored By" :readonly="!editing" class="{{ $ldCell }}" @input="dirty = true">
                                    </div>
                                </form>

                                {{-- Deferred DELETE: hidden, no submit button, only joins the save
                         queue once :class adds js-subform. --}}
                                <form method="POST" action="{{ route('startup.ld.destroy', $item) }}"
                                    class="js-deleteform hidden"
                                    :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                    @csrf
                                    @method('DELETE')
                                </form>

                                <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                    <button type="button" x-show="editing" x-cloak
                                        @click="toggleRemoval('{{ $rowKey }}')"
                                        title="Remove entry"
                                        aria-label="Remove entry"
                                        class="text-red-600 hover:text-red-800 text-base leading-none">
                                        &times;
                                    </button>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 px-3 py-3">None listed yet.</p>
                            @endforelse

                            {{-- Add new --}}
                            <template x-for="row in newRows.ld" :key="row.id">
                                <div class="flex items-stretch">
                                    <form method="POST" action="{{ route('startup.ld.store') }}"
                                        class="js-subform js-addform flex">
                                        @csrf
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[0] }}">
                                            <input type="text" name="title" placeholder="Title" class="{{ $ldCell }}" @input="dirty = true">
                                        </div>
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[1] }}">
                                            <input type="date" name="date_from" class="{{ $ldCell }}" @input="dirty = true">
                                        </div>
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[2] }}">
                                            <input type="date" name="date_to" class="{{ $ldCell }}" @input="dirty = true">
                                        </div>
                                        <div class="border-r border-gray-200 flex-shrink-0 {{ $ldCols[3] }}">
                                            <input type="text" name="number_of_hours" placeholder="Hours" class="{{ $ldCell }} text-center" @input="dirty = true">
                                        </div>
                                        <div class="flex-shrink-0 {{ $ldCols[4] }}">
                                            <input type="text" name="conducted_sponsored_by" placeholder="Conducted/Sponsored By" class="{{ $ldCell }}" @input="dirty = true">
                                        </div>
                                    </form>
                                    <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                        <button type="button" @click="discardRow('ld', row.id)" title="Discard entry" aria-label="Discard entry"
                                            class="text-red-600 hover:text-red-800 text-base leading-none">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Footer actions, outside the scroll box so they stay visible --}}
                    <div class="mt-2 flex items-center gap-4" x-show="editing" x-cloak>
                        <button type="button" @click="addRow('ld')"
                            class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                            + Add Entry
                        </button>

                        <span x-show="removalCount('ld-') > 0" x-cloak class="text-xs text-gray-500">
                            <span x-text="removalCount('ld-')"></span> marked for removal on save.
                            <button type="button" @click="restoreRemovals('ld-')" class="underline hover:text-gray-700">Restore</button>
                        </span>
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
                            <label class="w-48 flex-shrink-0 text-gray-800">
                                <span class="font-semibold">27.</span> STARTUP NAME:
                            </label>

                            <div class="flex-1 border rounded px-3 py-1.5 text-sm bg-gray-50 text-gray-500">
                                {{ $startup->company_name }}
                            </div>
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

                {{--
    SECTIONS 31 & 34 AS TABLES — FRONTEND ONLY

    No migration, no routes, no controller. Both still save into the columns they already
    use (non_academic_distinctions and membership_associations) via the existing
    #info-sheet-form PATCH.

    LAYOUT
    Side by side in the same md:grid-cols-2 the textareas used, so Section V keeps its
    two-column rhythm. The number sits above the frame ("31." / "34.") and the full label
    is the table's header row, matching the printed form.

    HOW IT WORKS
    Those columns already hold newline-separated text, so a "row" is just a line. PHP splits
    the stored text on load, Alpine owns the array from then on, and a hidden field joins it
    back with \n. Existing data shows up as rows immediately — nothing to backfill, and
    anything else reading these columns keeps working.

    SCOPE
    Each block has its own x-data but sits INSIDE the page's x-data, so `editing` and `dirty`
    are inherited. You do NOT need to touch newRows, addRow(), toggleRemoval(), or
    submitInfoSheetForms.

    WHERE IT GOES
    Replaces the <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4"> block in Section V
    that currently holds the two textareas — this file includes that wrapper.
--}}

                @php
                // One line = one row. Blank lines are dropped so trailing newlines don't
                // render as empty rows.
                $splitRows = fn ($text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->map(fn ($value, $i) => ['id' => $i + 1, 'text' => $value]);

                $distRows = $splitRows(old('non_academic_distinctions', $sheet?->non_academic_distinctions));
                $memRows = $splitRows(old('membership_associations', $sheet?->membership_associations));

                // Shared so the two tables stay identical without copy-paste drift.
                $listCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 text-sm
                focus:outline-none focus:bg-blue-50
                read-only:bg-transparent read-only:text-gray-500 placeholder:text-gray-300';
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                    {{-- 31. Non-Academic Distinctions --}}
                    <div
                        x-data="{
            rows: {{ Illuminate\Support\Js::from($distRows) }},
            original: {{ Illuminate\Support\Js::from($distRows) }},
            nextId: {{ $distRows->count() + 1 }},

            // What actually gets submitted. Blank rows are dropped here rather than on
            // add, so a half-typed row doesn't vanish under the cursor.
            get packed() {
                return this.rows.map(r => r.text.trim()).filter(Boolean).join('\n');
            },

            add() {
                this.rows.push({ id: this.nextId++, text: '' });
                dirty = true;
            },

            remove(id) {
                this.rows = this.rows.filter(r => r.id !== id);
                dirty = true;
            },

            reset() {
                this.rows = JSON.parse(JSON.stringify(this.original));
            },
        }"
                        x-init="$watch('editing', value => { if (!value) reset() })">

                        <p class="text-xs font-semibold text-gray-700 mb-1">31.</p>

                        {{-- The real field. Hidden, but still part of the main form via form="info-sheet-form".
             x-effect writes .value directly — :value on a <textarea> only sets the attribute,
             which the browser ignores once the element exists, so the form would submit empty. --}}
                        <textarea name="non_academic_distinctions" form="info-sheet-form" class="hidden"
                            x-ref="packedField" x-effect="$refs.packedField.value = packed"></textarea>

                        <div class="border border-gray-200 rounded-md overflow-hidden divide-y divide-gray-200 bg-white">

                            {{-- Header. flex-1 label + fixed w-10 gutter, the same shape every row uses,
                 so nothing shifts between view and edit mode. --}}
                            <div class="flex bg-gray-50/70 text-[11px] font-semibold uppercase tracking-wide text-gray-800">
                                <div class="flex-1 px-3 py-3 leading-tight">NON-ACADEMIC DISTINCTIONS / RECOGNITION / ELIGIBILITIES</div>
                                <div class="w-10 flex-shrink-0"></div>
                            </div>

                            {{-- Rows --}}
                            <template x-for="row in rows" :key="row.id">
                                <div class="flex items-stretch">
                                    <div class="flex-1">
                                        <input type="text" x-model="row.text"
                                            placeholder="SAMPLE"
                                            :readonly="!editing" @input="dirty = true"
                                            class="{{ $listCell }}">
                                    </div>
                                    <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                        {{-- Immediate, not deferred: there's no DELETE to queue, the row only
                             exists in this array until Save packs it. --}}
                                        <button type="button" x-show="editing" x-cloak
                                            @click="remove(row.id)"
                                            title="Remove entry" aria-label="Remove entry"
                                            class="text-red-600 hover:text-red-800 text-base leading-none">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <p class="text-sm text-gray-400 px-3 py-3" x-show="rows.length === 0" x-cloak>None listed yet.</p>

                            {{-- Footer action inside the frame, same as References --}}
                            <div class="px-3 py-2" x-show="editing" x-cloak>
                                <button type="button" @click="add()"
                                    class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                                    + Add Entry
                                </button>
                            </div>
                        </div>
                        @error('non_academic_distinctions') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>


                    {{-- 34. Membership in Association / Organization --}}
                    <div
                        x-data="{
            rows: {{ Illuminate\Support\Js::from($memRows) }},
            original: {{ Illuminate\Support\Js::from($memRows) }},
            nextId: {{ $memRows->count() + 1 }},

            get packed() {
                return this.rows.map(r => r.text.trim()).filter(Boolean).join('\n');
            },

            add() {
                this.rows.push({ id: this.nextId++, text: '' });
                dirty = true;
            },

            remove(id) {
                this.rows = this.rows.filter(r => r.id !== id);
                dirty = true;
            },

            reset() {
                this.rows = JSON.parse(JSON.stringify(this.original));
            },
        }"
                        x-init="$watch('editing', value => { if (!value) reset() })">

                        <p class="text-xs font-semibold text-gray-700 mb-1">34.</p>

                        <textarea name="membership_associations" form="info-sheet-form" class="hidden"
                            x-ref="packedField" x-effect="$refs.packedField.value = packed"></textarea>

                        <div class="border border-gray-200 rounded-md overflow-hidden divide-y divide-gray-200 bg-white">

                            {{-- Header --}}
                            <div class="flex bg-gray-50/70 text-[11px] font-semibold uppercase tracking-wide text-gray-800">
                                <div class="flex-1 px-3 py-3 leading-tight">MEMBERSHIP IN ASSOCIATION/ORGANIZATION</div>
                                <div class="w-10 flex-shrink-0"></div>
                            </div>

                            {{-- Rows --}}
                            <template x-for="row in rows" :key="row.id">
                                <div class="flex items-stretch">
                                    <div class="flex-1">
                                        <input type="text" x-model="row.text"
                                            placeholder="SAMPLE"
                                            :readonly="!editing" @input="dirty = true"
                                            class="{{ $listCell }}">
                                    </div>
                                    <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                        <button type="button" x-show="editing" x-cloak
                                            @click="remove(row.id)"
                                            title="Remove entry" aria-label="Remove entry"
                                            class="text-red-600 hover:text-red-800 text-base leading-none">
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <p class="text-sm text-gray-400 px-3 py-3" x-show="rows.length === 0" x-cloak>None listed yet.</p>

                            <div class="px-3 py-2" x-show="editing" x-cloak>
                                <button type="button" @click="add()"
                                    class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                                    + Add Entry
                                </button>
                            </div>
                        </div>
                        @error('membership_associations') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>


                {{-- 35. References --}}
                <div class="mt-6">
                    <p class="text-xs font-semibold text-gray-700 mb-2">35. REFERENCES</p>
                    {{--
        The four columns stay fluid (grid-cols-4) since this table fits without
        scrolling. What makes them line up is that the header and every row share
        the same shape: a flex-1 grid next to a fixed w-10 gutter. The gutter is
        always in the DOM — only the button inside it toggles — so nothing shifts
        between view and edit mode.
    --}}
                    <div class="border border-gray-200 rounded-md overflow-hidden divide-y divide-gray-200 bg-white">

                        {{-- Header --}}
                        <div class="flex bg-gray-50/70 text-[11px] font-semibold uppercase tracking-wide text-gray-800">
                            <div class="grid grid-cols-4 flex-1">
                                <div class="px-3 py-3 border-r border-gray-200">NAME</div>
                                <div class="px-3 py-3 border-r border-gray-200">CONTACT</div>
                                <div class="px-3 py-3 border-r border-gray-200">EMAIL ADDRESS</div>
                                <div class="px-3 py-3">ADDRESS</div>
                            </div>
                            <div class="w-10 flex-shrink-0"></div>
                        </div>

                        {{-- Saved rows --}}
                        @forelse ($sheet?->references ?? [] as $reference)
                        @php $rowKey = 'ref-' . $reference->id; @endphp
                        <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">
                            <form method="POST" action="{{ route('startup.references.update', $reference) }}"
                                class="js-subform grid grid-cols-4 flex-1 text-sm"
                                :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                @csrf
                                @method('PATCH')
                                <div class="border-r border-gray-200">
                                    <input type="text" name="name" value="{{ $reference->name }}"
                                        placeholder="Name" :readonly="!editing"
                                        class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50 readonly:bg-transparent readonly:text-gray-500"
                                        @input="dirty = true">
                                </div>
                                <div class="border-r border-gray-200">
                                    <input type="text" name="contact" value="{{ $reference->contact }}"
                                        placeholder="Contact" :readonly="!editing"
                                        class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50 readonly:bg-transparent readonly:text-gray-500"
                                        @input="dirty = true">
                                </div>
                                <div class="border-r border-gray-200">
                                    <input type="email" name="email" value="{{ $reference->email }}"
                                        placeholder="Email" :readonly="!editing"
                                        class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50 readonly:bg-transparent readonly:text-gray-500"
                                        @input="dirty = true">
                                </div>
                                <div>
                                    <input type="text" name="address" value="{{ $reference->address }}"
                                        placeholder="Address" :readonly="!editing"
                                        class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50 readonly:bg-transparent readonly:text-gray-500"
                                        @input="dirty = true">
                                </div>
                            </form>

                            {{-- Deferred DELETE: hidden, no submit button, only joins the save
                 queue once :class adds js-subform. --}}
                            <form method="POST" action="{{ route('startup.references.destroy', $reference) }}"
                                class="js-deleteform hidden"
                                :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                @csrf
                                @method('DELETE')
                            </form>

                            <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                <button type="button" x-show="editing" x-cloak
                                    @click="toggleRemoval('{{ $rowKey }}')"
                                    title="Remove entry"
                                    aria-label="Remove entry"
                                    class="text-red-600 hover:text-red-800 text-base leading-none">
                                    &times;
                                </button>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 px-3 py-3">None listed yet.</p>
                        @endforelse

                        {{-- Add new. js-subform is on the FORM now, not the inner div, so Save
             actually picks it up. No + button — same flow as the other sections. --}}
                        <template x-for="row in newRows.ref" :key="row.id">
                            <div class="flex items-stretch">
                                <form method="POST" action="{{ route('startup.references.store') }}"
                                    class="js-subform js-addform grid grid-cols-4 flex-1 text-sm">
                                    @csrf
                                    <div class="border-r border-gray-200">
                                        <input type="text" name="name" placeholder="Name"
                                            class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200">
                                        <input type="text" name="contact" placeholder="Contact"
                                            class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                    </div>
                                    <div class="border-r border-gray-200">
                                        <input type="email" name="email" placeholder="Email"
                                            class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                    </div>
                                    <div>
                                        <input type="text" name="address" placeholder="Address"
                                            class="w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                    </div>
                                </form>
                                <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                    <button type="button" @click="discardRow('ref', row.id)" title="Discard entry" aria-label="Discard entry"
                                        class="text-red-600 hover:text-red-800 text-base leading-none">&times;</button>
                                </div>
                            </div>
                        </template>

                        {{-- Footer actions. Unlike II/III/IV these live inside the frame, since
             this table doesn't scroll horizontally. --}}
                        <div class="px-3 py-2 flex items-center gap-4" x-show="editing" x-cloak>
                            <button type="button" @click="addRow('ref')"
                                class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                                + Add Entry
                            </button>

                            <span x-show="removalCount('ref-') > 0" x-cloak class="text-xs text-gray-500">
                                <span x-text="removalCount('ref-')"></span> marked for removal on save.
                                <button type="button" @click="restoreRemovals('ref-')" class="underline hover:text-gray-700">Restore</button>
                            </span>
                        </div>
                    </div>
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- 36. Declaration & Endorsement. Founder side only shows the
             declaration + date accomplished — the "For TBIDO Only" half
             (Portfolio Manager/Cohort/Endorsed by/Director) is admin-only,
             see admin/information-sheets/show.blade.php. Both signature
             boxes are intentionally NOT form fields: per direct instruction
             this is a wet-ink signature — the form is meant to be printed
             and physically signed, not signed digitally in the app. --}}
                <div class="mt-6">
                    <p class="text-xs font-semibold text-gray-700 mb-2">36. DECLARATION</p>
                    <div class="border border-gray-200 rounded-md p-4 bg-white text-xs text-gray-700 leading-relaxed">
                        <p class="mb-4">
                            I declare that I have personally accomplished this Startup Information Sheet which is a true, correct and complete
                            statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines.
                            I authorize the agency head/authorized representative to verify/validate the contents stated herein. I agree that
                            any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal
                            case/s against me.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="h-16 border border-dashed border-gray-300 rounded flex items-center justify-center text-gray-400 italic mb-1">
                                    Sign inside the box (print &amp; sign)
                                </div>
                                <p class="text-center text-[11px] text-gray-500">Founder's Signature</p>
                            </div>
                            <div>
                                {!! $field('date_accomplished', 'DATE ACCOMPLISHED', null, 'date') !!}
                            </div>
                        </div>
                    </div>
                </div>



                <div class="flex gap-3">

                    <template x-if="isLocked">
                        <div class="flex-1 text-center bg-gray-100 text-gray-500 rounded-lg py-2.5 text-sm font-medium">
                            {{ $lockReason }}
                        </div>
                    </template>

                    <template x-if="!isLocked && !editing">
                        <div class="flex-1 rounded-lg p-[1.5px] bg-gradient-to-r from-[#6D0D23] to-[#11386A] mt-10">
                            <button
                                type="button"
                                x-ref="editButton"
                                @click="
                    editing = true;
                    $nextTick(() => {
                        if (lastClickedInput) {
                            const el = document.querySelector(`[name='${lastClickedInput}']`);
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                el.focus();
                            }
                        }
                    });
                "
                                class="w-full rounded-[7px] bg-white py-2.5 text-sm font-semibold text-[#11386A]
                       transition-all duration-200 hover:bg-slate-50 hover:shadow-sm">
                                Edit
                            </button>
                        </div>
                    </template>

                    <template x-if="editing && !isLocked">
                        <button
                            type="button"
                            @click="editing = false; dirty = false; pendingRemoval = []"
                            class="flex-1 border border-gray-300 bg-white text-gray-700 rounded-lg py-2.5 text-sm font-semibold
                   hover:bg-gray-50 transition">
                            Cancel
                        </button>
                    </template>

                    <button
                        type="button"
                        x-show="editing && !isLocked"
                        x-cloak
                        @click="saveAll()"
                        :disabled="saving"
                        class="flex-1 rounded-lg py-2.5 text-sm font-semibold text-white
               bg-gradient-to-r from-[#6D0D23] to-[#11386A]
               hover:opacity-95 transition disabled:opacity-60">
                        <span x-text="saving ? 'Saving…' : 'Save'"></span>
                    </button>

                </div>
            </div>
        </div>
    </div>


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
                // Row marked for removal: don't PATCH what's about to be deleted.
                if (form.classList.contains('js-skip')) return false;
                // Skip empty "add new entry" rows so we don't create blank records.
                if (form.classList.contains('js-addform') && isBlank(form)) return false;
                return true;
            });

            // Deletes go last, so a failed update can't leave a row already destroyed.
            forms.sort((a, b) => {
                const aDel = a.classList.contains('js-deleteform') ? 1 : 0;
                const bDel = b.classList.contains('js-deleteform') ? 1 : 0;
                return aDel - bDel;
            });

            let created = 0;
            let removed = 0;

            for (const form of forms) {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });

                if (!response.ok) {
                    const error = new Error('Request to ' + form.action + ' failed with status ' + response.status);
                    error.status = response.status;
                    error.action = form.action;

                    // Laravel returns validation errors as JSON on an XHR request.
                    if (response.status === 422) {
                        try {
                            const body = await response.json();
                            error.validation = body.errors || null;
                            error.message = Object.values(body.errors || {}).flat().join(' ') || error.message;
                        } catch (ignored) {}
                    }

                    throw error;
                }

                if (form.classList.contains('js-addform')) created++;
                if (form.classList.contains('js-deleteform')) removed++;
            }

            return {
                created,
                removed
            };
        };


        // Post-reload success toast.
        (function() {
            let shown = false;

            const flushSavedToast = () => {
                if (shown) return;
                if (!sessionStorage.getItem('infoSheetSaved')) return;

                const toast = window.Alpine && window.Alpine.store('toast');
                if (!toast) return; // not ready yet — a later hook will retry

                sessionStorage.removeItem('infoSheetSaved');
                shown = true;

                toast.success(
                    'Information Sheet Saved',
                    'Your changes have been saved successfully.'
                );
            };

            // Whichever of these lands first wins; the rest no-op.
            document.addEventListener('alpine:initialized', flushSavedToast);
            window.addEventListener('load', flushSavedToast);
            setTimeout(flushSavedToast, 300);
        })();
    </script>
</x-layouts.founder>