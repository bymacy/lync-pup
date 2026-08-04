<x-layouts.admin title="Roadblock Management">
    <div x-data="{ tab: 'manage', archiveStage: 'assessment' }">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Roadblock Management</h1>
            <p class="text-gray-500 mt-1">Review startup roadblocks and assign experts.</p>
        </div>

        <div class="border-b border-gray-200 mb-6">
            <nav class="flex gap-8">
                <button type="button" @click="tab = 'manage'" :class="tab === 'manage' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500'" class="pb-3 border-b-2 font-medium">Manage Roadblock</button>
                <button type="button" @click="tab = 'today'" :class="tab === 'today' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500'" class="pb-3 border-b-2 font-medium">Scheduled Today</button>
                <button type="button" @click="tab = 'archive'" :class="tab === 'archive' ? 'border-rose-900 text-rose-900' : 'border-transparent text-gray-500'" class="pb-3 border-b-2 font-medium">Archive</button>
            </nav>
        </div>

        {{-- ============ MANAGE ROADBLOCK ============ --}}
        <div x-show="tab === 'manage'">
            <h2 class="font-bold text-gray-900 mb-4">Pending Roadblock</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                @forelse ($pending as $roadblock)
                    @php $banners = ['from-purple-500 to-purple-700', 'from-blue-500 to-blue-700', 'from-teal-500 to-teal-700']; @endphp
                    <div class="border rounded-xl overflow-hidden" x-data="{ viewOpen: false, assignOpen: false }">
                        <div class="h-32 bg-gradient-to-br {{ $banners[$roadblock->roadblock_id % count($banners)] }} relative flex items-center justify-center">
                            <span class="absolute top-3 right-3 bg-white text-xs font-medium rounded-full px-3 py-1">{{ $roadblock->startup->industry_sector }}</span>
                        </div>
                        <div class="p-4">
                            <p class="font-bold">{{ $roadblock->startup->company_name }} <span class="text-rose-900">&bull;</span> {{ $roadblock->display_category }}</p>
                            <p class="text-sm text-gray-500 mt-1 mb-3">{{ \Illuminate\Support\Str::limit($roadblock->description, 100) }}</p>
                            {{-- RLS score: guessing the relation name below, confirm/adjust if wrong --}}
                            <p class="text-xs text-gray-500 mb-3">RLS {{ $roadblock->startup->latestReadinessAssessment?->overall ?? 'N/A' }}</p>

                            <div class="flex gap-2">
                                <button type="button" @click="viewOpen = true" class="flex-1 border border-rose-900 text-rose-900 rounded-lg py-2 text-sm font-medium">View Details</button>
                                <button type="button" @click="assignOpen = true" class="flex-1 bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-lg py-2 text-sm font-medium">Assign & Schedule</button>
                            </div>
                        </div>

                        {{-- View Details modal --}}
                        <div x-show="viewOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                            <div class="bg-white rounded-xl w-full max-w-lg overflow-hidden max-h-[90vh] overflow-y-auto" @click.outside="viewOpen = false">
                                <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex justify-between items-center">
                                    <span class="font-bold">Roadblock</span>
                                    <button type="button" @click="viewOpen = false" class="text-2xl">&times;</button>
                                </div>
                                <div class="p-6">
                                    <p class="font-semibold mb-2">Startup Information</p>
                                    <div class="border rounded-lg p-4 mb-4">
                                        <div class="grid grid-cols-2 gap-3 mb-3">
                                            <div>
                                                <label class="text-xs text-gray-500">Startup Name</label>
                                                <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->startup->company_name }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-500">Category</label>
                                                <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->startup->industry_sector }}</p>
                                            </div>
                                        </div>
                                        <label class="text-xs text-gray-500">Batch</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm mb-3">{{ $roadblock->startup->batch_label }}</p>
                                        <label class="text-xs text-gray-500">Team</label>
                                        <div class="grid grid-cols-2 gap-2 mt-1">
                                            {{-- guessing relation name teamMembers(), confirm/adjust if wrong --}}
                                            @foreach ($roadblock->startup->teamMembers as $member)
                                                <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $member->full_name }}</p>
                                            @endforeach
                                        </div>
                                    </div>

                                    <p class="font-semibold mb-2">Roadblock Details</p>
                                    <div class="border rounded-lg p-4">
                                        <div class="grid grid-cols-2 gap-3 mb-3">
                                            <div>
                                                <label class="text-xs text-gray-500">Roadblock Category</label>
                                                <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->display_category }}</p>
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-500">Date Submitted</label>
                                                <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->created_at->format('M d, Y') }}</p>
                                            </div>
                                        </div>
                                        <label class="text-xs text-gray-500">Issue</label>
                                        <p class="bg-gray-100 rounded px-3 py-3 text-sm mb-3">{{ $roadblock->description }}</p>

                                        @if ($roadblock->files->isNotEmpty())
                                            <label class="text-xs text-gray-500">Supporting Files</label>
                                            <div class="space-y-2 mt-1">
                                                @foreach ($roadblock->files as $file)
                                                    <div class="flex items-center justify-between border rounded-md px-3 py-2 text-sm">
                                                        <span class="text-rose-900">{{ $file->original_filename }}</span>
                                                        <a href="{{ $file->url }}" download class="text-rose-900">&darr;</a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Assign & Schedule modal --}}
                        <div x-show="assignOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                            <div class="bg-white rounded-xl w-full max-w-3xl overflow-hidden" @click.outside="assignOpen = false">
                                <x-roadblock-assign-modal mode="assign" :roadblock="$roadblock" :mentors="$mentors" :action="route('admin.roadblocks.assign', $roadblock)" />
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 col-span-full">No pending roadblocks.</p>
                @endforelse
            </div>

            <h2 class="font-bold text-gray-900 mb-4">Upcoming Mentorship</h2>
            @include('admin.roadblocks._mentorship-table', ['rows' => $upcoming])
        </div>

        {{-- ============ SCHEDULED TODAY ============ --}}
        <div x-show="tab === 'today'">
            <h2 class="font-bold text-gray-900 mb-4">Mentorship Today</h2>
            @include('admin.roadblocks._mentorship-table', ['rows' => $scheduledToday])
        </div>

        {{-- ============ ARCHIVE ============ --}}
        <div x-show="tab === 'archive'">
            <h2 class="font-bold text-gray-900 mb-4">Mentorship Evaluation</h2>

            <div class="mb-6">
                <label class="text-sm font-medium mr-2">Stage:</label>
                <select x-model="archiveStage" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="assessment">Assessment</option>
                    <option value="resolved">Resolved</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            {{-- Assessment stage --}}
            <div x-show="archiveStage === 'assessment'">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                            <th class="px-4 py-3">Startup</th>
                            <th class="px-4 py-3">Roadblock</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Mentor</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assessment as $roadblock)
                            <tr class="border-b">
                                <td class="px-4 py-3">{{ $roadblock->startup->company_name }}</td>
                                <td class="px-4 py-3">{{ $roadblock->display_category }}</td>
                                <td class="px-4 py-3">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">{{ $roadblock->startup->industry_sector }}</td>
                                <td class="px-4 py-3">{{ $roadblock->mentor?->display_name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.roadblocks.fail', $roadblock) }}">
                                            @csrf
                                            <button type="submit" class="border border-rose-900 text-rose-900 rounded-lg px-3 py-1.5">Failed</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.roadblocks.resolve', $roadblock) }}">
                                            @csrf
                                            <button type="submit" class="bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-lg px-3 py-1.5">Resolve</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Nothing pending assessment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Resolved stage --}}
            <div x-show="archiveStage === 'resolved'">
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg px-4 py-3 mb-4 text-sm">
                    These roadblocks have been successfully resolved.
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                            <th class="px-4 py-3">Startup</th>
                            <th class="px-4 py-3">Roadblock</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Mentor</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resolved as $roadblock)
                            <tr class="border-b">
                                <td class="px-4 py-3">{{ $roadblock->startup->company_name }}</td>
                                <td class="px-4 py-3">{{ $roadblock->display_category }}</td>
                                <td class="px-4 py-3">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">{{ $roadblock->startup->industry_sector }}</td>
                                <td class="px-4 py-3">{{ $roadblock->mentor?->display_name }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.roadblocks.recover', $roadblock) }}">
                                        @csrf
                                        <button type="submit" class="border border-rose-900 text-rose-900 rounded-lg px-3 py-1.5">Recover</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No resolved roadblocks.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Failed stage --}}
            <div x-show="archiveStage === 'failed'">
                <div class="bg-rose-50 text-rose-800 border border-rose-200 rounded-lg px-4 py-3 mb-4 text-sm">
                    Efforts to resolve these roadblocks were unsuccessful.
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-rose-950 to-blue-950 text-white text-left">
                            <th class="px-4 py-3">Startup</th>
                            <th class="px-4 py-3">Roadblock</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Mentor</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($failed as $roadblock)
                            <tr class="border-b" x-data="{ deleteOpen: false, rescheduleOpen: false }">
                                <td class="px-4 py-3">{{ $roadblock->startup->company_name }}</td>
                                <td class="px-4 py-3">{{ $roadblock->display_category }}</td>
                                <td class="px-4 py-3">{{ $roadblock->meeting_date?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">{{ $roadblock->startup->industry_sector }}</td>
                                <td class="px-4 py-3">{{ $roadblock->mentor?->display_name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button type="button" @click="deleteOpen = true" class="border border-rose-900 text-rose-900 rounded-lg px-3 py-1.5">Delete</button>
                                        <button type="button" @click="rescheduleOpen = true" class="bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-lg px-3 py-1.5">Reschedule</button>
                                    </div>

                                    <div x-show="deleteOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                                        <div class="bg-white rounded-2xl w-full max-w-md p-8 text-center" @click.outside="deleteOpen = false">
                                            <h3 class="text-xl font-semibold mb-2">Delete Roadblock</h3>
                                            <p class="text-sm text-gray-600 mb-6">This action is permanent and cannot be undone.</p>
                                            <div class="flex gap-3 justify-center">
                                                <button type="button" @click="deleteOpen = false" class="border rounded-lg px-5 py-2.5 text-sm font-medium">Cancel</button>
                                                <form method="POST" action="{{ route('admin.roadblocks.destroy', $roadblock) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg-gradient-to-r from-rose-900 to-blue-950 text-white rounded-lg px-5 py-2.5 text-sm font-medium">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div x-show="rescheduleOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                                        <div class="bg-white rounded-xl w-full max-w-3xl overflow-hidden" @click.outside="rescheduleOpen = false">
                                            <x-roadblock-assign-modal mode="reschedule" :roadblock="$roadblock" :mentors="$mentors" :action="route('admin.roadblocks.assign', $roadblock)" />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No failed roadblocks.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
