{{--
    Export Document / Export Configuration / Generating / Completed modal
    flow. Self-contained Alpine component — opened by dispatching a window
    event ("open-export-modal") from the "Export Document" button in
    index.blade.php. Uses $assessableStartups (already passed to the parent
    view by AssessmentHubController) for the startup dropdown.
--}}
@php
    $exportDocumentList = [
        1 => 'Startup Information Sheet',
        2 => 'Pre-Assessment TRL',
        3 => 'Pre-Assessment MRL',
        4 => 'Pre-Assessment TMRL',
        5 => 'Pre-Assessment SRL',
        6 => 'Startup Growth Strategy',
        7 => 'Weekly Check-Ins',
        8 => 'Prototype Validation Form',
        9 => 'Post-Assessment TRL',
        10 => 'Post-Assessment MRL',
        11 => 'Post-Assessment TMRL',
        12 => 'Post-Assessment SRL',
        13 => 'Startup Exit Form',
    ];
@endphp

<div
    x-data="{
        open: false,
        step: 'select',
        startups: @js($assessableStartups->map(fn ($s) => ['id' => $s->startup_id, 'name' => $s->company_name])->values()),
        documents: @js(collect($exportDocumentList)->map(fn ($label, $num) => ['number' => $num, 'label' => $label])->values()),
        startupId: null,
        selectedDocs: [],
        format: 'PDF Bundle',
        fileName: '',
        progress: 0,
        result: null,
        error: null,
        savingToReports: false,
        savedToReports: false,

        get startupName() {
            const s = this.startups.find(s => s.id === this.startupId);
            return s ? s.name : '';
        },

        openModal() {
            this.step = 'select';
            this.startupId = this.startups.length ? this.startups[0].id : null;
            this.selectedDocs = this.documents.map(d => d.number);
            this.format = 'PDF Bundle';
            this.fileName = '';
            this.result = null;
            this.error = null;
            this.savingToReports = false;
            this.savedToReports = false;
            this.open = true;
        },

        closeModal() {
            this.open = false;
        },

        toggleDoc(num) {
            this.selectedDocs = this.selectedDocs.includes(num)
                ? this.selectedDocs.filter(n => n !== num)
                : [...this.selectedDocs, num];
        },

        toggleAll() {
            this.selectedDocs = this.selectedDocs.length === this.documents.length
                ? []
                : this.documents.map(d => d.number);
        },

        goToConfigure() {
            if (!this.startupId || this.selectedDocs.length === 0) return;
            const today = new Date().toISOString().slice(0, 10);
            this.fileName = `${this.startupName} - Export - ${today}`;
            this.error = null;
            this.step = 'configure';
        },

        get estimatedPages() {
            // Rough, honest estimate for the configuration screen only —
            // the real page count is computed server-side once the PDF is
            // actually generated (shown on the Completed screen instead).
            return this.selectedDocs.length * 3;
        },

        get estimatedSizeLabel() {
            const kb = this.selectedDocs.length * 120;
            return kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
        },

        csrfToken() {
            return document.getElementById('export-modal-csrf').value;
        },

        async generate() {
            if (!this.fileName.trim()) return;

            this.step = 'generating';
            this.progress = 0;
            this.error = null;

            const timer = setInterval(() => {
                if (this.progress < 90) this.progress += Math.ceil(Math.random() * 12);
            }, 200);

            try {
                const response = await fetch('{{ route('admin.exports.generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        startup_id: this.startupId,
                        document_numbers: this.selectedDocs,
                        format: this.format,
                        file_name: this.fileName,
                    }),
                });

                if (!response.ok) {
                    const body = await response.json().catch(() => ({}));
                    throw new Error(body.message || 'Export failed. Please try again.');
                }

                this.result = await response.json();
                this.progress = 100;
                setTimeout(() => { this.step = 'completed'; }, 250);
            } catch (e) {
                this.error = e.message || 'Something went wrong generating the export.';
                this.step = 'configure';
            } finally {
                clearInterval(timer);
            }
        },

        async saveToReports() {
            if (!this.result || this.savingToReports || this.savedToReports) return;
            this.savingToReports = true;
            this.error = null;

            try {
                const response = await fetch('{{ route('admin.exports.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        startup_id: this.startupId,
                        export_batch: this.result.export_batch,
                        files: this.result.files,
                    }),
                });

                if (!response.ok) throw new Error('Could not save to Reports.');

                this.savedToReports = true;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.savingToReports = false;
            }
        },

        downloadAll() {
            (this.result?.files ?? []).forEach((file, i) => {
                setTimeout(() => window.open(file.download_url, '_blank'), i * 300);
            });
        },
    }"
    @open-export-modal.window="openModal()"
>
    <input type="hidden" id="export-modal-csrf" value="{{ csrf_token() }}">

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.outside="step !== 'generating' && closeModal()" class="w-full max-w-lg rounded-xl bg-white shadow-xl">

            {{-- Step 1: Export Document (choose startup + documents) --}}
            <template x-if="step === 'select'">
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900">Export Document</h2>
                        <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <label class="mb-1 block text-sm font-semibold text-gray-700">Choose a Startup</label>
                    <select x-model.number="startupId" class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-900 focus:outline-none">
                        <option :value="null" disabled>Select a startup&hellip;</option>
                        <template x-for="s in startups" :key="s.id">
                            <option :value="s.id" x-text="s.name"></option>
                        </template>
                    </select>

                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-sm font-semibold text-gray-700">Select Documents</label>
                        <button type="button" @click="toggleAll()" class="text-xs font-semibold text-rose-900 hover:underline"
                            x-text="selectedDocs.length === documents.length ? 'Deselect All' : 'Select All'"></button>
                    </div>

                    <div class="mb-5 max-h-64 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2">
                        <template x-for="doc in documents" :key="doc.number">
                            <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50">
                                <input type="checkbox" :checked="selectedDocs.includes(doc.number)" @change="toggleDoc(doc.number)"
                                    class="rounded border-gray-300 text-rose-900 focus:ring-rose-900">
                                <span x-text="doc.label"></span>
                            </label>
                        </template>
                    </div>

                    <p x-show="error" x-text="error" class="mb-3 text-sm text-red-600"></p>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="goToConfigure()" :disabled="!startupId || selectedDocs.length === 0"
                            class="rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-40">
                            Export Selected
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step 2: Export Configuration --}}
            <template x-if="step === 'configure'">
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900">Export Configuration</h2>
                        <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <label class="mb-1 block text-sm font-semibold text-gray-700">Export Format</label>
                    <div class="mb-4 grid grid-cols-1 gap-2">
                        <template x-for="opt in ['PDF Bundle', 'ZIP Archive', 'Individual PDFs']" :key="opt">
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                                :class="format === opt ? 'border-rose-900 bg-rose-50' : 'border-gray-200'">
                                <input type="radio" :value="opt" x-model="format" class="text-rose-900 focus:ring-rose-900">
                                <span x-text="opt"></span>
                            </label>
                        </template>
                    </div>

                    <div class="mb-4 space-y-1 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                        <div class="flex justify-between"><span>Documents Selected</span><span x-text="selectedDocs.length + ' of ' + documents.length"></span></div>
                        <div class="flex justify-between"><span>Estimated Pages</span><span x-text="estimatedPages"></span></div>
                        <div class="flex justify-between"><span>Estimated Size</span><span x-text="estimatedSizeLabel"></span></div>
                    </div>

                    <label class="mb-1 block text-sm font-semibold text-gray-700">File Name</label>
                    <input type="text" x-model="fileName" class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-rose-900 focus:outline-none">

                    <p x-show="error" x-text="error" class="mb-3 text-sm text-red-600"></p>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="step = 'select'"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</button>
                        <button type="button" @click="generate()" :disabled="!fileName.trim()"
                            class="rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-40">
                            Generate Export
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step 3: Generating --}}
            <template x-if="step === 'generating'">
                <div class="p-8 text-center">
                    <h2 class="mb-6 text-lg font-bold text-gray-900">Generating Export Documents&hellip;</h2>
                    <div class="mx-auto mb-3 h-2 w-full max-w-xs overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] transition-all" :style="`width: ${progress}%`"></div>
                    </div>
                    <p class="text-sm text-gray-500" x-text="progress + '%'"></p>
                </div>
            </template>

            {{-- Step 4: Export Completed --}}
            <template x-if="step === 'completed' && result">
                <div class="p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-600">&check;</span>
                        <h2 class="text-lg font-bold text-gray-900">Export Completed</h2>
                    </div>

                    <div class="mb-4 max-h-56 space-y-2 overflow-y-auto">
                        <template x-for="file in result.files" :key="file.file_path">
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 text-sm">
                                <div>
                                    <div class="font-semibold text-gray-800" x-text="file.file_name"></div>
                                    <div class="text-xs text-gray-500">
                                        <span x-text="(file.page_count ?? '—') + ' pages'"></span> &middot;
                                        <span x-text="file.file_size_label"></span>
                                    </div>
                                </div>
                                <a :href="file.download_url" target="_blank"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    Download
                                </a>
                            </div>
                        </template>
                    </div>

                    <p x-show="error" x-text="error" class="mb-3 text-sm text-red-600"></p>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Close</button>

                        <button type="button" @click="downloadAll()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Download File<span x-show="result.files.length > 1">s</span>
                        </button>

                        <button type="button" @click="saveToReports()" :disabled="savingToReports || savedToReports"
                            class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                            :class="savedToReports || savingToReports
                                ? 'bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white opacity-90'
                                : 'border border-gray-300 text-gray-700 hover:bg-gray-50'">
                            <span x-show="!savingToReports && !savedToReports">Save to Reports</span>
                            <span x-show="savingToReports">Saving&hellip;</span>
                            <span x-show="savedToReports">&check; Saved to Reports</span>
                        </button>
                    </div>
                </div>
            </template>

        </div>
    </div>
</div>
