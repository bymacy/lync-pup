<x-layouts.admin :title="$startup->company_name.' - Information Sheet'">
    @php
        $sheet = $startup->informationSheet;
        $backUrl = request('from') === 'assessment-hub'
            ? route('admin.assessment-hub.index')
            : route('admin.startups.show', $startup);
    @endphp

    <div
        class="bg-white rounded-xl border border-gray-200 max-w-5xl mx-auto"
        x-data="{
            editing: false,
            isLocked: {{ $sheet?->approval_status === 'Approved' ? 'true' : 'false' }},
            saving: false,
            dirty: false,
            approveConfirmOpen: false,
            leaveConfirmOpen: false,
            leaveConfirmTarget: null,
            lastClickedInput: null,

            newRows: { team: [], inc: [], ld: [], ref: [] },
            nextRowId: 1,

            addRow(section) {
                this.newRows[section].push({ id: this.nextRowId++ });
            },

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

                    this.dirty = false;

                    if (result?.created > 0 || result?.removed > 0) {
                        sessionStorage.setItem('infoSheetSaved', '1');
                        window.location.reload();
                        return;
                    }

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
                    $refs.editButton?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            }
        "
        x-init="
            $watch('editing', value => {
                if (!value) newRows = { team: [], inc: [], ld: [], ref: [] };
            });

            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        "
    >
        <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h2 class="font-bold">{{ $startup->company_name }}</h2>
            <a href="{{ $backUrl }}" class="text-white/70 hover:text-white">&times;</a>
        </div>

        <div class="p-6">
            <p class="text-center text-xs text-rose-800 font-medium mb-2">PUP-TBIDO FORM No.001</p>
            <h1 class="text-center font-bold text-lg mb-2">STARTUP INFORMATION SHEET</h1>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-gray-600 mb-6">
                During the evaluation meeting, click <span class="font-semibold">Edit</span> below to fill in or correct any field together with the founder — this is especially useful for fields like Target Market, Problem Statement, and Solution Offered that don't have anything in them yet.
            </div>

            @php
                // Renders a labeled input, pre-filled from the sheet, that only becomes
                // typeable once `editing` is true. Every field is treated the same way —
                // filled-in fields aren't locked, they're just as editable as blank ones.
                $field = function ($name, $label, $type = 'text') use ($sheet) {
                    $value = old($name, $sheet?->{$name});
                    return '<div><label class="text-gray-500 text-xs mb-1 block">'.e($label).'</label>'
                        .'<input type="'.$type.'" name="'.$name.'" value="'.e($value).'" form="info-sheet-form"'
                        .' :readonly="!editing" placeholder="N/A"'
                        .' class="w-full border rounded px-3 py-2 text-sm read-only:bg-gray-50 read-only:text-gray-700 placeholder:text-gray-300"'
                        .' @input="dirty = true"></div>';
                };

                $textarea = function ($name, $label) use ($sheet) {
                    $value = old($name, $sheet?->{$name});
                    return '<div><label class="text-gray-500 text-xs mb-1 block">'.e($label).'</label>'
                        .'<textarea name="'.$name.'" rows="3" form="info-sheet-form"'
                        .' :readonly="!editing" placeholder="N/A"'
                        .' class="w-full border rounded px-3 py-2 text-sm read-only:bg-gray-50 read-only:text-gray-700 placeholder:text-gray-300"'
                        .' @input="dirty = true">'.e($value).'</textarea></div>';
                };
            @endphp

            <form id="info-sheet-form" method="POST" action="{{ route('admin.information-sheet.update', $startup) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- I. Founder's Information --}}
                <div class="mb-6">
                    <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">I. FOUNDER'S INFORMATION</h3>
                    <div class="border border-t-0 rounded-b-lg p-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        {!! $field('surname', 'SURNAME') !!}
                        {!! $field('first_name', 'FIRST NAME') !!}
                        {!! $field('middle_name', 'MIDDLE NAME') !!}
                        {!! $field('name_extension', 'NAME EXTENSION') !!}
                        {!! $field('sex', 'SEX') !!}
                        {!! $field('civil_status', 'CIVIL STATUS') !!}
                        {!! $field('height_m', 'HEIGHT (M)') !!}
                        {!! $field('weight_kg', 'WEIGHT (KG)') !!}
                        {!! $field('blood_type', 'BLOOD TYPE') !!}
                        {!! $field('citizenship_by_birth', 'CITIZENSHIP (BY BIRTH)') !!}
                        {!! $field('citizenship_dual', 'CITIZENSHIP (DUAL)') !!}
                        {!! $field('place_of_birth', 'PLACE OF BIRTH') !!}
                        {!! $field('date_of_birth', 'DATE OF BIRTH', 'date') !!}
                        {!! $field('gsis_no', 'GSIS ID NO.') !!}
                        {!! $field('pagibig_no', 'PAG-IBIG NO.') !!}
                        {!! $field('philhealth_no', 'PHILHEALTH NO.') !!}
                        {!! $field('sss_no', 'SSS NO.') !!}
                        {!! $field('mobile_no', 'MOBILE NO.') !!}
                        {!! $field('founder_email', 'EMAIL ADDRESS') !!}
                        <div class="md:col-span-2">{!! $field('residential_address', 'RESIDENTIAL ADDRESS') !!}</div>
                        <div class="md:col-span-2">{!! $field('permanent_address', 'PERMANENT ADDRESS') !!}</div>
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
                                        <td class="px-3 py-2 font-medium align-top">{{ $label }}</td>
                                        <td class="border p-1"><input type="text" name="{{ $key }}_school" value="{{ old("{$key}_school", $sheet?->{"{$key}_school"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="N/A" class="w-full border-0 px-2 py-1.5 text-sm read-only:bg-transparent read-only:text-gray-700 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                                        <td class="border p-1"><input type="text" name="{{ $key }}_degree_course" value="{{ old("{$key}_degree_course", $sheet?->{"{$key}_degree_course"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="N/A" class="w-full border-0 px-2 py-1.5 text-sm read-only:bg-transparent read-only:text-gray-700 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                                        <td class="border p-1"><input type="text" name="{{ $key }}_highest_level_unit" value="{{ old("{$key}_highest_level_unit", $sheet?->{"{$key}_highest_level_unit"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="N/A" class="w-full border-0 px-2 py-1.5 text-sm read-only:bg-transparent read-only:text-gray-700 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                                        <td class="border p-1"><input type="text" name="{{ $key }}_year_graduated" value="{{ old("{$key}_year_graduated", $sheet?->{"{$key}_year_graduated"}) }}" form="info-sheet-form" :readonly="!editing" placeholder="N/A" class="w-full border-0 px-2 py-1.5 text-sm read-only:bg-transparent read-only:text-gray-700 placeholder:text-gray-300 focus:outline-none" @input="dirty = true"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <p class="text-xs text-gray-500 mt-4 mb-1 font-medium">23. SCHOLARSHIP / ACADEMIC HONORS RECEIVED</p>
                        {!! $textarea('scholarships_academic_honors', '') !!}
                    </div>
                </div>
            </form>
            {{-- The main form closes here for layout purposes only — every field below
                 that belongs to it still carries form="info-sheet-form". --}}

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
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($startup->teamMembers as $member)
                                @php $rowKey = 'team-'.$member->getKey(); @endphp
                                <tr class="border-t" x-show="!isRemoving('{{ $rowKey }}')">
                                    <td colspan="10" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.team-members.update', $member) }}"
                                            class="js-subform flex" :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                            @csrf @method('PATCH')
                                            <div class="flex-1 grid grid-cols-9">
                                                <input type="text" name="full_name" value="{{ $member->full_name }}" placeholder="Name" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="designation" value="{{ $member->designation ?? $member->role }}" placeholder="Designation" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="phone" value="{{ $member->phone }}" placeholder="Phone" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="address" value="{{ $member->address }}" placeholder="Address" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_of_birth" value="{{ $member->date_of_birth?->format('Y-m-d') }}" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="email" name="email" value="{{ $member->email }}" placeholder="Email" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="citizenship" value="{{ $member->citizenship }}" placeholder="Citizenship" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="sex" value="{{ $member->sex }}" placeholder="Sex" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="civil_status" value="{{ $member->civil_status }}" placeholder="Civil Status" :readonly="!editing" class="px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                            <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                                <button type="button" x-show="editing" x-cloak @click.prevent="toggleRemoval('{{ $rowKey }}')" title="Remove" class="text-red-600 hover:text-red-800">&times;</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('admin.information-sheet.team-members.destroy', $member) }}"
                                            class="js-deleteform hidden" :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                            @csrf @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-3 text-gray-400" colspan="10">No team members listed.</td></tr>
                            @endforelse

                            <template x-for="row in newRows.team" :key="row.id">
                                <tr>
                                    <td colspan="10" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.team-members.store', $startup) }}" class="js-subform js-addform flex">
                                            @csrf
                                            <div class="flex-1 grid grid-cols-9">
                                                <input type="text" name="full_name" placeholder="Name" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="designation" placeholder="Designation" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="phone" placeholder="Phone" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="address" placeholder="Address" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_of_birth" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="email" name="email" placeholder="Email" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="citizenship" placeholder="Citizenship" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="sex" placeholder="Sex" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="civil_status" placeholder="Civil Status" class="px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                        </form>
                                        <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                            <button type="button" @click="discardRow('team', row.id)" title="Discard" class="text-red-600 hover:text-red-800">&times;</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center gap-4 mt-2" x-show="editing" x-cloak>
                    <button type="button" @click="addRow('team')" class="text-sm font-medium text-blue-900 hover:underline">+ Add Team Member</button>
                    <span x-show="removalCount('team-') > 0" x-cloak class="text-xs text-gray-500">
                        <span x-text="removalCount('team-')"></span> marked for removal on save.
                        <button type="button" @click="restoreRemovals('team-')" class="underline">Restore</button>
                    </span>
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
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sheet?->incubationInvolvements ?? [] as $item)
                                @php $rowKey = 'inc-'.$item->id; @endphp
                                <tr class="border-t" x-show="!isRemoving('{{ $rowKey }}')">
                                    <td colspan="6" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.incubation.update', $item) }}"
                                            class="js-subform flex" :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                            @csrf @method('PATCH')
                                            <div class="flex-1 grid grid-cols-5">
                                                <input type="text" name="organization_name_address" value="{{ $item->organization_name_address }}" placeholder="Organization Name & Address" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}" placeholder="Hours" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="incubation_program_focus" value="{{ $item->incubation_program_focus }}" placeholder="Program/Focus" :readonly="!editing" class="px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                            <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                                <button type="button" x-show="editing" x-cloak @click.prevent="toggleRemoval('{{ $rowKey }}')" title="Remove" class="text-red-600 hover:text-red-800">&times;</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('admin.information-sheet.incubation.destroy', $item) }}"
                                            class="js-deleteform hidden" :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                            @csrf @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-3 text-gray-400" colspan="6">None listed.</td></tr>
                            @endforelse

                            <template x-for="row in newRows.inc" :key="row.id">
                                <tr>
                                    <td colspan="6" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.incubation.store', $startup) }}" class="js-subform js-addform flex">
                                            @csrf
                                            <div class="flex-1 grid grid-cols-5">
                                                <input type="text" name="organization_name_address" placeholder="Organization Name & Address" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_from" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_to" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="number_of_hours" placeholder="Hours" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="incubation_program_focus" placeholder="Program/Focus" class="px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                        </form>
                                        <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                            <button type="button" @click="discardRow('inc', row.id)" title="Discard" class="text-red-600 hover:text-red-800">&times;</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center gap-4 mt-2" x-show="editing" x-cloak>
                    <button type="button" @click="addRow('inc')" class="text-sm font-medium text-blue-900 hover:underline">+ Add Entry</button>
                    <span x-show="removalCount('inc-') > 0" x-cloak class="text-xs text-gray-500">
                        <span x-text="removalCount('inc-')"></span> marked for removal on save.
                        <button type="button" @click="restoreRemovals('inc-')" class="underline">Restore</button>
                    </span>
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
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sheet?->ldInterventions ?? [] as $item)
                                @php $rowKey = 'ld-'.$item->id; @endphp
                                <tr class="border-t" x-show="!isRemoving('{{ $rowKey }}')">
                                    <td colspan="6" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.ld.update', $item) }}"
                                            class="js-subform flex" :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                            @csrf @method('PATCH')
                                            <div class="flex-1 grid grid-cols-5">
                                                <input type="text" name="title" value="{{ $item->title }}" placeholder="Title" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_from" value="{{ $item->date_from?->format('Y-m-d') }}" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_to" value="{{ $item->date_to?->format('Y-m-d') }}" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="number_of_hours" value="{{ $item->number_of_hours }}" placeholder="Hours" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="conducted_sponsored_by" value="{{ $item->conducted_sponsored_by }}" placeholder="Conducted/Sponsored By" :readonly="!editing" class="px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                            <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                                <button type="button" x-show="editing" x-cloak @click.prevent="toggleRemoval('{{ $rowKey }}')" title="Remove" class="text-red-600 hover:text-red-800">&times;</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('admin.information-sheet.ld.destroy', $item) }}"
                                            class="js-deleteform hidden" :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                            @csrf @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-3 text-gray-400" colspan="6">None listed.</td></tr>
                            @endforelse

                            <template x-for="row in newRows.ld" :key="row.id">
                                <tr>
                                    <td colspan="6" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.ld.store', $startup) }}" class="js-subform js-addform flex">
                                            @csrf
                                            <div class="flex-1 grid grid-cols-5">
                                                <input type="text" name="title" placeholder="Title" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_from" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="date" name="date_to" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="number_of_hours" placeholder="Hours" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="conducted_sponsored_by" placeholder="Conducted/Sponsored By" class="px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                        </form>
                                        <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                            <button type="button" @click="discardRow('ld', row.id)" title="Discard" class="text-red-600 hover:text-red-800">&times;</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center gap-4 mt-2" x-show="editing" x-cloak>
                    <button type="button" @click="addRow('ld')" class="text-sm font-medium text-blue-900 hover:underline">+ Add Entry</button>
                    <span x-show="removalCount('ld-') > 0" x-cloak class="text-xs text-gray-500">
                        <span x-text="removalCount('ld-')"></span> marked for removal on save.
                        <button type="button" @click="restoreRemovals('ld-')" class="underline">Restore</button>
                    </span>
                </div>
            </div>

            {{-- V. Startup Information --}}
            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">V. STARTUP INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="text-gray-500 text-xs mb-1 block">STARTUP NAME</label>
                        <div class="border rounded px-3 py-2 bg-gray-50 text-sm">{{ $startup->company_name }}</div>
                    </div>
                    {!! $field('sec_registration', 'SEC REGISTRATION') !!}
                    {!! $field('business_id_number', 'BUSINESS ID NUMBER') !!}
                    {!! $field('dti_registration_number', 'DTI REGISTRATION NUMBER') !!}
                    {!! $field('business_tin', 'BUSINESS TIN') !!}
                    <div class="md:col-span-2">{!! $textarea('business_description', 'STARTUP OVERVIEW') !!}</div>
                    <div class="md:col-span-2">{!! $textarea('target_market', 'TARGET MARKET') !!}</div>
                    <div class="md:col-span-2">{!! $textarea('problem_statement', 'PROBLEM STATEMENT') !!}</div>
                    <div class="md:col-span-2">{!! $textarea('solution_offered', 'SOLUTION OFFERED') !!}</div>
                    <div class="md:col-span-2">{!! $textarea('non_academic_distinctions', 'NON-ACADEMIC DISTINCTIONS / RECOGNITION / ELIGIBILITIES') !!}</div>
                    <div class="md:col-span-2">{!! $textarea('membership_associations', 'MEMBERSHIP IN ASSOCIATION/ORGANIZATION') !!}</div>
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
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sheet?->references ?? [] as $ref)
                                @php $rowKey = 'ref-'.$ref->id; @endphp
                                <tr class="border-t" x-show="!isRemoving('{{ $rowKey }}')">
                                    <td colspan="5" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.references.update', $ref) }}"
                                            class="js-subform flex" :class="isRemoving('{{ $rowKey }}') && 'js-skip'">
                                            @csrf @method('PATCH')
                                            <div class="flex-1 grid grid-cols-4">
                                                <input type="text" name="name" value="{{ $ref->name }}" placeholder="Name" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="contact" value="{{ $ref->contact }}" placeholder="Contact" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="email" name="email" value="{{ $ref->email }}" placeholder="Email" :readonly="!editing" class="border-r px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="address" value="{{ $ref->address }}" placeholder="Address" :readonly="!editing" class="px-3 py-2 text-sm read-only:bg-transparent focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                            <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                                <button type="button" x-show="editing" x-cloak @click.prevent="toggleRemoval('{{ $rowKey }}')" title="Remove" class="text-red-600 hover:text-red-800">&times;</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('admin.information-sheet.references.destroy', $ref) }}"
                                            class="js-deleteform hidden" :class="isRemoving('{{ $rowKey }}') ? 'js-subform' : ''">
                                            @csrf @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-3 text-gray-400" colspan="5">None listed.</td></tr>
                            @endforelse

                            <template x-for="row in newRows.ref" :key="row.id">
                                <tr>
                                    <td colspan="5" class="p-0">
                                        <form method="POST" action="{{ route('admin.information-sheet.references.store', $startup) }}" class="js-subform js-addform flex">
                                            @csrf
                                            <div class="flex-1 grid grid-cols-4">
                                                <input type="text" name="name" placeholder="Name" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="contact" placeholder="Contact" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="email" name="email" placeholder="Email" class="border-r px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                                <input type="text" name="address" placeholder="Address" class="px-3 py-2 text-sm focus:outline-none focus:bg-blue-50" @input="dirty = true">
                                            </div>
                                        </form>
                                        <div class="w-10 flex-shrink-0 flex items-center justify-center">
                                            <button type="button" @click="discardRow('ref', row.id)" title="Discard" class="text-red-600 hover:text-red-800">&times;</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="flex items-center gap-4 mt-2" x-show="editing" x-cloak>
                        <button type="button" @click="addRow('ref')" class="text-sm font-medium text-blue-900 hover:underline">+ Add Reference</button>
                        <span x-show="removalCount('ref-') > 0" x-cloak class="text-xs text-gray-500">
                            <span x-text="removalCount('ref-')"></span> marked for removal on save.
                            <button type="button" @click="restoreRemovals('ref-')" class="underline">Restore</button>
                        </span>
                    </div>
                </div>
            </div>

            {{-- 36. Declaration & Endorsement --}}
            <div class="mb-6 border rounded-lg p-4">
                <p class="text-xs text-gray-600 mb-4">
                    I declare that I have personally accomplished this Startup Information Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines.
                </p>

                <div class="grid grid-cols-2 gap-6 text-sm mb-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">FOUNDER'S SIGNATURE</p>
                        @if ($sheet?->founder_signature_path)
                            <img src="{{ Storage::url($sheet->founder_signature_path) }}" class="border rounded h-20 object-contain mb-2">
                        @else
                            <div class="border rounded px-3 py-6 bg-gray-50 text-center text-gray-400 text-xs mb-2">No signature uploaded</div>
                        @endif
                        <input type="file" name="founder_signature" accept="image/*" form="info-sheet-form" x-show="editing" x-cloak @change="dirty = true" class="text-xs">
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">DIRECTOR'S SIGNATURE</p>
                        @if ($sheet?->director_signature_path)
                            <img src="{{ Storage::url($sheet->director_signature_path) }}" class="border rounded h-20 object-contain mb-2">
                        @else
                            <div class="border rounded px-3 py-6 bg-gray-50 text-center text-gray-400 text-xs mb-2">No signature uploaded</div>
                        @endif
                        <input type="file" name="director_signature" accept="image/*" form="info-sheet-form" x-show="editing" x-cloak @change="dirty = true" class="text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    {!! $field('date_accomplished', 'DATE ACCOMPLISHED', 'date') !!}
                    {!! $field('portfolio_manager', 'PORTFOLIO MANAGER') !!}
                    {!! $field('cohort_no', 'COHORT NO.') !!}
                    {!! $field('endorsed_by', 'ENDORSED BY') !!}
                    {!! $field('endorsement_date', 'ENDORSEMENT DATE', 'date') !!}
                    {!! $field('director_approval_date', 'DATE OF APPROVAL', 'date') !!}
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex gap-3">
                <button type="button"
                    @click="if (dirty) { leaveConfirmTarget = '{{ $backUrl }}'; leaveConfirmOpen = true } else { window.location = '{{ $backUrl }}' }"
                    class="flex-1 text-center border rounded-lg py-3 text-sm font-medium text-blue-900 border-blue-900">
                    BACK
                </button>

                <template x-if="isLocked">
                    <div class="flex-1 text-center bg-gray-100 text-gray-500 rounded-lg py-3 text-sm font-medium">
                        Approved & Locked
                    </div>
                </template>

                <template x-if="!isLocked && !editing">
                    <button type="button" x-ref="editButton"
                        @click="
                            editing = true;
                            $nextTick(() => {
                                if (lastClickedInput) {
                                    const el = document.querySelector(`[name='${lastClickedInput}']`);
                                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); }
                                }
                            });
                        "
                        class="flex-1 border border-blue-900 text-blue-900 rounded-lg py-3 text-sm font-medium hover:bg-blue-50">
                        Edit
                    </button>
                </template>

                <template x-if="editing && !isLocked">
                    <button type="button"
                        @click="if (dirty) { leaveConfirmTarget = 'cancel'; leaveConfirmOpen = true } else { editing = false; pendingRemoval = [] }"
                        class="flex-1 border border-gray-300 bg-white text-gray-700 rounded-lg py-3 text-sm font-medium hover:bg-gray-50">
                        Cancel
                    </button>
                </template>

                <button type="button" x-show="editing && !isLocked" x-cloak @click="saveAll()" :disabled="saving"
                    class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] hover:opacity-90 transition-all duration-200 text-white rounded-lg py-3 text-sm font-medium disabled:opacity-60">
                    <span x-text="saving ? 'Saving…' : 'Save'"></span>
                </button>

                @if ($startup->informationSheet?->approval_status === 'Pending')
                    <template x-if="!isLocked && !editing">
                        <button type="button" @click="approveConfirmOpen = true" class="flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] hover:opacity-90 transition-all duration-200 text-white rounded-lg py-3 text-sm font-medium">
                            APPROVE & LOCK
                        </button>
                    </template>
                @endif
            </div>

            <x-confirm-action-modal
                show="approveConfirmOpen" close="approveConfirmOpen = false"
                title="Confirm Startup Approval"
                message="Approving will lock this Information Sheet — no further edits will be possible unless a Coordinator reopens it."
                :action="route('admin.information-sheet.approve', $startup)"
                method="PATCH" confirm-label="Confirm" icon="people" />

            <x-leave-confirm-modal
                show="leaveConfirmOpen" close="leaveConfirmOpen = false"
                confirm="leaveConfirmOpen = false; if (leaveConfirmTarget === 'cancel') { editing = false; dirty = false; pendingRemoval = []; newRows = { team: [], inc: [], ld: [], ref: [] } } else { window.location = leaveConfirmTarget }" />
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
                if (form.classList.contains('js-skip')) return false;
                if (form.classList.contains('js-addform') && isBlank(form)) return false;
                return true;
            });

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
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    const error = new Error('Request to ' + form.action + ' failed with status ' + response.status);
                    error.status = response.status;
                    error.action = form.action;

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

            return { created, removed };
        };

        (function() {
            let shown = false;

            const flushSavedToast = () => {
                if (shown) return;
                if (!sessionStorage.getItem('infoSheetSaved')) return;

                const toast = window.Alpine && window.Alpine.store('toast');
                if (!toast) return;

                sessionStorage.removeItem('infoSheetSaved');
                shown = true;

                toast.success('Information Sheet Saved', 'Your changes have been saved successfully.');
            };

            document.addEventListener('alpine:initialized', flushSavedToast);
            window.addEventListener('load', flushSavedToast);
            setTimeout(flushSavedToast, 300);
        })();
    </script>
</x-layouts.admin>
