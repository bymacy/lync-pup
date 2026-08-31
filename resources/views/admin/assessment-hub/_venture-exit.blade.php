@php
    $veData = $ventureExitDocument->data ?? [];

    $veSeed = [
        'startup_name' => $veData['startup_name'] ?? $selectedStartup->company_name,
        'date_of_assessment' => $veData['date_of_assessment'] ?? now()->format('Y-m-d'),
        'business_stage' => array_merge(
            array_fill_keys(\App\Support\VentureExitForm::BUSINESS_STAGES, false),
            $veData['business_stage'] ?? []
        ),
        'graduation_readiness' => [],
        'summary_of_progress' => $veData['summary_of_progress'] ?? '',
        'post_incubation_recommendation' => $veData['post_incubation_recommendation'] ?? '',
        'scale_up_linkages' => $veData['scale_up_linkages'] ?? '',
        'readiness_levels' => [],
    ];

    foreach (\App\Support\VentureExitForm::GRADUATION_READINESS_INDICATORS as $indicator) {
        $storedRow = $veData['graduation_readiness'][$indicator] ?? [];
        $veSeed['graduation_readiness'][$indicator] = [
            'status' => (bool) ($storedRow['status'] ?? false),
            'remark' => $storedRow['remark'] ?? '',
        ];
    }

    // "Highest Level" prefills from this startup's saved Post-Assessment
    // scores the first time this form is opened — once saved (even
    // unchanged), the saved value takes over and stays freely editable.
    foreach (\App\Support\ReadinessRubric::TYPES as $type) {
        $storedRow = $veData['readiness_levels'][$type] ?? null;
        $postScore = $postAssessmentSummary?->scoreFor($type);
        $defaultHighest = $postScore !== null ? $postScore.'/9' : '';

        $veSeed['readiness_levels'][$type] = [
            'highest_level' => $storedRow['highest_level'] ?? $defaultHighest,
            'remarks' => $storedRow['remarks'] ?? '',
        ];
    }

    // Venture Exit's own signatory block — editable-but-prefilled the
    // same way as the assessment form's own signatory blocks. Noted by
    // has no title/position, per how it was given.
    $veSeed['evaluated_by_name'] = $veData['evaluated_by_name'] ?? '';
    $veSeed['evaluated_by_position'] = $veData['evaluated_by_position'] ?? 'Portfolio Coordinator, TBIDO';
    $veSeed['reviewed_by_name'] = $veData['reviewed_by_name'] ?? '';
    $veSeed['reviewed_by_position'] = $veData['reviewed_by_position'] ?? 'Incubation Management Chief, TBIDO';
    $veSeed['noted_by_name'] = $veData['noted_by_name'] ?? '';
    $veSeed['noted_by_position'] = $veData['noted_by_position'] ?? 'Director, TBIDO';
@endphp

<div
    x-data="{
        ve: @js($veSeed),
        initialVe: @js($veSeed),
        showClearConfirm: false,
        showSaved: @js($justSaved ?? false),
        incompleteAssessments: @js($incompleteAssessments ?? []),
        showIncompleteConfirm: false,
        confirmedIncomplete: false,
        aiGenerating: false,
        aiError: '',
        // Generate with AI — drafts the Graduation Readiness
        // Status+Remark, the three Final Evaluation textareas, and the
        // Post Program Readiness Level remarks (never Highest Level,
        // which prefills separately from saved Post-Assessment scores).
        // Purely a first draft: it only fills the in-memory form state
        // below, which stays fully editable and isn't saved until the
        // admin clicks Save Assessment same as any other edit.
        async generateWithAi() {
            this.aiError = '';
            this.aiGenerating = true;
            try {
                const response = await fetch('{{ route('admin.assessment-hub.assessments.venture-exit.generate-ai', $selectedStartup) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content ?? '',
                        'Accept': 'application/json',
                    },
                });
                const body = await response.json().catch(() => ({}));
                if (! response.ok) {
                    throw new Error(body.message || 'Something went wrong generating content. Please try again.');
                }
                const data = body.data;
                Object.keys(this.ve.graduation_readiness).forEach(key => {
                    const row = data.graduation_readiness?.[key];
                    if (row) {
                        this.ve.graduation_readiness[key].status = !!row.status;
                        this.ve.graduation_readiness[key].remark = row.remark || '';
                    }
                });
                this.ve.summary_of_progress = data.summary_of_progress || '';
                this.ve.post_incubation_recommendation = data.post_incubation_recommendation || '';
                this.ve.scale_up_linkages = data.scale_up_linkages || '';
                Object.keys(this.ve.readiness_levels).forEach(key => {
                    const row = data.readiness_levels?.[key];
                    if (row) {
                        this.ve.readiness_levels[key].remarks = row.remarks || '';
                    }
                });
            } catch (e) {
                this.aiError = e.message || 'Something went wrong generating content. Please try again.';
            } finally {
                this.aiGenerating = false;
            }
        },
        isDirty() {
            return JSON.stringify(this.ve) !== JSON.stringify(this.initialVe);
        },
        // Gate the actual submit: if this startup still has other
        // assessments/documents that were never started, ask once before
        // proceeding instead of silently exiting them. Already-complete
        // startups submit immediately, same as before.
        trySubmit(event) {
            if (this.incompleteAssessments.length && ! this.confirmedIncomplete) {
                event.preventDefault();
                this.showIncompleteConfirm = true;
            }
        },
        proceedAnyway() {
            this.confirmedIncomplete = true;
            this.showIncompleteConfirm = false;
            this.$nextTick(() => document.getElementById('venture-exit-form').requestSubmit());
        },
        clearAll() {
            // Startup Name is intentionally left untouched — clearing the
            // form should not wipe out which startup this exit form is
            // for. Every other field is reset explicitly below instead of
            // calling the native form.reset(), which would blank the
            // Startup Name input too (it has no default value attribute).
            this.ve.date_of_assessment = '';
            Object.keys(this.ve.business_stage).forEach(k => this.ve.business_stage[k] = false);
            Object.keys(this.ve.graduation_readiness).forEach(k => {
                this.ve.graduation_readiness[k].status = false;
                this.ve.graduation_readiness[k].remark = '';
            });
            this.ve.summary_of_progress = '';
            this.ve.post_incubation_recommendation = '';
            this.ve.scale_up_linkages = '';
            Object.keys(this.ve.readiness_levels).forEach(k => {
                this.ve.readiness_levels[k].highest_level = '';
                this.ve.readiness_levels[k].remarks = '';
            });
            this.showClearConfirm = false;
        },
    }">

    <div class="rounded-t-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-3 text-center font-bold uppercase text-white">
        Startup Exit Form
    </div>

    <div class="rounded-b-lg border border-t-0 p-4 sm:p-6">
        <form method="POST" action="{{ route('admin.assessment-hub.assessments.update-documents', $selectedStartup) }}" id="venture-exit-form" @submit="trySubmit($event)">
            @csrf
            @method('PUT')
            <input type="hidden" name="stage" value="Venture Exit">
            <input type="hidden" name="document_13" :value="JSON.stringify(ve)">

            <p class="mb-6 text-center">
                <span class="rounded border border-rose-900 px-3 py-1 text-xs font-semibold italic text-rose-900">{{ \App\Support\VentureExitForm::FORM_NO }}</span>
            </p>

            <div class="mb-6 flex flex-col items-end gap-2">
                <button type="button" @click="generateWithAi()" :disabled="aiGenerating"
                    class="flex h-10 items-center gap-2 rounded-md border border-transparent bg-gradient-to-r from-emerald-600 to-teal-600 px-4 text-sm font-bold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60">
                    <svg x-show="! aiGenerating" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l1.8 5.2L19 9l-5.2 1.8L12 16l-1.8-5.2L5 9l5.2-1.8L12 2z" />
                        <path d="M19 14l.9 2.6L22 17.5l-2.1.9L19 21l-.9-2.6L16 17.5l2.1-.9L19 14z" opacity="0.7" />
                    </svg>
                    <svg x-show="aiGenerating" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
                    </svg>
                    <span x-text="aiGenerating ? 'Generating…' : 'Generate with AI'"></span>
                </button>
                <p class="max-w-md text-right text-xs italic text-gray-500">Drafts the checklist remarks, exit support plan, and readiness-level remarks below from this startup's assessment history. Review and edit before saving.</p>
                <p x-show="aiError" x-cloak x-text="aiError" class="max-w-md text-right text-xs text-red-600"></p>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Startup Name:</label>
                    <input type="text" x-model="ve.startup_name" class="w-full rounded border px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Date of Assessment:</label>
                    <input type="date" x-model="ve.date_of_assessment" class="w-full rounded border px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mb-6 flex flex-wrap gap-x-10 gap-y-2">
                <p class="font-semibold text-gray-700">Business Stage:</p>
                @foreach (\App\Support\VentureExitForm::BUSINESS_STAGES as $stageOption)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" x-model="ve.business_stage['{{ $stageOption }}']" class="h-4 w-4 rounded border-gray-300">
                        {{ $stageOption }}
                    </label>
                @endforeach
            </div>

            <div class="mb-8">
                <p class="mb-2 font-bold text-gray-900">(1) Graduation Readiness Assessment</p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] border text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border px-3 py-2 text-left">Indicator</th>
                                <th class="w-32 whitespace-nowrap border px-3 py-2 text-center">Status (&#10003; / &#10007;)</th>
                                <th class="border px-3 py-2 text-left">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Support\VentureExitForm::GRADUATION_READINESS_INDICATORS as $indicator)
                                <tr>
                                    <td class="border px-3 py-2">{{ $indicator }}</td>
                                    <td class="border px-3 py-2 text-center">
                                        <div class="inline-flex overflow-hidden rounded-full border border-gray-300">
                                            <button type="button" @click="ve.graduation_readiness['{{ $indicator }}'].status = true"
                                                class="flex h-8 w-9 items-center justify-center border-r border-gray-300 transition"
                                                :class="ve.graduation_readiness['{{ $indicator }}'].status ? 'bg-green-600 text-white' : 'bg-white text-gray-300 hover:bg-green-50 hover:text-green-600'"
                                                aria-label="Mark met">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="ve.graduation_readiness['{{ $indicator }}'].status = false"
                                                class="flex h-8 w-9 items-center justify-center transition"
                                                :class="! ve.graduation_readiness['{{ $indicator }}'].status ? 'bg-rose-600 text-white' : 'bg-white text-gray-300 hover:bg-rose-50 hover:text-rose-600'"
                                                aria-label="Mark not met">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="border p-1">
                                        <input type="text" x-model="ve.graduation_readiness['{{ $indicator }}'].remark"
                                            class="w-full rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-rose-900">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-8">
                <p class="mb-2 font-bold text-gray-900">(2) Final Evaluation and Exit Support Plan</p>
                <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] border text-sm">
                    <tbody>
                        <tr>
                            <td class="w-56 border bg-gray-50 px-3 py-2 align-top font-semibold">Summary of Startup Progress</td>
                            <td class="border p-1">
                                <textarea x-model="ve.summary_of_progress" rows="3" class="w-full rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-rose-900"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="border bg-gray-50 px-3 py-2 align-top font-semibold">Post Incubation Recommendation</td>
                            <td class="border p-1">
                                <textarea x-model="ve.post_incubation_recommendation" rows="3" class="w-full rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-rose-900"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="border bg-gray-50 px-3 py-2 align-top font-semibold">Scale Up Linkages</td>
                            <td class="border p-1">
                                <textarea x-model="ve.scale_up_linkages" rows="3" class="w-full rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-rose-900"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mb-2">
                <p class="mb-2 font-bold text-gray-900">(3) Post Program Readiness Level</p>
                <p class="mb-2 text-xs italic text-gray-500">Highest Level prefills from this startup's saved Post-Assessment scores, but can be edited.</p>
                <div class="overflow-x-auto">
                    <table class="w-full border text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="border px-3 py-2 text-left">Readiness Level</th>
                                <th class="w-40 border px-3 py-2 text-left">Highest Level</th>
                                <th class="border px-3 py-2 text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Support\ReadinessRubric::TYPES as $type)
                                <tr>
                                    <td class="border px-3 py-2 font-semibold">{{ $rubricMeta[$type]['label'] }} ({{ $type }})</td>
                                    <td class="border p-1">
                                        <input type="text" x-model="ve.readiness_levels.{{ $type }}.highest_level"
                                            class="w-full rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-rose-900">
                                    </td>
                                    <td class="border p-1">
                                        <input type="text" x-model="ve.readiness_levels.{{ $type }}.remarks"
                                            class="w-full rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-rose-900">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-6 border-t border-gray-200 pt-6 sm:grid-cols-3">
                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-700">Evaluated by:</p>
                    <input type="text" x-model="ve.evaluated_by_name" placeholder="Input Name"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <input type="text" x-model="ve.evaluated_by_position"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-700">Reviewed by:</p>
                    <input type="text" x-model="ve.reviewed_by_name" placeholder="Input Name"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <input type="text" x-model="ve.reviewed_by_position"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-700">Noted by:</p>
                    <input type="text" x-model="ve.noted_by_name" placeholder="Input Name"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <input type="text" x-model="ve.noted_by_position"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-500">
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
    </div>

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

    {{-- ============ Incomplete assessments confirmation ============ --}}
    <div x-show="showIncompleteConfirm" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none;">
        <div class="relative w-full max-w-lg rounded-2xl bg-white px-5 pb-5 pt-8 text-center shadow-2xl sm:px-6">
            <button type="button" @click="showIncompleteConfirm = false"
                class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full border border-gray-900 text-gray-900 transition hover:border-transparent hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white"
                aria-label="Close">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                </svg>
            </button>

            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A]">
                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0Z" />
                </svg>
            </div>

            <h2 class="mt-2.5 bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-base font-bold text-transparent sm:text-lg">Incomplete Assessments</h2>
            <p class="mt-1.5 text-xs leading-5 text-gray-600">The following assessment(s) have not been started yet:</p>

            <ul class="mx-auto mt-3 max-w-xs list-inside list-disc space-y-1 text-left text-xs text-gray-700">
                <template x-for="item in incompleteAssessments" :key="item">
                    <li x-text="item"></li>
                </template>
            </ul>

            <p class="mt-3 text-xs leading-5 text-gray-600">Do you want to proceed anyway?</p>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-4">
                <button type="button" @click="showIncompleteConfirm = false"
                    class="h-10 w-full rounded-md border border-gray-300 bg-white text-sm font-bold text-gray-800 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" @click="proceedAnyway()"
                    class="h-10 w-full rounded-md bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95">
                    Proceed Anyway
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
