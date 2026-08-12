<div class="rounded-xl border border-gray-200 overflow-hidden mb-10">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-left">
                <th class="px-4 py-3 font-semibold">Startup</th>
                <th class="px-4 py-3 font-semibold">Roadblock</th>
                <th class="px-4 py-3 font-semibold">Status</th>
                <th class="px-4 py-3 font-semibold">Meeting Schedule</th>
                <th class="px-4 py-3 font-semibold">Mentor</th>
                <th class="px-4 py-3 font-semibold">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $roadblock)
            <tr class="border-b border-gray-100 last:border-0" x-data="{ viewOpen: false, editOpen: false }">
                <td class="px-4 py-3">{{ $roadblock->startup->company_name }}</td>
                <td class="px-4 py-3">{{ $roadblock->display_category }}</td>
                <td class="px-4 py-3">{{ $roadblock->meeting_status_label }}</td>
                <td class="px-4 py-3">
                    {{ $roadblock->meeting_date?->format('M j, Y') }}<br>
                    {{ $roadblock->meeting_start_time }} - {{ $roadblock->meeting_end_time }}<br>
                    {{ $roadblock->meeting_platform }}
                    <a href="{{ $roadblock->meeting_link }}" target="_blank" class="text-blue-700 underline">Join meeting</a>
                </td>
                <td class="px-4 py-3">{{ $roadblock->mentor?->display_name }}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        <button type="button" @click="viewOpen = true" class="border border-[#6D0D23] text-[#6D0D23] rounded-lg px-3 py-1.5 hover:bg-[#6D0D23]/5">View</button>
                        @if ($roadblock->meeting_status_label === 'Live (In-Session)')
                        <a href="{{ $roadblock->meeting_link }}" target="_blank" class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg px-3 py-1.5">Join</a>
                        @else
                        <button type="button" @click="editOpen = true" class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white rounded-lg px-3 py-1.5">Edit</button>
                        @endif
                    </div>

                    <div x-show="viewOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white rounded-xl w-full max-w-lg overflow-hidden max-h-[90vh] overflow-y-auto" @click.outside="viewOpen = false">
                            <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white px-6 py-4 flex justify-between items-center">
                                <span class="font-bold">Mentorship</span>
                                <button type="button" @click="viewOpen = false" class="text-2xl">&times;</button>
                            </div>
                            <div class="p-6">
                                <p class="font-semibold mb-2">Schedule</p>
                                <div class="border rounded-lg p-4 mb-4 grid grid-cols-3 gap-3">
                                    <div><label class="text-xs text-gray-500">Platform</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->meeting_platform }}</p>
                                    </div>
                                    <div><label class="text-xs text-gray-500">Date</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->meeting_date?->format('M j, Y') }}</p>
                                    </div>
                                    <div><label class="text-xs text-gray-500">Time Slot</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->meeting_start_time }} - {{ $roadblock->meeting_end_time }}</p>
                                    </div>
                                </div>

                                <p class="font-semibold mb-2">Assigned Mentor</p>
                                <div class="border rounded-lg p-4 mb-4 grid grid-cols-2 gap-3">
                                    <div><label class="text-xs text-gray-500">Mentor</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->mentor?->display_name }}</p>
                                    </div>
                                    <div><label class="text-xs text-gray-500">Expertise</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->mentor?->specialization }}</p>
                                    </div>
                                    <div><label class="text-xs text-gray-500">Email</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->mentor?->contact_email }}</p>
                                    </div>
                                    <div><label class="text-xs text-gray-500">Contact Number</label>
                                        <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->mentor?->contact_number }}</p>
                                    </div>
                                </div>

                                <p class="font-semibold mb-2">Roadblock Details</p>
                                <div class="border rounded-lg p-4">
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div><label class="text-xs text-gray-500">Startup Name</label>
                                            <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->startup->company_name }}</p>
                                        </div>
                                        <div><label class="text-xs text-gray-500">Category</label>
                                            <p class="bg-gray-100 rounded px-3 py-2 text-sm">{{ $roadblock->startup->industry_sector }}</p>
                                        </div>
                                    </div>
                                    <label class="text-xs text-gray-500">Issue</label>
                                    <p class="bg-gray-100 rounded px-3 py-3 text-sm">{{ $roadblock->description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="editOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
                        <div class="bg-white rounded-xl w-full max-w-3xl overflow-hidden" @click.outside="editOpen = false">
                            <x-roadblock-assign-modal mode="edit" :roadblock="$roadblock" :mentors="$mentors" :action="route('admin.roadblocks.assign', $roadblock)" />
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-6 text-center text-gray-500">Nothing scheduled.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>