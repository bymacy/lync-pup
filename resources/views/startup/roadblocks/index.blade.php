<x-layouts.founder>
    <div x-data="{
        tab: '{{ request('tab', 'roadblock') }}',
        category: '',
        categoryOther: '',
        description: '',
        files: [],
        dt: new DataTransfer(),
        dragOver: false,
        showConfirm: false,
        showSuccess: {{ session('roadblock_submitted') ? 'true' : 'false' }},
        activeRoadblock: null,

        addFiles(fileList) {
            Array.from(fileList).forEach(file => this.dt.items.add(file));
            this.syncInput();
        },

        removeFile(index) {
            const newDt = new DataTransfer();
            Array.from(this.dt.files).forEach((file, i) => {
                if (i !== index) newDt.items.add(file);
            });
            this.dt = newDt;
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
            this.description = '';
            this.dt = new DataTransfer();
            this.files = [];
            this.$refs.fileInput.files = this.dt.files;
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

        {{-- Roadblock tab --}}
        <div x-show="tab === 'roadblock'">

            <div class="flex items-center gap-2.5 mb-5">
                <span class="text-rose-900"><x-icon name="warning.svg" class="w-10 h-10" /></span>
                <h2 class="font-semibold text-lg text-gray-900">Roadblock Submission</h2>
            </div>

            <form x-ref="form" method="POST" action="{{ route('startup.submissions.store') }}"
                enctype="multipart/form-data" class="space-y-4">
                @csrf

                {{-- Problem Category --}}
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
            },

            choose(option) {
                this.category = option;
                this.open = false;
                this.$refs.trigger.focus();
            },

            move(step) {
                if (!this.open) { this.toggle(); return; }
                const count = this.options.length;
                this.highlighted = (this.highlighted + step + count) % count;
            },
        }"
                        @click.outside="open = false"
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
                            class="w-full flex items-stretch rounded-lg border border-rose-200 overflow-hidden text-left
                   transition focus:outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
                            :class="open && 'border-rose-400 ring-2 ring-rose-100'">

                            <span class="w-11 flex-shrink-0 bg-rose-50 border-r border-rose-200 flex items-center justify-center text-rose-800">
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

                    <div x-show="category === 'Others'" x-cloak class="mt-4 max-w-md">
                        <label class="block text-xs text-gray-500 mb-1.5">Type specific roadblock (e.g., Legal Counseling)</label>
                        <input type="text" name="problem_category_other" x-model="categoryOther"
                            :required="category === 'Others'" placeholder="Enter here..."
                            class="w-full rounded-lg border border-rose-200 px-3 py-2.5 text-sm
                   focus:border-rose-400 focus:ring-2 focus:ring-rose-100 focus:outline-none">
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-white border border-gray-300 rounded-xl p-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Describe your roadblock</label>

                    <div class="flex items-stretch rounded-lg border border-rose-200 overflow-hidden
                        transition focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100">
                        <div class="w-11 flex-shrink-0 bg-rose-50 border-r border-rose-200 flex items-center justify-center text-rose-800">
                            <x-icon name="mail.svg" class="w-5 h-5" />
                        </div>
                        <textarea name="description" x-model="description" required rows="5" maxlength="5000"
                            placeholder="Enter details about your roadblock..."
                            class="flex-1 border-0 px-3 py-2.5 text-sm resize-y
                           placeholder:text-gray-400 focus:outline-none focus:ring-0"></textarea>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">Provide enough detail for the mentor to understand the situation.</p>
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
                            :class="dragOver ? 'border-rose-500 bg-rose-100' : 'border-rose-200 bg-rose-50/60'"
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
                                @change="addFiles($event.target.files)">
                        </div>
                    </div>

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
                    <button type="button" @click="if (category && description) showConfirm = true"
                        class="flex-1 bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-lg py-3
                       text-sm font-semibold hover:opacity-95 transition">
                        Submit Roadblock
                    </button>
                </div>
            </form>
        </div>

        {{-- Update tab — placeholder, separate module --}}
        <div x-show="tab === 'update'" class="text-gray-500 italic">
            Weekly Update submission — not built yet.
        </div>

        {{-- Archive tab --}}
        <div x-show="tab === 'archive'">
            <h2 class="font-semibold text-lg mb-4">Archive</h2>

            @forelse ($roadblocks as $roadblock)
            <div class="flex border rounded-lg overflow-hidden mb-4">
                <div class="w-2 bg-rose-100"></div>
                <div class="flex-1 p-4 flex justify-between items-start">
                    <div>
                        <p class="font-semibold">Roadblock</p>
                        <p class="text-sm"><span class="font-medium">Problem Category:</span> {{ $roadblock->display_category }}</p>
                        <p class="text-sm text-gray-600">
                            Submitted on: {{ $roadblock->created_at->format('M d, Y') }}
                            &nbsp;&nbsp;Status:
                            <span class="{{ $roadblock->status === 'Resolved' ? 'text-green-600' : 'text-amber-600' }} font-medium">
                                {{ $roadblock->status }}
                            </span>
                        </p>
                        <p class="text-sm text-gray-500 mt-2">{{ \Illuminate\Support\Str::limit($roadblock->description, 80) }}</p>
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
                        class="border border-rose-900 text-rose-900 rounded-md px-3 py-1 text-sm h-fit">
                        View
                    </button>
                </div>
            </div>
            @empty
            <p class="text-gray-500">No roadblocks submitted yet.</p>
            @endforelse
        </div>

        {{-- Confirm submit modal --}}
        <div x-show="showConfirm" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-8 max-w-md w-full text-center">
                <h3 class="text-xl font-bold text-rose-900 mb-2">Submit Roadblock?</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to submit your roadblock details? This will notify the team and initiate the mentor assignment process.</p>
                <div class="flex gap-4">
                    <button type="button" @click="showConfirm = false" class="flex-1 border border-blue-950 text-blue-950 rounded-md py-2 font-semibold">Cancel</button>
                    <button type="button" @click="showConfirm = false; $refs.form.submit()" class="flex-1 bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-md py-2 font-semibold">Submit</button>
                </div>
            </div>
        </div>

        {{-- Success modal --}}
        <div x-show="showSuccess" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg overflow-hidden max-w-md w-full text-center">
                <div class="bg-gradient-to-r from-rose-900 to-blue-950 py-6">
                    <div class="bg-white rounded-full w-12 h-12 flex items-center justify-center mx-auto text-rose-900 font-bold">&#10003;</div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-rose-900 mb-2">Great!</h3>
                    <p class="text-gray-600 mb-4">Submission successful.</p>
                    <button type="button" @click="showSuccess = false; tab = 'archive'"
                        class="bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-full px-8 py-2 font-semibold">
                        Continue
                    </button>
                </div>
            </div>
        </div>

        {{-- Roadblock detail modal --}}
        <div x-show="activeRoadblock" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg overflow-hidden max-w-lg w-full">
                <div class="bg-gradient-to-r from-rose-900 to-blue-950 px-6 py-4 flex justify-between items-center text-white">
                    <span class="font-semibold">Roadblock</span>
                    <button type="button" @click="activeRoadblock = null">&times;</button>
                </div>
                <div class="p-6">
                    <div class="flex justify-between mb-4">
                        <h4 class="font-semibold">Roadblock Details</h4>
                        <span class="text-sm">Status:
                            <span :class="activeRoadblock && activeRoadblock.status === 'Resolved' ? 'text-green-600' : 'text-amber-600'" x-text="activeRoadblock ? activeRoadblock.status : ''"></span>
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-sm text-gray-500">Roadblock Category</label>
                            <p class="bg-gray-100 rounded px-3 py-2" x-text="activeRoadblock ? activeRoadblock.category : ''"></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500">Date Submitted</label>
                            <p class="bg-gray-100 rounded px-3 py-2" x-text="activeRoadblock ? activeRoadblock.date : ''"></p>
                        </div>
                    </div>
                    <label class="text-sm text-gray-500">Issue</label>
                    <p class="bg-gray-100 rounded px-3 py-3 mb-4" x-text="activeRoadblock ? activeRoadblock.description : ''"></p>

                    <label class="text-sm text-gray-500">Supporting Files</label>
                    <template x-if="activeRoadblock">
                        <ul class="space-y-2 mt-1">
                            <template x-for="file in activeRoadblock.files" :key="file.name">
                                <li class="flex items-center justify-between border rounded-md px-3 py-2">
                                    <span class="text-rose-900 text-sm" x-text="file.name"></span>
                                    <a :href="file.url" download class="text-rose-900">&darr;</a>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-layouts.founder>