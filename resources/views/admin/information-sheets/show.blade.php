{{--
    ADMIN VIEW — Startup Information Sheet

    Layout, spacing and column widths are a direct port of the founder view. The editing
    machinery (Alpine state + submitInfoSheetForms) is the same too, so a fix on one side
    is a fix on the other. What's different is only the action row:

        VIEW MODE   [ Back ]  [ Edit ]  [ Approve & Lock ]
        EDIT MODE   [ Cancel ]  [ Save ]
        LOCKED      [ Back ]  + "Approved & locked" banner

    ROUTE NAMES — all of them go through the $url() helper below, which returns null when
    the route doesn't exist yet. A null URL just means that control isn't rendered, so this
    page won't 500 while you're still wiring the backend. Rename them in ONE place: the
    $url() calls. Nothing else hardcodes a route.
--}}
<x-layouts.admin title="Information Sheet">

    @php
    $sheet = $startup->informationSheet;
    $isLocked = $sheet?->approval_status === 'Approved';

    // Returns the URL, or null if that route isn't registered yet.
    $url = function (string $name, ...$params) {
    return Route::has($name) ? route($name, $params) : null;
    };

    $backUrl = request('from') === 'assessment-hub'
        ? route('admin.assessment-hub.index', array_filter([
            'main' => 'information-sheet',
            'tab' => request('tab'),
            'stage' => request('stage'),
        ]))
        : ($url('admin.startups.index') ?? url()->previous());
    $sheetUpdateUrl = $url('admin.information-sheet.update', $startup);
    $approveUrl = $url('admin.information-sheet.approve', $startup);

    $teamStoreUrl = $url('admin.team-members.store', $startup);
    $incStoreUrl = $url('admin.incubation.store', $startup);
    $ldStoreUrl = $url('admin.ld.store', $startup);
    $refStoreUrl = $url('admin.references.store', $startup);
    @endphp

    {{-- Page header. The back arrow lives here as well as in the action row, so the reviewer
         doesn't have to scroll a long form to get out. --}}
    <div class="mb-6 flex items-start gap-3">
        <a href="{{ $backUrl }}"
            class="mt-1 flex-shrink-0 text-gray-400 hover:text-[#11386A] transition"
            title="Back to startups" aria-label="Back to startups">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Information Sheet</h1>
            <p class="text-gray-500 mt-1">Review and edit the details submitted by {{ $startup->company_name }}.</p>
        </div>
    </div>

    <div
        class="bg-white rounded-xl border border-gray-200 max-w-6xl overflow-hidden"
        x-data="{
    editing: false,
    isLocked: {{ $isLocked ? 'true' : 'false' }},
    saving: false,
    dirty: false,
    confirmingApprove: false,
    lastClickedInput: null,

    newRows: { team: [], inc: [], ld: [], ref: [] },
    nextRowId: 1,

    addRow(section) {
        this.newRows[section].push({ id: this.nextRowId++ });
    },

    // Unlike toggleRemoval(): nothing was ever saved, so no DELETE is queued — burado agad.
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
        if (!editing && !isLocked && $event.target.matches('input, textarea, select')) {
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

            {{-- Admin-only: submission status, pushed right so the bar otherwise matches. --}}
            <span class="ml-auto text-[11px] font-semibold uppercase tracking-wide bg-white/15 px-3 py-1 rounded-full">
                {{ $sheet?->approval_status ?? 'Pending' }}
            </span>
        </div>

        <div class="p-6">
            <div class="flex justify-center mb-3">
                <span class="border border-rose-800 text-rose-800 text-xs italic font-medium px-4 py-1 rounded-md">PUP-TBIDO FORM No.001</span>
            </div>
            <h1 class="text-center font-bold text-xl text-blue-950 mb-4">STARTUP INFORMATION SHEET</h1>

            {{-- Instruction box --}}
            <div class="flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 mb-6 text-xs text-gray-700">
                <img src="{{ asset('images/icons/blue-warning.svg') }}" alt=""
                    class="h-6 w-6 flex-shrink-0">
                <ul class="list-disc pl-4 space-y-0.5 italic marker:text-[#11386A]">
                    <li>This is the founder's submitted copy of PUP-TBIDO Form No. 001. Use Edit to correct entries on their behalf.</li>
                    <li>Approving locks the sheet. The founder can no longer edit it and will be told to contact their Coordinator.</li>
                    <li>Date Format (mm/dd/yyyy)</li>
                </ul>
            </div>

            <form id="info-sheet-form" method="POST" action="{{ $sheetUpdateUrl }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- I. FOUNDER'S INFORMATION --}}
                <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2 rounded-t-lg">I. FOUNDER'S INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 mb-6">
                    @php
                    // Renders a numbered, horizontally-aligned field: "N. LABEL: [input]"
                    // NOTE: the variant is read-only:, not readonly:. The founder view uses
                    // disabled:/readonly: prefixes that Tailwind never compiles, which is why
                    // its view-mode fields don't grey out. Fixed here.
                    $field = function ($name, $label, $number = null, $type = 'text') use ($sheet) {
                    $value = old($name, $sheet?->{$name});
                    $numHtml = $number ? "<span class='font-semibold'>{$number}.</span> " : '';
                    return "<div class='flex items-center gap-2 py-1.5 text-sm'>
                        <label class='w-48 flex-shrink-0 text-gray-800'>{$numHtml}".e($label).":</label>
                        <input type=\"{$type}\" name=\"{$name}\" value=\"".e($value)."\" form=\"info-sheet-form\"
                            :readonly=\"!editing\" placeholder=\"—\"
                            class='flex-1 border rounded px-3 py-1.5 text-sm read-only:bg-gray-50 read-only:text-gray-500 placeholder:text-gray-300'
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
                                        <input type="text" name="citizenship_by_birth" value="{{ old('citizenship_by_birth', $sheet?->citizenship_by_birth) }}" form="info-sheet-form"
                                            :readonly="!editing" placeholder="—"
                                            class="flex-1 border rounded px-3 py-1.5 text-sm read-only:bg-gray-50 read-only:text-gray-500 placeholder:text-gray-300"
                                            @click="if(!editing){ lastClickedInput = $el.name }"
                                            @input="dirty = true">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-32 flex-shrink-0">&bull; If Dual Citizenship</span>
                                        <input type="text" name="citizenship_dual" value="{{ old('citizenship_dual', $sheet?->citizenship_dual) }}" form="info-sheet-form"
                                            :readonly="!editing" placeholder="—"
                                            class="flex-1 border rounded px-3 py-1.5 text-sm read-only:bg-gray-50 read-only:text-gray-500 placeholder:text-gray-300"
                                            @click="if(!editing){ lastClickedInput = $el.name }"
                                            @input="dirty = true">
                                    </div>
                                </div>
                            </div>

                            {!! $field('place_of_birth', 'PLACE OF BIRTH', 18) !!}
                            {!! $field('date_of_birth', 'DATE OF BIRTH', 19, 'date') !!}
                            {!! $field('mobile_no', 'MOBILE NO.', 20) !!}
                            {!! $field('founder_email', 'EMAIL ADDRESS', 21) !!}
                        </div>
                    </div>
                </div>

                {{-- 22. Educational Background --}}
                <div class="mb-6">
                    <p class="text-xs font-semibold text-gray-700 mb-1">22. EDUCATIONAL BACKGROUND</p>
                    @php $eduCell = 'w-full border-0 px-2 py-1.5 text-sm read-only:bg-transparent read-only:text-gray-500 placeholder:text-gray-300 focus:outline-none'; @endphp
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
                                <td class="border p-1"><input type="text" name="{{ $key }}_school" value="{{ old("{$key}_school", $sheet?->{"{$key}_school"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="—" class="{{ $eduCell }}" @input="dirty = true"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_degree_course" value="{{ old("{$key}_degree_course", $sheet?->{"{$key}_degree_course"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="—" class="{{ $eduCell }}" @input="dirty = true"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_highest_level_unit" value="{{ old("{$key}_highest_level_unit", $sheet?->{"{$key}_highest_level_unit"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="—" class="{{ $eduCell }}" @input="dirty = true"></td>
                                <td class="border p-1"><input type="text" name="{{ $key }}_year_graduated" value="{{ old("{$key}_year_graduated", $sheet?->{"{$key}_year_graduated"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="—" class="{{ $eduCell }}" @input="dirty = true"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <p class="text-xs font-semibold text-gray-700 mt-4 mb-1">23. SCHOLARSHIP / ACADEMIC HONORS RECEIVED</p>
                    <textarea name="scholarships_academic_honors" rows="4" form="info-sheet-form" :readonly="!editing" placeholder="—"
                        class="w-full border rounded px-3 py-2 text-sm read-only:bg-gray-50 read-only:text-gray-500 placeholder:text-gray-300">{{ old('scholarships_academic_honors', $sheet?->scholarships_academic_honors) }}</textarea>
                </div>
            </form>
            {{-- The main form closes here for layout purposes only; every field below that belongs to it
                 carries form="info-sheet-form" so it still submits together with everything above. --}}

            {{-- II. Core Team Formation --}}
            <div class="mt-8 rounded-lg overflow-hidden">
                <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-sm font-semibold px-4 py-2">II. CORE TEAM FORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4">
                    <p class="text-xs font-semibold text-gray-700 mb-2">24.</p>
                    @php
                    // Single source of truth for column widths, identical to the founder view.
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

                    $teamCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 text-sm focus:outline-none focus:bg-blue-50 read-only:bg-transparent read-only:text-gray-500';
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
                            @php
                            $rowKey = 'team-' . $member->getKey();
                            $rowUpdateUrl = $url('admin.team-members.update-details', $member);
                            $rowDeleteUrl = $url('admin.team-members.destroy', $member);
                            @endphp
                            <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">
                                <form method="POST" action="{{ $rowUpdateUrl }}"
                                    class="{{ $rowUpdateUrl ? 'js-subform' : '' }} flex items-stretch text-sm"
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

                                @if ($rowDeleteUrl)
                                {{-- Deferred DELETE: hidden, no submit button, and only joins the save
                                     queue once :class adds js-subform. --}}
                                <form method="POST" action="{{ $rowDeleteUrl }}"
                                    class="js-deleteform hidden"
                                    :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif

                                <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                    @if ($rowDeleteUrl)
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
                            @if ($teamStoreUrl)
                            <template x-for="row in newRows.team" :key="row.id">
                                <div class="flex items-stretch" x-show="editing">
                                    <form method="POST" action="{{ $teamStoreUrl }}"
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

                                    {{-- Same gutter as saved rows so the columns don't shift --}}
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
                        @if ($teamStoreUrl)
                        <button type="button" @click="addRow('team')"
                            class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                            + Add Entry
                        </button>
                        @endif

                        <span x-show="removalCount('team-') > 0" x-cloak class="text-xs text-gray-500">
                            <span x-text="removalCount('team-')"></span> marked for removal on save.
                            <button type="button" @click="restoreRemovals('team-')" class="underline hover:text-gray-700">Restore</button>
                        </span>
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
                    @php
                    $incubationCols = [
                    'w-[340px]',
                    'w-[120px]',
                    'w-[120px]',
                    'w-[90px]',
                    'w-[300px]',
                    ];

                    $incCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 text-sm focus:outline-none focus:bg-blue-50 read-only:bg-transparent read-only:text-gray-500';
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
                            @php
                            $rowKey = 'inc-' . $item->id;
                            $rowUpdateUrl = $url('admin.incubation.update', $item);
                            $rowDeleteUrl = $url('admin.incubation.destroy', $item);
                            @endphp
                            <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">
                                <form method="POST" action="{{ $rowUpdateUrl }}"
                                    class="{{ $rowUpdateUrl ? 'js-subform' : '' }} flex"
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

                                @if ($rowDeleteUrl)
                                <form method="POST" action="{{ $rowDeleteUrl }}"
                                    class="js-deleteform hidden"
                                    :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif

                                <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                    @if ($rowDeleteUrl)
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
                            @if ($incStoreUrl)
                            <template x-for="row in newRows.inc" :key="row.id">
                                <div class="flex items-stretch" x-show="editing">
                                    <form method="POST" action="{{ $incStoreUrl }}"
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
                            @endif
                        </div>
                    </div>

                    <div class="mt-2 flex items-center gap-4" x-show="editing" x-cloak>
                        @if ($incStoreUrl)
                        <button type="button" @click="addRow('inc')"
                            class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                            + Add Entry
                        </button>
                        @endif

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

                    $ldCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 text-sm focus:outline-none focus:bg-blue-50 read-only:bg-transparent read-only:text-gray-500';
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
                            @php
                            $rowKey = 'ld-' . $item->id;
                            $rowUpdateUrl = $url('admin.ld.update', $item);
                            $rowDeleteUrl = $url('admin.ld.destroy', $item);
                            @endphp
                            <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">
                                <form method="POST" action="{{ $rowUpdateUrl }}"
                                    class="{{ $rowUpdateUrl ? 'js-subform' : '' }} flex"
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

                                @if ($rowDeleteUrl)
                                <form method="POST" action="{{ $rowDeleteUrl }}"
                                    class="js-deleteform hidden"
                                    :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif

                                <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                    @if ($rowDeleteUrl)
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
                            @if ($ldStoreUrl)
                            <template x-for="row in newRows.ld" :key="row.id">
                                <div class="flex items-stretch" x-show="editing">
                                    <form method="POST" action="{{ $ldStoreUrl }}"
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
                            @endif
                        </div>
                    </div>

                    <div class="mt-2 flex items-center gap-4" x-show="editing" x-cloak>
                        @if ($ldStoreUrl)
                        <button type="button" @click="addRow('ld')"
                            class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                            + Add Entry
                        </button>
                        @endif

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
                        <p class="text-xs text-gray-400 mt-1">Comes from the Startup Profile.</p>
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
                    @php $refCell = 'w-full h-full border-0 bg-transparent px-3 py-2.5 focus:outline-none focus:bg-blue-50 read-only:bg-transparent read-only:text-gray-500'; @endphp
                    {{--
                        The four columns stay fluid (grid-cols-4) since this table fits without
                        scrolling. What makes them line up is that the header and every row share
                        the same shape: a flex-1 grid next to a fixed w-10 gutter.
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
                        @php
                        $rowKey = 'ref-' . $reference->id;
                        $rowUpdateUrl = $url('admin.references.update', $reference);
                        $rowDeleteUrl = $url('admin.references.destroy', $reference);
                        @endphp
                        <div class="flex items-stretch" x-show="!isRemoving('{{ $rowKey }}')">
                            <form method="POST" action="{{ $rowUpdateUrl }}"
                                class="{{ $rowUpdateUrl ? 'js-subform' : '' }} grid grid-cols-4 flex-1 text-sm"
                                :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                @csrf
                                @method('PATCH')
                                <div class="border-r border-gray-200">
                                    <input type="text" name="name" value="{{ $reference->name }}"
                                        placeholder="Name" :readonly="!editing" class="{{ $refCell }}" @input="dirty = true">
                                </div>
                                <div class="border-r border-gray-200">
                                    <input type="text" name="contact" value="{{ $reference->contact }}"
                                        placeholder="Contact" :readonly="!editing" class="{{ $refCell }}" @input="dirty = true">
                                </div>
                                <div class="border-r border-gray-200">
                                    <input type="email" name="email" value="{{ $reference->email }}"
                                        placeholder="Email" :readonly="!editing" class="{{ $refCell }}" @input="dirty = true">
                                </div>
                                <div>
                                    <input type="text" name="address" value="{{ $reference->address }}"
                                        placeholder="Address" :readonly="!editing" class="{{ $refCell }}" @input="dirty = true">
                                </div>
                            </form>

                            @if ($rowDeleteUrl)
                            <form method="POST" action="{{ $rowDeleteUrl }}"
                                class="js-deleteform hidden"
                                :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                @csrf
                                @method('DELETE')
                            </form>
                            @endif

                            <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                @if ($rowDeleteUrl)
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
                        @if ($refStoreUrl)
                        <template x-for="row in newRows.ref" :key="row.id">
                            <div class="flex items-stretch">
                                <form method="POST" action="{{ $refStoreUrl }}"
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
                        @endif

                        {{-- Footer actions. Unlike II/III/IV these live inside the frame, since
                             this table doesn't scroll horizontally. --}}
                        <div class="px-3 py-2 flex items-center gap-4" x-show="editing" x-cloak>
                            @if ($refStoreUrl)
                            <button type="button" @click="addRow('ref')"
                                class="text-sm font-medium text-[#11386A] hover:text-[#6D0D23] hover:underline transition">
                                + Add Entry
                            </button>
                            @endif

                            <span x-show="removalCount('ref-') > 0" x-cloak class="text-xs text-gray-500">
                                <span x-text="removalCount('ref-')"></span> marked for removal on save.
                                <button type="button" @click="restoreRemovals('ref-')" class="underline hover:text-gray-700">Restore</button>
                            </span>
                        </div>
                    </div>
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{--
                    ACTION ROW

                    Same shapes as the founder view: flex-1, rounded-lg, py-2.5, text-sm font-semibold.
                    Back is an <a> styled as the outlined button. Edit keeps the gradient-border
                    treatment. Approve & Lock is the filled gradient, since it's the terminal action.
                --}}
                <div class="mt-10">

                    {{-- Locked --}}
                    <template x-if="isLocked">
                        <div class="flex gap-3">
                            <a href="{{ $backUrl }}"
                                class="flex-1 text-center border border-gray-300 bg-white text-gray-700 rounded-lg py-2.5 text-sm font-semibold hover:bg-gray-50 transition">
                                Back
                            </a>
                            <div class="flex-1 text-center bg-gray-100 text-gray-500 rounded-lg py-2.5 text-sm font-medium">
                                Approved &amp; Locked{{ $sheet?->approved_at ? ' — ' . $sheet->approved_at->format('m/d/Y') : '' }}
                            </div>
                        </div>
                    </template>

                    {{-- View mode: Back / Edit / Approve & Lock --}}
                    <div class="flex gap-3" x-show="!isLocked && !editing && !confirmingApprove" x-cloak>
                        <a href="{{ $backUrl }}"
                            class="flex-1 text-center border border-gray-300 bg-white text-gray-700 rounded-lg py-2.5 text-sm font-semibold hover:bg-gray-50 transition">
                            Back
                        </a>

                        @if ($sheetUpdateUrl)
                        <div class="flex-1 rounded-lg p-[1.5px] bg-gradient-to-r from-[#6D0D23] to-[#11386A]">
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
                        @endif

                        @if ($approveUrl)
                        @php $canApprove = $startup->hasScheduledEvaluation(); @endphp
                        <button type="button"
                            @click="{{ $canApprove ? 'confirmingApprove = true' : '' }}"
                            @disabled(! $canApprove)
                            title="{{ $canApprove ? '' : 'Schedule an evaluation for this startup before approving.' }}"
                            class="flex-1 rounded-lg py-2.5 text-sm font-semibold text-white
                                   bg-gradient-to-r from-[#6D0D23] to-[#11386A]
                                   hover:opacity-95 transition
                                   disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:opacity-50">
                            Approve &amp; Lock
                        </button>
                        @endif
                    </div>

                    {{-- Approve confirmation. Locking is irreversible from the founder's side,
                         so it doesn't fire on a single click. --}}
                    @if ($approveUrl)
                    <div x-show="confirmingApprove" x-cloak class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-700">
                            Approving locks this sheet. {{ $startup->company_name }} won't be able to edit it and
                            will be told to contact their Coordinator for changes.
                        </p>
                        <div class="flex gap-3 mt-3">
                            <button type="button" @click="confirmingApprove = false"
                                class="flex-1 border border-gray-300 bg-white text-gray-700 rounded-lg py-2.5 text-sm font-semibold hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <form method="POST" action="{{ $approveUrl }}" class="flex-1"
                                @submit="dirty = false; $store.navigation.hasUnsavedChanges = false">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="w-full rounded-lg py-2.5 text-sm font-semibold text-white
                                           bg-gradient-to-r from-[#6D0D23] to-[#11386A]
                                           hover:opacity-95 transition">
                                    Yes, approve &amp; lock
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    {{-- Edit mode: Cancel / Save --}}
                    <div class="flex gap-3" x-show="editing && !isLocked" x-cloak>
                        <button
                            type="button"
                            @click="editing = false; dirty = false; pendingRemoval = []"
                            class="flex-1 border border-gray-300 bg-white text-gray-700 rounded-lg py-2.5 text-sm font-semibold
                                   hover:bg-gray-50 transition">
                            Cancel
                        </button>

                        <button
                            type="button"
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
                // A form with no action means the route isn't registered yet — posting it
                // would hit this same page and 405.
                if (!form.getAttribute('action')) return false;
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
</x-layouts.admin>