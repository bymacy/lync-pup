<x-layouts.founder>
    <div x-data="{
        tab: '{{ request('tab', 'roadblock') }}',
        category: '',
        categoryOther: '',
        otherSuggestions: @js($otherCategorySuggestions),
        showOtherSuggestions: false,
        description: '',
        files: [],
        dt: new DataTransfer(),
        dragOver: false,
        showConfirm: false,
        showSuccess: {{ session('roadblock_submitted') ? 'true' : 'false' }},
        activeRoadblock: null,
        previewImageUrl: null,

        /* ---------- validation state ---------- */
        errors: {},
        touched: {},
        fileError: '',

        limits: {
            maxFiles: 5,
            maxBytes: 5 * 1024 * 1024,
            accept: ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
        },

        get descriptionLeft() {
            return 5000 - this.description.trim().length;
        },

        /* ---------- 'Others' predictive suggestions ----------
           Pulled from every startup's past 'Others' submissions (server-side),
           filtered here as the user types so they can reuse an existing
           category label instead of creating near-duplicate free text. */
        get filteredOtherSuggestions() {
            const val = this.categoryOther.trim().toLowerCase();
            const pool = val
                ? this.otherSuggestions.filter(s => s.toLowerCase().includes(val) && s.toLowerCase() !== val)
                : this.otherSuggestions;
            return pool.slice(0, 6);
        },

        pickOtherSuggestion(value) {
            this.categoryOther = value;
            this.showOtherSuggestions = false;
            this.touched.categoryOther = true;
            this.validateField('categoryOther');
        },

        validateField(field) {
            const errs = { ...this.errors };
            delete errs[field];

            const check = {
                category: () => {
                    if (!this.category) return 'Choose a problem category.';
                },
                categoryOther: () => {
                    if (this.category !== 'Others') return;
                    const val = this.categoryOther.trim();
                    if (!val) return 'Name the specific roadblock.';
                    if (val.length < 3) return 'Use at least 3 characters.';
                    if (val.length > 100) return 'Keep it under 100 characters.';
                },
                description: () => {
                    const val = this.description.trim();
                    if (!val) return 'Describe your roadblock.';
                    if (val.length < 20) return `Add more detail — ${20 - val.length} more characters.`;
                    if (val.length > 5000) return 'Keep it under 5000 characters.';
                },
            }[field];

            const msg = check?.();
            if (msg) errs[field] = msg;

            this.errors = errs;
            return !msg;
        },

        validateAll() {
            ['category', 'categoryOther', 'description'].forEach(field => {
                this.touched[field] = true;
                this.validateField(field);
            });

            return Object.keys(this.errors).length === 0;
        },

        get isValid() {
            // Note: fileError only ever describes a *rejected* add attempt (over the
            // cap, wrong type, too large, duplicate) — the file is never actually
            // attached in those cases, so the current file list is always valid on
            // its own. It must not block submitting the files that did get attached.
            return this.category
                && (this.category !== 'Others' || this.categoryOther.trim().length >= 3)
                && this.description.trim().length >= 20
                && this.description.trim().length <= 5000;
        },

        /* ---------- files ---------- */
        addFiles(fileList) {
            this.fileError = '';
            const existing = Array.from(this.dt.files).map(f => f.name + f.size);

            Array.from(fileList).forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();

                if (this.dt.files.length >= this.limits.maxFiles) {
                    this.fileError = `Attach up to ${this.limits.maxFiles} files.`;
                    return;
                }
                if (!this.limits.accept.includes(ext)) {
                    this.fileError = `${file.name} isn't a supported file type.`;
                    return;
                }
                if (file.size > this.limits.maxBytes) {
                    this.fileError = `${file.name} is larger than 5MB.`;
                    return;
                }
                if (existing.includes(file.name + file.size)) {
                    this.fileError = `${file.name} is already attached.`;
                    return;
                }

                this.dt.items.add(file);
                existing.push(file.name + file.size);
            });

            this.syncInput();

            // This is feedback about a rejected attempt, not a lasting problem with
            // the attached files — fade it out on its own instead of leaving it
            // stuck on screen until the next add/remove action.
            if (this.fileError) {
                setTimeout(() => { this.fileError = ''; }, 4000);
            }
        },

        removeFile(index) {
            const newDt = new DataTransfer();
            Array.from(this.dt.files).forEach((file, i) => {
                if (i !== index) newDt.items.add(file);
            });
            this.dt = newDt;
            this.fileError = '';
            this.syncInput();
        },

        syncInput() {
            this.$refs.fileInput.files = this.dt.files;
            this.files = Array.from(this.dt.files).map(file => ({
                name: file.name,
                isImage: file.type.startsWith('image/'),
                url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
            }));
        },

        resetForm() {
            this.category = '';
            this.categoryOther = '';
            this.showOtherSuggestions = false;
            this.description = '';
            this.dt = new DataTransfer();
            this.files = [];
            this.$refs.fileInput.files = this.dt.files;
            this.errors = {};
            this.touched = {};
            this.fileError = '';
        },
    }">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Submit Roadblock</h1>
            <p class="text-gray-600 mt-1">Submit your roadblock below, and our team will assign a mentor to help you.</p>
        </div>

        <div class="border-b border-gray-200 mb-6">
            <nav class="flex gap-8">
                <button type="button" @click="tab = 'roadblock'" :class="tab === 'roadblock' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-3 border-b-2 font-medium">Roadblock</button>
                <button type="button" @click="tab = 'update'" :class="tab === 'update' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-3 border-b-2 font-medium">Update</button>
                <button type="button" @click="tab = 'archive'" :class="tab === 'archive' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-3 border-b-2 font-medium">Archive</button>
            </nav>
        </div>

        {{-- ============================================================
             Roadblock tab
             ============================================================ --}}
        <div x-show="tab === 'roadblock'">

            <div class="flex items-center gap-2.5 mb-5">
                <span class="text-rose-900"><x-icon name="warning.svg" class="w-10 h-10" /></span>
                <h2 class="font-semibold text-lg text-gray-900">Roadblock Submission</h2>
            </div>

            <form x-ref="form" method="POST" action="{{ route('startup.submissions.store') }}"
                enctype="multipart/form-data" class="space-y-4">
                @csrf

                {{-- Problem Category --}}
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Problem Category</label>

                    {{-- Custom listbox: native <option> can't take a gradient, so the popup is ours.
                         The real value rides on a hidden input bound to the same x-model. --}}
                    <div class="relative max-w-md"
                        x-data="{
                            open: false,
                            highlighted: -1,
                            options: ['Business Development', 'Technical Support', 'Market Research', 'Strategy Consultant', 'Others'],

                            toggle() {
                                this.open = !this.open;
                                this.highlighted = this.open ? this.options.indexOf(this.category) : -1;
                                if (!this.open) this.blurValidate();
                            },

                            choose(option) {
                                this.category = option;
                                this.open = false;
                                this.blurValidate();
                                this.validateField('categoryOther');
                                this.$refs.trigger.focus();
                            },

                            blurValidate() {
                                this.touched.category = true;
                                this.validateField('category');
                            },

                            move(step) {
                                if (!this.open) { this.toggle(); return; }
                                const count = this.options.length;
                                this.highlighted = (this.highlighted + step + count) % count;
                            },
                        }"
                        @click.outside="open = false; blurValidate()"
                        @keydown.escape.window="open = false">

                        <input type="hidden" name="problem_category" :value="category">

                        <button type="button"
                            x-ref="trigger"
                            @click="toggle()"
                            @keydown.arrow-down.prevent="move(1)"
                            @keydown.arrow-up.prevent="move(-1)"
                            @keydown.enter.prevent="open && highlighted > -1 ? choose(options[highlighted]) : toggle()"
                            :aria-expanded="open"
                            aria-haspopup="listbox"
                            class="w-full flex items-stretch rounded-lg border overflow-hidden text-left
                                   transition focus:outline-none focus:ring-2"
                            :class="errors.category && touched.category
                                ? 'border-red-400 focus:border-red-400 focus:ring-red-100'
                                : (open
                                    ? 'border-rose-400 ring-2 ring-rose-100'
                                    : 'border-rose-200 focus:border-rose-400 focus:ring-rose-100')">

                            <span class="w-11 flex-shrink-0 flex items-center justify-center border-r"
                                :class="errors.category && touched.category
                                    ? 'bg-red-50 border-red-200 text-red-700'
                                    : 'bg-rose-50 border-rose-200 text-rose-800'">
                                <x-icon name="warning-round.svg" class="w-5 h-5" />
                            </span>

                            <span class="flex-1 px-3 py-2.5 text-sm"
                                :class="category ? 'text-gray-900' : 'text-gray-400'"
                                x-text="category || 'Type of roadblock'"></span>

                            <span class="pr-3 flex items-center">
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </button>

                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            role="listbox"
                            class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">

                            <template x-for="(option, index) in options" :key="option">
                                <button type="button"
                                    role="option"
                                    :aria-selected="category === option"
                                    @click="choose(option)"
                                    @mouseenter="highlighted = index"
                                    class="w-full px-4 py-2.5 text-left text-sm transition-colors"
                                    :class="highlighted === index
                                        ? 'bg-gradient-to-r from-rose-900 to-blue-950 text-white'
                                        : (category === option ? 'bg-rose-50 text-rose-900 font-medium' : 'text-gray-700')"
                                    x-text="option"></button>
                            </template>
                        </div>
                    </div>

                    <p x-show="errors.category && touched.category" x-cloak
                        class="mt-1.5 text-xs text-red-600" x-text="errors.category"></p>

                    {{-- "Others" free-text --}}
                    <div x-show="category === 'Others'" x-cloak class="mt-4 max-w-md">
                        <label class="block text-xs text-gray-500 mb-1.5">Type specific roadblock (e.g., Legal Counseling)</label>

                        <div class="relative">
                            <input type="text" name="problem_category_other" x-model="categoryOther"
                                maxlength="100"
                                autocomplete="off"
                                @focus="showOtherSuggestions = true"
                                @blur="touched.categoryOther = true; validateField('categoryOther'); setTimeout(() => showOtherSuggestions = false, 150)"
                                @input="showOtherSuggestions = true; touched.categoryOther && validateField('categoryOther')"
                                placeholder="Enter here..."
                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:ring-2 focus:outline-none"
                                :class="errors.categoryOther && touched.categoryOther
                                    ? 'border-red-400 focus:border-red-400 focus:ring-red-100'
                                    : 'border-rose-200 focus:border-rose-400 focus:ring-rose-100'">

                            {{-- Predictive suggestions from other startups' past "Others" entries --}}
                            <div x-show="showOtherSuggestions && filteredOtherSuggestions.length" x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                role="listbox"
                                class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg">

                                <template x-for="suggestion in filteredOtherSuggestions" :key="suggestion">
                                    <button type="button"
                                        role="option"
                                        @mousedown.prevent="pickOtherSuggestion(suggestion)"
                                        class="w-full px-3 py-2 text-left text-sm text-gray-700 transition-colors hover:bg-rose-50 hover:text-rose-900"
                                        x-text="suggestion"></button>
                                </template>
                            </div>
                        </div>

                        <p x-show="errors.categoryOther && touched.categoryOther" x-cloak
                            class="mt-1.5 text-xs text-red-600" x-text="errors.categoryOther"></p>
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-white border border-gray-300 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Describe your roadblock</label>

                    <div class="flex items-stretch rounded-lg border overflow-hidden transition focus-within:ring-2"
                        :class="errors.description && touched.description
                            ? 'border-red-400 focus-within:border-red-400 focus-within:ring-red-100'
                            : 'border-rose-200 focus-within:border-rose-400 focus-within:ring-rose-100'">

                        <div class="w-11 flex-shrink-0 flex items-center justify-center border-r"
                            :class="errors.description && touched.description
                                ? 'bg-red-50 border-red-200 text-red-700'
                                : 'bg-rose-50 border-rose-200 text-rose-800'">
                            <x-icon name="mail.svg" class="w-5 h-5" />
                        </div>

                        <textarea name="description" x-model="description" rows="5" maxlength="5000"
                            @blur="touched.description = true; validateField('description')"
                            @input="touched.description && validateField('description')"
                            placeholder="Enter details about your roadblock..."
                            class="flex-1 border-0 px-3 py-2.5 text-sm resize-y
                                   placeholder:text-gray-400 focus:outline-none focus:ring-0"></textarea>
                    </div>

                    <div class="mt-2 flex items-start justify-between gap-4">
                        <p class="text-xs"
                            :class="errors.description && touched.description ? 'text-red-600' : 'text-gray-500'"
                            x-text="(errors.description && touched.description)
                                ? errors.description
                                : 'Provide enough detail for the mentor to understand the situation.'"></p>

                        <span class="shrink-0 text-xs tabular-nums"
                            :class="descriptionLeft < 100 ? 'text-amber-600' : 'text-gray-400'"
                            x-text="`${descriptionLeft} left`"></span>
                    </div>
                </div>

                {{-- Supporting Files --}}
                <div class="bg-white border border-gray-300 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Supporting Files</label>

                    <div class="flex items-stretch max-w-md">
                        <div class="w-11 flex-shrink-0 rounded-l-lg bg-rose-50 border border-rose-200
                                    flex items-center justify-center text-rose-800">
                            <x-icon name="cam.svg" class="w-5 h-5" />
                        </div>

                        {{-- border-l-0 so the dashed edge doesn't double up against the icon strip --}}
                        <div
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="dragOver = false; addFiles($event.dataTransfer.files)"
                            :class="fileError
                                ? 'border-red-400 bg-red-50/60'
                                : (dragOver ? 'border-rose-500 bg-rose-100' : 'border-rose-200 bg-rose-50/60')"
                            class="flex-1 border-2 border-l-0 border-dashed rounded-r-lg px-4 py-5 text-center transition">

                            <svg class="w-5 h-5 mx-auto mb-1.5 text-rose-800" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-.41-8.98 4.5 4.5 0 0 1 8.08-3.32 3 3 0 0 1 3.76 3.87 4.5 4.5 0 0 1-.44 8.43H6.75Z" />
                            </svg>

                            <p class="text-xs text-gray-600 mb-2.5">Drag-and-drop</p>

                            <button type="button" @click="$refs.fileInput.click()"
                                class="bg-rose-900 hover:bg-rose-800 transition text-white text-xs font-medium px-4 py-1.5 rounded">
                                Browse Files
                            </button>

                            <input type="file" name="supporting_files[]" x-ref="fileInput" multiple class="hidden"
                                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv"
                                @change="addFiles($event.target.files)">
                        </div>
                    </div>

                    <p x-show="fileError" x-cloak class="mt-2 text-xs text-red-600 max-w-md" x-text="fileError"></p>

                    <p class="mt-2 text-xs text-gray-500 max-w-md"
                        x-text="`Up to ${limits.maxFiles} files, 5MB each. Images, PDF, Word, or Excel.`"></p>

                    <template x-if="files.length > 0">
                        <ul class="mt-4 space-y-2 max-w-md">
                            <template x-for="(file, index) in files" :key="index">
                                <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <template x-if="file.isImage">
                                            <img :src="file.url" class="w-9 h-9 object-cover rounded flex-shrink-0">
                                        </template>
                                        <template x-if="!file.isImage">
                                            <span class="w-9 h-9 flex-shrink-0 rounded bg-rose-50 text-rose-900 font-semibold
                                                         text-[10px] flex items-center justify-center"
                                                x-text="file.name.split('.').pop().toUpperCase()"></span>
                                        </template>
                                        <span x-text="file.name" class="text-sm text-gray-700 truncate"></span>
                                    </div>
                                    <button type="button" @click="removeFile(index)"
                                        class="flex-shrink-0 ml-3 text-gray-400 hover:text-rose-900 transition">&times;</button>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>

                {{-- Actions --}}
                <div class="flex gap-4 pt-1">
                    <button type="button" @click="resetForm()"
                        class="flex-1 border border-blue-950 text-blue-950 rounded-lg py-3 text-sm font-semibold
                               hover:bg-blue-50/50 transition">
                        Clear Form
                    </button>

                    <button type="button"
                        @click="validateAll() && (showConfirm = true)"
                        :disabled="!isValid"
                        :class="isValid ? 'hover:opacity-95' : 'opacity-50 cursor-not-allowed'"
                        class="flex-1 bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-lg py-3
                               text-sm font-semibold transition">
                        Submit Roadblock
                    </button>
                </div>
            </form>
        </div>

        {{-- Update tab — placeholder, separate module --}}
        <div x-show="tab === 'update'" class="text-gray-500 italic">
            Weekly Update submission — not built yet.
        </div>

        {{-- ============================================================
             Archive tab
             ============================================================ --}}
        <div x-show="tab === 'archive'" x-cloak>

            <div class="mb-4 flex items-center gap-3">
                <span class="icon-mask h-10 w-10 shrink-0 text-[#6D0D23]"
                    style="--icon: url('{{ asset('images/icons/submit-roadblock.svg') }}')"></span>
                <h2 class="text-base font-bold tracking-tight text-gray-900">Archive</h2>
            </div>

            @forelse ($roadblocks as $roadblock)
                <div class="mb-4 flex overflow-hidden rounded-lg border border-solid border-gray-200 bg-white">

                    {{-- Warning rail --}}
                    <div class="flex w-12 shrink-0 items-center justify-center bg-[#FFF1F2]">
                        <span class="icon-mask h-5 w-5 text-[#6D0D23]"
                            style="--icon: url('{{ asset('images/icons/warning.svg') }}')"></span>
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 px-5 py-4">

                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="mb-1 text-sm font-bold text-gray-900">Roadblock</p>

                                <p class="text-sm text-gray-700">
                                    <span class="font-semibold text-gray-900">Problem Category:</span>
                                    {{ $roadblock->display_category }}
                                </p>

                                <p class="mt-0.5 text-sm text-gray-600">
                                    Submitted on: {{ $roadblock->created_at->format('M d, Y') }}
                                    <span class="ml-4">Status:
                                        <span class="font-medium {{ $roadblock->status === 'Resolved' ? 'text-green-600' : 'text-amber-600' }}">
                                            {{ $roadblock->status }}
                                        </span>
                                    </span>
                                </p>
                            </div>

                            <button type="button"
                                @click="activeRoadblock = @js([
                                    'category' => $roadblock->display_category,
                                    'date' => $roadblock->created_at->format('M d, Y'),
                                    'status' => $roadblock->status,
                                    'description' => $roadblock->description,
                                    'files' => $roadblock->files->map(fn ($f) => [
                                        'name' => $f->original_filename,
                                        'url' => $f->url,
                                        'is_image' => $f->is_image,
                                    ]),
                                ])"
                                class="h-fit shrink-0 rounded-md border border-[#6D0D23] px-6 py-1.5 text-sm font-medium text-[#6D0D23] transition hover:bg-[#6D0D23] hover:text-white focus:outline-none">
                                View
                            </button>
                        </div>

                        {{-- Divider + excerpt --}}
                        <div class="mt-3 border-t border-gray-200 pt-3">
                            <p class="truncate text-sm text-gray-600">
                                {{ \Illuminate\Support\Str::limit($roadblock->description, 80) }}
                            </p>
                        </div>

                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 px-6 py-10 text-center">
                    <p class="text-sm text-gray-500">No roadblocks submitted yet.</p>
                </div>
            @endforelse
        </div>

        {{-- ============================================================
             Confirm submit modal
             ============================================================ --}}
        <div x-show="showConfirm" x-cloak
            x-transition.opacity.duration.200ms
            @keydown.escape.window="showConfirm = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

            <div @click.outside="showConfirm = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-lg rounded-2xl bg-white px-10 py-9 text-center shadow-2xl">

                {{-- Close --}}
                <button type="button" @click="showConfirm = false" aria-label="Close"
                    class="absolute right-5 top-5 flex h-6 w-6 items-center justify-center rounded-full border border-[#6D0D23] text-[#6D0D23] transition hover:bg-[#6D0D23] hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-[#6D0D23]/40">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="3" stroke-linecap="round">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>

                {{-- Icon --}}
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A]">
                    <span class="icon-mask h-8 w-8 text-white"
                        style="--icon: url('{{ asset('images/icons/submit-roadblock.svg') }}')"></span>
                </div>

                <h3 class="mb-3 text-2xl font-extrabold tracking-tight bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-transparent">
                    Submit Roadblock?
                </h3>

                <p class="mx-auto mb-8 max-w-sm text-sm leading-relaxed text-gray-600">
                    Are you sure you want to submit your roadblock details?
                    This will notify the team and initiate the mentor assignment process.
                </p>

                <div class="flex gap-4">
                    {{-- Cancel: gradient hairline border --}}
                    <div class="flex-1 rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] p-px">
                        <button type="button" @click="showConfirm = false"
                            class="h-full w-full rounded-[7px] bg-white py-2.5 text-sm font-bold text-[#11386A] transition hover:bg-gray-50 focus:outline-none">
                            Cancel
                        </button>
                    </div>

                    {{-- Submit --}}
                    <button type="button"
                        @click="if (validateAll()) { showConfirm = false; $refs.form.requestSubmit(); }"
                        class="flex-1 rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-2.5 text-sm font-bold text-white transition hover:opacity-90 focus:outline-none">
                        Submit
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             Success modal
             ============================================================ --}}
        <div x-show="showSuccess" x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">

            <div x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="w-full max-w-lg overflow-hidden rounded-2xl bg-white text-center shadow-2xl">

                {{-- Gradient header with check --}}
                <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-7">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm">
                        <svg class="h-7 w-7 text-[#6D0D23]" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <div class="px-8 pb-9 pt-7">
                    <h3 class="mb-2 text-3xl font-extrabold tracking-tight bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-transparent">
                        Great!
                    </h3>
                    <p class="mb-6 text-gray-600">Submission successful.</p>

                    <button type="button" @click="showSuccess = false; tab = 'archive'"
                        class="rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-12 py-2.5 text-sm font-bold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#11386A]/40">
                        Continue
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             Roadblock detail modal
             ============================================================ --}}
        <div x-show="activeRoadblock" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            @keydown.escape.window="activeRoadblock = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-6">

            <div @click.outside="activeRoadblock = null"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">

                {{-- Header (pinned) --}}
                <div class="flex shrink-0 items-center justify-between bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-6 py-4 text-white">
                    <div class="flex items-center gap-3">
                        <span class="icon-mask h-7 w-7 shrink-0 text-white"
                            style="--icon: url('{{ asset('images/icons/upcoming-mentorship.svg') }}')"></span>
                        <span class="text-lg font-bold tracking-tight">Roadblock</span>
                    </div>

                    <button type="button" @click="activeRoadblock = null" aria-label="Close"
                        class="flex h-6 w-6 items-center justify-center rounded-full border border-white/70 bg-white/10 transition hover:bg-white/25 focus:outline-none">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="3" stroke-linecap="round">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>

                {{-- Body (scrolls) --}}
                <div class="min-h-0 flex-1 overflow-y-auto p-6">

                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-900">Roadblock Details</h4>
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                            Status:
                            <span class="h-2 w-2 rounded-full"
                                :class="activeRoadblock && activeRoadblock.status === 'Resolved' ? 'bg-green-500' : 'bg-amber-500'"></span>
                            <span class="font-normal text-gray-600"
                                x-text="activeRoadblock ? activeRoadblock.status : ''"></span>
                        </span>
                    </div>

                    {{-- Detail card --}}
                    <div class="rounded-xl border border-gray-200 p-5">

                        <div class="mb-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-500">Roadblock Category</label>
                                <p class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-800"
                                    x-text="activeRoadblock ? activeRoadblock.category : ''"></p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-500">Date Submitted</label>
                                <p class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-800"
                                    x-text="activeRoadblock ? activeRoadblock.date : ''"></p>
                            </div>
                        </div>

                        {{-- Issue: clamped to 6 lines, expandable --}}
                        <div class="mb-4" x-data="{ expanded: false }">
                            <label class="mb-1.5 block text-xs font-medium text-gray-500">Issue</label>
                            <p class="whitespace-pre-line rounded-md bg-gray-100 px-4 py-3 text-sm leading-relaxed text-gray-800"
                                :class="expanded ? '' : 'line-clamp-6'"
                                x-text="activeRoadblock ? activeRoadblock.description : ''"></p>

                            <button type="button" @click="expanded = !expanded"
                                x-show="activeRoadblock && activeRoadblock.description && activeRoadblock.description.length > 400"
                                class="mt-1.5 text-xs font-semibold text-[#6D0D23] transition hover:underline focus:outline-none"
                                x-text="expanded ? 'Show less' : 'Show more'"></button>
                        </div>

                        {{-- Supporting files --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-500">Supporting Files</label>

                            <template x-if="activeRoadblock && activeRoadblock.files && activeRoadblock.files.length">
                                <ul class="flex flex-wrap gap-3">
                                    <template x-for="file in activeRoadblock.files" :key="file.name">
                                        <li class="flex items-center gap-8 rounded-md border border-gray-200 px-3 py-2">
                                            <span class="text-sm font-medium text-[#6D0D23]" x-text="file.name"></span>

                                            <span class="flex items-center gap-3 text-[#6D0D23]">
                                                {{-- Preview: images open in an on-page lightbox, everything else opens in a new tab --}}
                                                <button type="button" x-show="file.is_image" x-cloak
                                                    @click="previewImageUrl = file.url" aria-label="Preview image"
                                                    class="transition hover:opacity-70 focus:outline-none">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </button>
                                                <a :href="file.url" target="_blank" rel="noopener" x-show="!file.is_image" x-cloak
                                                    aria-label="Preview file" class="transition hover:opacity-70 focus:outline-none">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </a>

                                                <a :href="file.url" :download="file.name" aria-label="Download file"
                                                    class="transition hover:opacity-70 focus:outline-none">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 3v12" />
                                                        <path d="M7 11l5 5 5-5" />
                                                        <path d="M4 20h16" />
                                                    </svg>
                                                </a>
                                            </span>
                                        </li>
                                    </template>
                                </ul>
                            </template>

                            <template x-if="activeRoadblock && (!activeRoadblock.files || !activeRoadblock.files.length)">
                                <p class="text-sm text-gray-400">No files attached.</p>
                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             Image preview lightbox
             ============================================================ --}}
        <div x-show="previewImageUrl" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-6"
            @click.self="previewImageUrl = null" @keydown.escape.window="previewImageUrl = null">
            <button type="button" @click="previewImageUrl = null" aria-label="Close preview"
                class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20">&times;</button>
            <img :src="previewImageUrl" class="max-h-full max-w-full rounded-lg object-contain">
        </div>
    </div>
</x-layouts.founder>