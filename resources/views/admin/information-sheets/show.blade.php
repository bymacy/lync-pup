<x-layouts.admin :title="$startup->company_name.' - Information Sheet'">
    <div class="bg-white rounded-xl border border-gray-200 max-w-5xl mx-auto">
        <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between rounded-t-xl">
            <h2 class="font-bold">{{ $startup->company_name }}</h2>
            <a href="{{ route('admin.startups.show', $startup) }}" class="text-white/70 hover:text-white">&times;</a>
        </div>

        <div class="p-6">
            <p class="text-center text-xs text-rose-800 font-medium mb-2">PUP-TBIDO FORM No.001</p>
            <h1 class="text-center font-bold text-lg mb-6">STARTUP INFORMATION SHEET</h1>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-gray-600 mb-6">
                Read the attached guide before accomplishing this form. Use capital letters, tick appropriate boxes, and indicate N/A if not applicable.
            </div>

            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">V. STARTUP INFORMATION</h3>
                <div class="border border-t-0 rounded-b-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 text-xs mb-1">STARTUP NAME</p>
                        <p class="border rounded px-3 py-2 bg-gray-50">{{ $startup->company_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs mb-1">STARTUP OVERVIEW</p>
                        <p class="border rounded px-3 py-2 bg-gray-50">{{ $startup->informationSheet?->business_description }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs mb-1">TARGET MARKET</p>
                        <p class="border rounded px-3 py-2 bg-gray-50">{{ $startup->informationSheet?->target_market ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs mb-1">PROBLEM STATEMENT</p>
                        <p class="border rounded px-3 py-2 bg-gray-50">{{ $startup->informationSheet?->problem_statement ?? '—' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-gray-500 text-xs mb-1">SOLUTION OFFERED</p>
                        <p class="border rounded px-3 py-2 bg-gray-50">{{ $startup->informationSheet?->solution_offered ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="bg-blue-900 text-white text-sm font-medium px-4 py-2 rounded-t-lg">II. CORE TEAM FORMATION</h3>
                <div class="border border-t-0 rounded-b-lg overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Role</th>
                                <th class="px-3 py-2">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($startup->teamMembers as $member)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $member->full_name }}</td>
                                    <td class="px-3 py-2">{{ $member->role }}</td>
                                    <td class="px-3 py-2">{{ $member->email }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-3 py-2 text-gray-400" colspan="3">No team members listed.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex gap-3" x-data="{ rejecting: false }">
                <a href="{{ route('admin.startups.show', $startup) }}"
                   class="flex-1 text-center border rounded-lg py-3 text-sm font-medium text-blue-900 border-blue-900">
                    BACK
                </a>

                @if ($startup->informationSheet?->approval_status === 'Pending')
                    <button type="button" @click="rejecting = !rejecting"
                            class="flex-1 border border-red-700 text-red-700 rounded-lg py-3 text-sm font-medium">
                        REJECT
                    </button>

                    <form method="POST" action="{{ route('admin.information-sheet.approve', $startup) }}" class="flex-1">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full bg-rose-900 text-white rounded-lg py-3 text-sm font-medium">
                            APPROVE
                        </button>
                    </form>
                @endif
            </div>

            <div x-data="{ rejecting: false }" x-show="rejecting" x-cloak class="mt-4">
                <form method="POST" action="{{ route('admin.information-sheet.reject', $startup) }}">
                    @csrf
                    @method('PATCH')
                    <textarea name="evaluator_remarks" rows="3" placeholder="Reason for rejection..."
                              class="w-full border rounded-lg p-3 text-sm"></textarea>
                    <button type="submit" class="mt-2 bg-red-700 text-white rounded-lg px-4 py-2 text-sm font-medium">
                        Confirm Rejection
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>