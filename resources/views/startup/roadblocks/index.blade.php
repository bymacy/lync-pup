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
            <h2 class="font-semibold text-lg mb-4">Roadblock Submission</h2>

            <form x-ref="form" method="POST" action="{{ route('startup.submissions.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="border rounded-lg p-6">
                    <label class="block font-medium mb-2">Problem Category</label>
                    <select name="problem_category" x-model="category" required class="w-full border rounded-md px-3 py-2">
                        <option value="" disabled>Type of roadblock</option>
                        <option value="Business Development">Business Development</option>
                        <option value="Technical Support">Technical Support</option>
                        <option value="Market Research">Market Research</option>
                        <option value="Strategy Consultant">Strategy Consultant</option>
                        <option value="Others">Others</option>
                    </select>

                    <div x-show="category === 'Others'" class="mt-4">
                        <label class="block text-sm text-gray-500 mb-1">Type specific roadblock (e.g., Legal Counseling)</label>
                        <input type="text" name="problem_category_other" x-model="categoryOther"
                            :required="category === 'Others'" placeholder="Enter here..."
                            class="w-full border rounded-md px-3 py-2">
                    </div>
                </div>

                <div class="border rounded-lg p-6">
                    <label class="block font-medium mb-2">Describe your roadblock</label>
                    <textarea name="description" x-model="description" required rows="5" maxlength="5000"
                        placeholder="Enter details about your roadblock..."
                        class="w-full border rounded-md px-3 py-2"></textarea>
                    <p class="text-sm text-gray-500 mt-1">Provide enough detail for the mentor to understand the situation.</p>
                </div>

                <div class="border rounded-lg p-6">
                    <label class="block font-medium mb-2">Supporting Files</label>

                    <div
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="dragOver = false; addFiles($event.dataTransfer.files)"
                        :class="dragOver ? 'border-rose-900 bg-rose-50' : 'border-gray-300'"
                        class="border-2 border-dashed rounded-lg p-8 text-center"
                    >
                        <p class="text-gray-500 mb-2">Drag-and-drop</p>
                        <button type="button" @click="$refs.fileInput.click()"
                            class="bg-rose-900 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Browse Files
                        </button>
                        <input type="file" name="supporting_files[]" x-ref="fileInput" multiple class="hidden"
                            @change="addFiles($event.target.files)">
                    </div>

                    <template x-if="files.length > 0">
                        <ul class="mt-4 space-y-2">
                            <template x-for="(file, index) in files" :key="index">
                                <li class="flex items-center justify-between border rounded-md px-3 py-2">
                                    <div class="flex items-center gap-3">
                                        <template x-if="file.isImage">
                                            <img :src="file.url" class="w-10 h-10 object-cover rounded">
                                        </template>
                                        <template x-if="!file.isImage">
                                            <span class="text-rose-900 font-medium text-xs" x-text="file.name.split('.').pop().toUpperCase()"></span>
                                        </template>
                                        <span x-text="file.name" class="text-sm"></span>
                                    </div>
                                    <button type="button" @click="removeFile(index)" class="text-gray-400 hover:text-rose-900">&times;</button>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>

                <div class="flex gap-4">
                    <button type="button" @click="resetForm()"
                        class="flex-1 border border-blue-950 text-blue-950 rounded-md py-3 font-semibold">
                        Clear Form
                    </button>
                    <button type="button" @click="if (category && description) showConfirm = true"
                        class="flex-1 bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-md py-3 font-semibold">
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
