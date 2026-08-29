@php
    $doc6Data = $activeDocuments->get(6)?->data ?? [];
    $doc6Seed = [
        'business_stage' => array_merge(
            array_fill_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_6_BUSINESS_STAGES, false),
            $doc6Data['business_stage'] ?? []
        ),
    ];
    $blankRow = array_fill_keys(array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_6_ROW_COLUMNS), '');
    foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_6_SECTIONS as $sectionKey => $section) {
        $doc6Seed[$sectionKey] = $doc6Data[$sectionKey] ?? array_fill(0, $section['default_rows'], $blankRow);
    }

    // Document 6's own signatory block — three "Prepared By" signatories
    // (each with a fixed default title/position, editable-but-prefilled
    // like the assessment form's other signatory blocks) plus a single
    // "Noted By" name with no accompanying title.
    $doc6PreparedByDefaults = [
        'Startup Development Chief, TBIDO',
        'Incubation Management Chief, TBIDO',
        'Technology Development Chief, TBIDO',
    ];
    $doc6Seed['prepared_by'] = [];
    foreach ($doc6PreparedByDefaults as $i => $defaultPosition) {
        $doc6Seed['prepared_by'][] = [
            'name' => $doc6Data['prepared_by'][$i]['name'] ?? '',
            'position' => $doc6Data['prepared_by'][$i]['position'] ?? $defaultPosition,
        ];
    }
    $doc6Seed['noted_by'] = $doc6Data['noted_by'] ?? '';
    $doc6Seed['noted_by_position'] = $doc6Data['noted_by_position'] ?? 'Director, TBIDO';

    $doc7Data = $activeDocuments->get(7)?->data ?? [];
    $blankCheckInRow = array_fill_keys(array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_7_ROW_COLUMNS), '');
    $doc7Seed = [
        'check_ins' => $doc7Data['check_ins'] ?? array_fill(0, \App\Support\ActiveAssessmentForms::DOCUMENT_7_DEFAULT_ROWS, $blankCheckInRow),
        'performance_matrix' => [],
    ];
    $blankMetricRow = array_fill_keys(array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_7_PERFORMANCE_COLUMNS), '');
    foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_7_PERFORMANCE_METRICS as $metric) {
        $doc7Seed['performance_matrix'][$metric] = $doc7Data['performance_matrix'][$metric] ?? $blankMetricRow;
    }

    // Document 7's own signatory block — one Prepared By, one Noted By,
    // both editable-but-prefilled like the assessment form's other
    // signatory blocks.
    $doc7Seed['prepared_by_name'] = $doc7Data['prepared_by_name'] ?? '';
    $doc7Seed['prepared_by_position'] = $doc7Data['prepared_by_position'] ?? 'Portfolio Coordinator, TBIDO';
    $doc7Seed['noted_by_name'] = $doc7Data['noted_by_name'] ?? '';
    $doc7Seed['noted_by_position'] = $doc7Data['noted_by_position'] ?? 'Assigned Chief, TBIDO';

    $doc8Data = $activeDocuments->get(8)?->data ?? [];
    $checklistSeed = fn (array $options, array $stored) => array_merge(
        array_fill_keys($options, false),
        ['others_checked' => false, 'others_text' => ''],
        $stored
    );
    $doc8Seed = [
        'prototype_name' => $doc8Data['prototype_name'] ?? '',
        'prototype_description' => $doc8Data['prototype_description'] ?? '',
        'platform_compatibility' => $checklistSeed(\App\Support\ActiveAssessmentForms::DOCUMENT_8_PLATFORM_COMPATIBILITY, $doc8Data['platform_compatibility'] ?? []),
        'development_status' => $checklistSeed(\App\Support\ActiveAssessmentForms::DOCUMENT_8_DEVELOPMENT_STATUS, $doc8Data['development_status'] ?? []),
        'ip_status' => $checklistSeed(\App\Support\ActiveAssessmentForms::DOCUMENT_8_IP_STATUS, $doc8Data['ip_status'] ?? []),
        'ratings' => [],
        'recommendations' => $doc8Data['recommendations'] ?? '',
    ];
    foreach (\App\Support\ActiveAssessmentForms::document8RatingCategories() as $catKey => $cat) {
        $storedRatings = $doc8Data['ratings'][$catKey] ?? [];
        $doc8Seed['ratings'][$catKey] = [];
        foreach ($cat['criteria'] as $i => $criterion) {
            $doc8Seed['ratings'][$catKey][] = $storedRatings[$i] ?? null;
        }
    }

    // Document 8's own signatory block. "Validated By" captures whoever
    // actually ran this validation (no sensible default — starts blank).
    // "Noted By" / "Approved By" are editable-but-prefilled with TBIDO's
    // fixed reviewers, same treatment as the assessment form's own blocks.
    $doc8Seed['validated_by_name'] = $doc8Data['validated_by_name'] ?? '';
    $doc8Seed['validated_by_position'] = $doc8Data['validated_by_position'] ?? '';
    $doc8Seed['validated_by_contact'] = $doc8Data['validated_by_contact'] ?? '';
    $doc8Seed['validated_by_date'] = $doc8Data['validated_by_date'] ?? '';
    $doc8Seed['noted_by_name'] = $doc8Data['noted_by_name'] ?? 'DR. JUANCHO D. ESPINELI';
    $doc8Seed['noted_by_position'] = $doc8Data['noted_by_position'] ?? 'Chief, Technology Development Section, PUP';
    $doc8Seed['approved_by_name'] = $doc8Data['approved_by_name'] ?? 'DR. PHILIP P. ERMITA, PIE, PDQM, ASEAN ENG.';
    $doc8Seed['approved_by_position'] = $doc8Data['approved_by_position']
        ?? "Director, Technology Business Incubation and Development Office, PUP\nProject Leader, DOST-HEIRIT";

    $docHasData = [6 => $activeDocuments->has(6), 7 => $activeDocuments->has(7), 8 => $activeDocuments->has(8)];
    $tableInput = 'w-full min-h-[30px] rounded border border-gray-400 px-2 py-1 text-sm leading-normal focus:outline-none focus:ring-1 focus:ring-rose-900';
@endphp

<div
    x-data="{
        activeDoc: 6,
        doc6: @js($doc6Seed),
        doc7: @js($doc7Seed),
        doc8: @js($doc8Seed),
        initialDoc6: @js($doc6Seed),
        initialDoc7: @js($doc7Seed),
        initialDoc8: @js($doc8Seed),
        showClearConfirm: false,
        showSaved: @js($justSaved ?? false),
        isDirty() {
            return JSON.stringify(this.doc6) !== JSON.stringify(this.initialDoc6)
                || JSON.stringify(this.doc7) !== JSON.stringify(this.initialDoc7)
                || JSON.stringify(this.doc8) !== JSON.stringify(this.initialDoc8);
        },
        addRow(doc, section, columns) {
            const blank = {};
            columns.forEach(c => blank[c] = '');
            this[doc][section].push(blank);
            this.$nextTick(() => {
                document.querySelectorAll(`textarea[x-model^='${doc}.${section}[']`).forEach(el => {
                    el.style.height = 'auto';
                    el.style.height = el.scrollHeight + 'px';
                });
            });
        },
        removeRow(doc, section, index) {
            this[doc][section].splice(index, 1);
        },
        avgFor(category) {
            const vals = this.doc8.ratings[category].filter(v => v !== null && v !== '');
            if (! vals.length) return null;
            return Math.round((vals.reduce((a, b) => a + Number(b), 0) / vals.length) * 100) / 100;
        },
        interpretation(avg) {
            if (avg === null) return '';
            if (avg >= 5) return 'Excellent';
            if (avg >= 4) return 'Very Good';
            if (avg >= 3) return 'Satisfactory';
            if (avg >= 2) return 'Needs Improvement';
            if (avg >= 1) return 'Poor';
            return '';
        },
        interpretationFor(category) {
            return this.interpretation(this.avgFor(category));
        },
        totalAverage() {
            const avgs = Object.keys(this.doc8.ratings).map(c => this.avgFor(c)).filter(v => v !== null);
            if (! avgs.length) return null;
            return Math.round((avgs.reduce((a, b) => a + b, 0) / avgs.length) * 100) / 100;
        },
        clearAll() {
            document.getElementById('active-assessment-form').reset();
            this.doc6.business_stage = @js(array_fill_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_6_BUSINESS_STAGES, false));
            this.doc7.check_ins.forEach(r => Object.keys(r).forEach(k => r[k] = ''));
            Object.values(this.doc7.performance_matrix).forEach(r => Object.keys(r).forEach(k => r[k] = ''));
            this.doc8.prototype_name = '';
            this.doc8.prototype_description = '';
            this.doc8.recommendations = '';
            ['platform_compatibility', 'development_status', 'ip_status'].forEach(group => {
                Object.keys(this.doc8[group]).forEach(k => this.doc8[group][k] = (k === 'others_text' ? '' : false));
            });
            Object.keys(this.doc8.ratings).forEach(cat => {
                this.doc8.ratings[cat] = this.doc8.ratings[cat].map(() => null);
            });
            this.showClearConfirm = false;
        },
    }"
    x-init="$watch(() => isDirty(), value => { $store.navigation.hasUnsavedChanges = value; })">

    <div class="mb-6 grid grid-cols-3 overflow-hidden rounded-lg border border-gray-200">
        @foreach ([6 => 'Document 6', 7 => 'Document 7', 8 => 'Document 8'] as $num => $label)
            <button type="button" @click="activeDoc = {{ $num }}"
                class="border-t-2 border-r border-gray-200 px-4 py-3 text-center transition last:border-r-0"
                :class="activeDoc === {{ $num }} ? 'border-t-[#6C0E24] bg-[#6C0E24]/5' : 'border-t-transparent bg-white hover:bg-[#6C0E24]/5'">
                <p class="font-bold" :class="activeDoc === {{ $num }} ? 'text-[#6C0E24]' : 'text-gray-900'">{{ $label }}</p>
                <p class="mt-1 flex items-center justify-center gap-1.5 text-xs {{ $docHasData[$num] ? 'text-green-600' : 'text-gray-400' }}">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $docHasData[$num] ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                    {{ $docHasData[$num] ? 'Started' : 'Not Started' }}
                </p>
            </button>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.assessment-hub.assessments.update-documents', $selectedStartup) }}" id="active-assessment-form"
        @submit="$store.navigation.hasUnsavedChanges = false">
        @csrf
        @method('PUT')
        <input type="hidden" name="stage" value="Active-Assessment">
        <input type="hidden" name="document_6" :value="JSON.stringify(doc6)">
        <input type="hidden" name="document_7" :value="JSON.stringify(doc7)">
        <input type="hidden" name="document_8" :value="JSON.stringify(doc8)">

        {{-- ============ Document 6 ============ --}}
        <div x-show="activeDoc === 6">
            <div class="rounded-t-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-3 text-center font-bold uppercase text-white">
                Startup Growth Strategy (DAGITAB Program)
            </div>
            <div class="rounded-b-lg border border-t-0 p-4">
                <p class="mb-4 text-center">
                    <span class="rounded border border-rose-900 px-3 py-1 text-xs font-semibold italic text-rose-900">PUP-TBIDO FORM No.006</span>
                </p>

                <div class="mb-4">
                    <p class="mb-1.5 text-sm font-semibold text-gray-700">Startup / Company Name</p>
                    <input type="text" value="{{ $selectedStartup?->company_name ?? '—' }}" readonly
                        class="w-full max-w-md cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                </div>

                <div class="mb-6 flex flex-wrap gap-x-10 gap-y-2">
                    <p class="font-semibold text-gray-700">Business Stage:</p>
                    @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_6_BUSINESS_STAGES as $stageOption)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" x-model="doc6.business_stage['{{ $stageOption }}']" class="h-4 w-4 rounded border-gray-300">
                            {{ $stageOption }}
                        </label>
                    @endforeach
                </div>

                @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_6_SECTIONS as $sectionKey => $section)
                    <div class="mb-8">
                        <p class="mb-2 font-bold text-gray-900">{{ $section['title'] }}</p>
                        <div class="overflow-x-auto">
                            <table class="w-full border border-gray-400 text-sm">
                                <thead>
                                    <tr class="bg-gray-50">
                                        @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_6_ROW_COLUMNS as $label)
                                            <th class="border border-gray-400 px-3 py-2 text-left">{{ $label }}</th>
                                        @endforeach
                                        <th class="w-10 border border-gray-400 px-2 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, idx) in doc6.{{ $sectionKey }}" :key="idx">
                                        <tr>
                                            @foreach (array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_6_ROW_COLUMNS) as $col)
                                                <td class="border border-gray-400 p-1">
                                                    {{-- A single-line text input hides everything past its edge behind
                                                         horizontal scroll once the entry runs long. A textarea wraps
                                                         instead, and this x-effect grows its height to fit — on every
                                                         keystroke, on tab switch (the panel is display:none while on
                                                         another tab, so height can only be measured once it's
                                                         visible again), and after Clear Form blanks it back down. --}}
                                                    <textarea rows="1" x-model="doc6.{{ $sectionKey }}[idx].{{ $col }}"
                                                        x-effect="doc6.{{ $sectionKey }}[idx].{{ $col }}; activeDoc; $nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                                                        class="{{ $tableInput }} block resize-none overflow-hidden"></textarea>
                                                </td>
                                            @endforeach
                                            <td class="border border-gray-400 p-1 text-center">
                                                <button type="button" @click="removeRow('doc6', '{{ $sectionKey }}', idx)" class="text-rose-600 hover:text-rose-800" aria-label="Remove row">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" @click="addRow('doc6', '{{ $sectionKey }}', @js(array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_6_ROW_COLUMNS)))"
                            class="mt-2 text-xs font-semibold text-rose-900 hover:underline">+ Add Row</button>
                    </div>
                @endforeach

                <div class="mt-8 border-t border-gray-200 pt-6">
                    <p class="mb-4 text-sm font-semibold text-gray-700">Prepared By:</p>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        @for ($i = 0; $i < 3; $i++)
                        <div>
                            <input type="text" x-model="doc6.prepared_by[{{ $i }}].name" placeholder="Input Name"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input type="text" x-model="doc6.prepared_by[{{ $i }}].position"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
                        </div>
                        @endfor
                    </div>

                    <p class="mb-2 mt-6 text-sm font-semibold text-gray-700">Noted By:</p>
                    <div class="max-w-xs">
                        <input type="text" x-model="doc6.noted_by" placeholder="Input Name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <input type="text" x-model="doc6.noted_by_position"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Document 7 ============ --}}
        <div x-show="activeDoc === 7" x-cloak>
            <div class="rounded-t-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-3 text-center font-bold uppercase text-white">
                Weekly Check-ins
            </div>
            <div class="rounded-b-lg border border-t-0 p-4">
                <p class="mb-4 text-center">
                    <span class="rounded border border-rose-900 px-3 py-1 text-xs font-semibold italic text-rose-900">PUP-TBIDO FORM No.007</span>
                </p>

                <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-gray-700">Startup / Company Name</p>
                        <input type="text" value="{{ $selectedStartup?->company_name ?? '—' }}" readonly
                            class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                    </div>
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-gray-700">Portfolio Coordinator</p>
                        <input type="text" value="{{ $selectedStartup?->activeCoordinatorAssignment?->coordinator?->name ?? 'Not assigned yet' }}" readonly
                            class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_7_ROW_COLUMNS as $label)
                                    <th class="border px-3 py-2 text-left">{{ $label }}</th>
                                @endforeach
                                <th class="w-10 border px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in doc7.check_ins" :key="idx">
                                <tr>
                                    @foreach (array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_7_ROW_COLUMNS) as $col)
                                        <td class="border p-1">
                                            @if ($col === 'dates')
                                            {{-- A native date input, not free text — a real calendar picker
                                                 instead of typing dates out by hand. --}}
                                            <input type="date" x-model="doc7.check_ins[idx].{{ $col }}" class="{{ $tableInput }}">
                                            @else
                                            <textarea rows="1" x-model="doc7.check_ins[idx].{{ $col }}"
                                                x-effect="doc7.check_ins[idx].{{ $col }}; activeDoc; $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                                class="{{ $tableInput }} block resize-none overflow-hidden"></textarea>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="border p-1 text-center">
                                        <button type="button" @click="removeRow('doc7', 'check_ins', idx)" class="text-rose-600 hover:text-rose-800" aria-label="Remove row">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button type="button" @click="addRow('doc7', 'check_ins', @js(array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_7_ROW_COLUMNS)))"
                    class="mb-6 mt-2 text-xs font-semibold text-rose-900 hover:underline">+ Add Row</button>

                <p class="mb-2 font-bold text-gray-900">Performance Matrix</p>
                <div class="overflow-x-auto">
                    <table class="w-full border text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border px-3 py-2 text-left">Metric</th>
                                @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_7_PERFORMANCE_COLUMNS as $label)
                                    <th class="border px-3 py-2 text-left">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Support\ActiveAssessmentForms::DOCUMENT_7_PERFORMANCE_METRICS as $metric)
                                <tr>
                                    <td class="border px-3 py-2 font-semibold">{{ $metric }}</td>
                                    @foreach (array_keys(\App\Support\ActiveAssessmentForms::DOCUMENT_7_PERFORMANCE_COLUMNS) as $col)
                                        <td class="border p-1">
                                            @if ($col === 'dates')
                                            <input type="date" x-model="doc7.performance_matrix['{{ $metric }}'].{{ $col }}" class="{{ $tableInput }}">
                                            @else
                                            <input type="text" x-model="doc7.performance_matrix['{{ $metric }}'].{{ $col }}" class="{{ $tableInput }}">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-6 border-t border-gray-200 pt-6 sm:grid-cols-2">
                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-700">Prepared By:</p>
                        <input type="text" x-model="doc7.prepared_by_name" placeholder="Input Name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <input type="text" x-model="doc7.prepared_by_position"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-700">Noted By:</p>
                        <input type="text" x-model="doc7.noted_by_name" placeholder="Input Name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <input type="text" x-model="doc7.noted_by_position"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Document 8 ============ --}}
        <div x-show="activeDoc === 8" x-cloak>
            <div class="rounded-t-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-3 text-center font-bold uppercase text-white">
                Prototype Validation Form
            </div>
            <div class="rounded-b-lg border border-t-0 p-4">
                <div class="mb-4 bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-2 font-bold text-white">
                    Section 1: Startup Profile <span class="font-normal italic text-white/80">(to be filled up by TBIDO personnel)</span>
                </div>
                <p class="mb-4 text-center">
                    <span class="rounded border border-rose-900 px-3 py-1 text-xs font-semibold italic text-rose-900">PUP-TBIDO FORM No.008</span>
                </p>

                <div class="mb-4">
                    <p class="mb-1.5 text-sm font-semibold text-gray-700">Startup / Company Name</p>
                    <input type="text" value="{{ $selectedStartup?->company_name ?? '—' }}" readonly
                        class="w-full max-w-md cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Prototype / Product Name:</label>
                    <input type="text" x-model="doc8.prototype_name" class="w-full rounded border px-3 py-2 text-sm">
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Brief Description of the Prototype:</label>
                    <p class="mb-1 text-xs italic text-gray-500">Summarize the function, core objective, and purpose of the prototype in 3-5 sentences. Include context if necessary.</p>
                    <textarea x-model="doc8.prototype_description" rows="3" class="w-full rounded border px-3 py-2 text-sm"></textarea>
                </div>

                <p class="mb-3 text-xs text-gray-600">
                    <strong>Instruction:</strong> Please check all applicable options in each column that best describe the platform compatibility, current development status, and IP status of the product. Use the "Others" field if none of the listed options apply.
                </p>

                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ([
                        ['key' => 'platform_compatibility', 'title' => 'Platform Compatibility', 'options' => \App\Support\ActiveAssessmentForms::DOCUMENT_8_PLATFORM_COMPATIBILITY],
                        ['key' => 'development_status', 'title' => 'Current Development Status', 'options' => \App\Support\ActiveAssessmentForms::DOCUMENT_8_DEVELOPMENT_STATUS],
                        ['key' => 'ip_status', 'title' => 'Intellectual Property (IP) Status', 'options' => \App\Support\ActiveAssessmentForms::DOCUMENT_8_IP_STATUS],
                    ] as $group)
                        <div class="rounded border">
                            <p class="border-b bg-gray-50 px-3 py-2 text-center text-sm font-semibold">{{ $group['title'] }}</p>
                            <div class="space-y-1.5 p-3">
                                @foreach ($group['options'] as $opt)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="doc8.{{ $group['key'] }}['{{ $opt }}']" class="h-4 w-4 rounded border-gray-300">
                                        {{ $opt }}
                                    </label>
                                @endforeach
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" x-model="doc8.{{ $group['key'] }}.others_checked" class="h-4 w-4 rounded border-gray-300">
                                    Others:
                                    <input type="text" x-model="doc8.{{ $group['key'] }}.others_text" class="flex-1 border-b border-gray-300 px-1 text-sm focus:outline-none">
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mb-4 bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-2 font-bold text-white">
                    Section 2: Prototype Assessment
                </div>

                <div class="mb-6 grid grid-cols-1 gap-4 text-xs text-gray-600 md:grid-cols-2">
                    <div>
                        <p class="mb-1 font-semibold">Please rate the following statements using the scale:</p>
                        <p>5 - Strongly Agree | 4 - Agree | 3 - Neutral | 2 - Disagree | 1 - Strongly Disagree</p>
                        <p class="mt-2 font-semibold">Score Interpretation Guide (Per Average Rating):</p>
                        <ul class="list-disc pl-4">
                            <li>5.00 – Excellent: Outstanding usability; no improvement needed</li>
                            <li>4.00–4.99 – Very Good: Performs well with minor suggestions</li>
                            <li>3.00–3.99 – Satisfactory: Usable but needs some improvements</li>
                            <li>2.00–2.99 – Needs Improvement: Noticeable usability issues</li>
                            <li>1.00–1.99 – Poor: Major issues, needs redesign or rework</li>
                        </ul>
                    </div>
                    <div class="flex items-center justify-center rounded border p-3 text-center">
                        <p class="font-semibold">Total Average Score Formula<br><span class="text-lg">TAS = Total Score / No. of Criteria</span></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @foreach (\App\Support\ActiveAssessmentForms::document8RatingCategories() as $catKey => $cat)
                        <div class="overflow-x-auto">
                            <table class="w-full border text-sm">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="w-8 border px-2 py-2">No.</th>
                                        <th class="border px-3 py-2 text-left">{{ $cat['title'] }}</th>
                                        @foreach ([5, 4, 3, 2, 1] as $n)
                                            <th class="w-8 border px-2 py-2 text-center">{{ $n }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cat['criteria'] as $i => $criterion)
                                        <tr>
                                            <td class="border px-2 py-2 text-center">{{ $i + 1 }}</td>
                                            <td class="border px-3 py-2">{{ $criterion }}</td>
                                            @foreach ([5, 4, 3, 2, 1] as $n)
                                                <td class="border px-2 py-2 text-center">
                                                    <input type="radio" x-model.number="doc8.ratings.{{ $catKey }}[{{ $i }}]" value="{{ $n }}" class="h-4 w-4">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-50 font-semibold">
                                        <td class="border px-3 py-2" colspan="2">Total Average Score</td>
                                        <td class="border px-2 py-2 text-center" colspan="5" x-text="avgFor('{{ $catKey }}') ?? '—'"></td>
                                    </tr>
                                    <tr class="bg-gray-50 font-semibold">
                                        <td class="border px-3 py-2" colspan="2">Score Interpretation</td>
                                        <td class="border px-2 py-2 text-center" colspan="5" x-text="interpretationFor('{{ $catKey }}') || '—'"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <p class="mb-2 font-bold text-gray-900">Summary</p>
                    <table class="w-full border text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border px-3 py-2 text-left">Category</th>
                                <th class="border px-3 py-2 text-center">Average Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Support\ActiveAssessmentForms::document8RatingCategories() as $catKey => $cat)
                                <tr>
                                    <td class="border px-3 py-2">{{ $loop->iteration }}. {{ $cat['title'] }}</td>
                                    <td class="border px-3 py-2 text-center" x-text="avgFor('{{ $catKey }}') ?? '—'"></td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50 font-semibold">
                                <td class="border px-3 py-2">Total Average Score</td>
                                <td class="border px-3 py-2 text-center" x-text="totalAverage() ?? '—'"></td>
                            </tr>
                            <tr class="bg-gray-50 font-semibold">
                                <td class="border px-3 py-2">Score Interpretation</td>
                                <td class="border px-3 py-2 text-center" x-text="interpretation(totalAverage()) || '—'"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Recommendations</label>
                    <textarea x-model="doc8.recommendations" rows="3" class="w-full rounded border px-3 py-2 text-sm"></textarea>
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6">
                    <p class="mb-3 text-sm font-semibold text-gray-700">Validated By:</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div>
                            <p class="mb-1 text-xs text-gray-500">Name</p>
                            <input type="text" x-model="doc8.validated_by_name"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <p class="mb-1 text-xs text-gray-500">Position / Affiliation</p>
                            <input type="text" x-model="doc8.validated_by_position"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <p class="mb-1 text-xs text-gray-500">Contact No.</p>
                            <input type="text" x-model="doc8.validated_by_contact"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <p class="mb-1 text-xs text-gray-500">Date</p>
                            <input type="date" x-model="doc8.validated_by_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <p class="mb-2 text-sm font-semibold text-gray-700">Noted By:</p>
                            <input type="text" x-model="doc8.noted_by_name"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <input type="text" x-model="doc8.noted_by_position"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-semibold text-gray-700">Approved By:</p>
                            <input type="text" x-model="doc8.approved_by_name"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <textarea x-model="doc8.approved_by_position" rows="2"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <button type="button" @click="showClearConfirm = true" :disabled="! isDirty()"
                class="h-11 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-white sm:flex-1">
                Clear Form
            </button>
            <button type="submit" :disabled="! isDirty()"
                class="flex h-11 w-full items-center justify-center gap-2 rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40 sm:flex-1">
                <img src="{{ asset('images/icons/save.svg') }}" alt="" class="h-4 w-4 brightness-0 invert">
                Save Assessment
            </button>
        </div>
    </form>

    {{-- ============ Clear Form confirmation ============ --}}
    <div x-show="showClearConfirm" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none;">
        <div class="relative w-full max-w-lg rounded-2xl bg-white px-5 pb-5 pt-8 text-center shadow-2xl sm:px-6">
            <button type="button" @click="showClearConfirm = false"
                class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full border border-gray-900 text-gray-900 transition hover:border-transparent hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white"
                aria-label="Close">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>

            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A]">
                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                </svg>
            </div>

            <h2 class="mt-2.5 bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-base font-bold text-transparent sm:text-lg">Clear Form</h2>
            <p class="mt-1.5 text-xs leading-5 text-gray-600">Are you sure you want to clear this form?</p>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4">
                <button type="button" @click="showClearConfirm = false"
                    class="h-10 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" @click="clearAll()"
                    class="h-10 w-full rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95">
                    Yes
                </button>
            </div>
        </div>
    </div>

    {{-- ============ Save success ============ --}}
    <div x-show="showSaved" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none;">
        <div class="relative w-full max-w-lg overflow-hidden rounded-xl bg-white">
            <div class="flex items-center justify-center bg-gradient-to-r from-rose-950 to-blue-950 py-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white">
                    <svg class="h-8 w-8 text-[#11386A]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <div class="p-8 text-center">
                <h3 class="mb-2 text-2xl font-bold text-gray-900">Great!</h3>
                <p class="mb-6 text-gray-500">Changes saved successfully.</p>
                <button type="button" @click="showSaved = false"
                    class="w-full rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-3 font-semibold text-white transition hover:opacity-90">
                    Continue
                </button>
            </div>
        </div>
    </div>
</div>
