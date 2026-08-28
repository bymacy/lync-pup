<x-layouts.admin title="Founder Application">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Founder Application</h1>
        <p class="text-gray-500 mt-1">Review and manage founder account applications.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl border border-[#B8D7FF] bg-[#CDE2FF] p-5 flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Application</p>
                <p class="text-4xl font-bold mt-1">{{ $totals['total'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-full bg-blue-500 flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>
        <div class="rounded-xl border border-[#F5D27B] bg-[#FFE2AA] p-5 flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Pending</p>
                <p class="text-4xl font-bold mt-1">{{ $totals['pending'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-full bg-amber-500 flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                </svg>
            </div>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Approved</p>
                <p class="text-4xl font-bold mt-1">{{ $totals['approved'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-full bg-green-600 flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Rejected</p>
                <p class="text-4xl font-bold mt-1">{{ $totals['rejected'] }}</p>
            </div>
            <div class="w-11 h-11 rounded-full bg-rose-600 flex items-center justify-center text-white flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        </div>
    </div>

    <div class="border-b border-gray-300 mb-6">
        <nav class="flex overflow-x-auto whitespace-nowrap">
            @foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.founder-applications.index', ['tab' => $key, 'per_page' => $perPage]) }}"
                    class="px-6 sm:px-10 lg:px-16 py-3 text-sm font-medium border-b-2 -mb-px transition-colors duration-200
                        {{ $activeTab === $key ? 'border-[#6D0D23] text-[#6D0D23]' : 'border-transparent text-gray-700 hover:text-[#6D0D23]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-left">
                    @if ($activeTab === 'pending')
                        <th class="px-4 py-3 font-semibold">#</th>
                    @endif
                    <th class="px-4 py-3 font-semibold">Applicant</th>
                    <th class="px-4 py-3 font-semibold">Startup Name</th>
                    <th class="px-4 py-3 font-semibold">Email</th>
                    <th class="px-4 py-3 font-semibold">Date Applied</th>
                    @if ($activeTab === 'approved')
                        <th class="px-4 py-3 font-semibold">Date Approved</th>
                    @elseif ($activeTab === 'rejected')
                        <th class="px-4 py-3 font-semibold">Date Rejected</th>
                    @elseif ($activeTab === 'all')
                        <th class="px-4 py-3 font-semibold">Status</th>
                    @endif
                    <th class="px-4 py-3 font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($applications as $i => $application)
                    @php
                        $founder = $application->user;
                        $isPending = $founder->isPendingApproval();
                    @endphp
                    <tr x-data="{
                            step: null,
                            remarks: @js($application->admin_remarks ?? ''),
                            reason: '',
                            cohortId: '',
                            coordinatorId: '{{ optional($application->activeCoordinatorAssignment)->coordinator_id }}',
                            submitting: false,
                        }">
                        @if ($activeTab === 'pending')
                            <td class="px-4 py-3 text-gray-500">{{ $applications->firstItem() + $i }}</td>
                        @endif
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $founder->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $application->company_name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $founder->email }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $founder->created_at->format('M j, Y g:i A') }}</td>

                        @if ($activeTab === 'approved')
                            <td class="px-4 py-3 text-gray-600">{{ optional($application->application_decided_at)->format('M j, Y g:i A') ?? '—' }}</td>
                        @elseif ($activeTab === 'rejected')
                            <td class="px-4 py-3 text-gray-600">{{ optional($application->application_decided_at)->format('M j, Y g:i A') ?? '—' }}</td>
                        @elseif ($activeTab === 'all')
                            <td class="px-4 py-3">
                                @php
                                    $badge = match ($founder->account_status) {
                                        'Active' => ['Approved', 'border-green-300 text-green-800'],
                                        'Rejected' => ['Rejected', 'border-red-300 text-red-800'],
                                        default => ['Pending', 'border-amber-300 text-amber-800'],
                                    };
                                @endphp
                                <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span>
                            </td>
                        @endif

                        <td class="px-4 py-3">
                            @if ($isPending)
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="step = 'review'"
                                        class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white text-xs font-semibold rounded-lg px-4 py-2 hover:opacity-90 transition">
                                        Review
                                    </button>
                                    <button type="button" @click="step = 'delete'" title="Delete application"
                                        class="border border-gray-300 text-gray-500 hover:text-red-700 hover:border-red-300 rounded-lg p-2 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0-1 12a2 2 0 01-2 2H9a2 2 0 01-2-2L6 7h12z" />
                                        </svg>
                                    </button>
                                </div>
                            @else
                                <button type="button" @click="step = 'view'"
                                    class="border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg px-4 py-2 hover:bg-gray-50 transition">
                                    View
                                </button>
                            @endif

                            {{-- Modals live inside this same <td> (not as siblings of it) —
                                 a <tr> may only contain <td>/<th> elements per the HTML spec,
                                 so any <div> placed directly under <tr> gets silently "foster
                                 parented" out in front of the whole <table> by the browser,
                                 disconnecting it from this row's Alpine x-data scope. --}}
                            @if ($isPending)
                                {{-- ============ REVIEW MODAL ============ --}}
                            <div x-show="step === 'review'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" style="display: none;">
                                <div class="relative bg-white rounded-xl w-full max-w-3xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="step = null">
                                    <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between flex-shrink-0">
                                        <h3 class="font-bold">Review Founder Application</h3>
                                        <button type="button" @click="step = null" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
                                    </div>

                                    <div class="p-6 overflow-y-auto space-y-6">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <h4 class="font-bold text-sm text-gray-900 mb-3">Applicant Information</h4>
                                                <dl class="space-y-2 text-sm">
                                                    <div><dt class="text-gray-500 text-xs">Full Name</dt><dd class="font-medium">{{ $founder->name }}</dd></div>
                                                    <div><dt class="text-gray-500 text-xs">Email</dt><dd class="font-medium">{{ $founder->email }}</dd></div>
                                                    <div><dt class="text-gray-500 text-xs">Startup</dt><dd class="font-medium">{{ $application->company_name }}</dd></div>
                                                </dl>
                                            </div>
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <h4 class="font-bold text-sm text-gray-900 mb-3">Account Information</h4>
                                                <dl class="space-y-2 text-sm">
                                                    <div class="flex items-center justify-between">
                                                        <dt class="text-gray-500 text-xs">Email Verified</dt>
                                                        <dd>
                                                            @if ($founder->hasVerifiedEmail())
                                                                <span class="rounded-full border border-green-300 text-green-700 text-[11px] font-semibold px-2 py-0.5">● Verified</span>
                                                            @else
                                                                <span class="rounded-full border border-gray-300 text-gray-500 text-[11px] font-semibold px-2 py-0.5">Not Verified</span>
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div><dt class="text-gray-500 text-xs">Date Applied</dt><dd class="font-medium">{{ $founder->created_at->format('M j, Y g:i A') }}</dd></div>
                                                    <div><dt class="text-gray-500 text-xs">Application ID</dt><dd class="font-medium">{{ $application->application_id }}</dd></div>
                                                </dl>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 class="font-bold text-sm text-gray-900 mb-2">Status History</h4>
                                            <x-founder-application-timeline :startup="$application" />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Remarks <span class="text-gray-400 font-normal">(Optional)</span></label>
                                            <textarea x-model="remarks" rows="3" placeholder="Add remarks here..."
                                                class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
                                        <button type="button" @click="step = null" class="flex-1 border rounded-lg py-2.5 text-sm font-medium text-gray-700">Cancel</button>
                                        <button type="button" @click="step = 'reject'" class="flex-1 bg-rose-900 hover:bg-rose-950 text-white rounded-lg py-2.5 text-sm font-medium transition">Reject Application</button>
                                        <button type="button" @click="step = 'approve'" class="flex-1 bg-green-700 hover:bg-green-800 text-white rounded-lg py-2.5 text-sm font-medium transition">Approve Application</button>
                                    </div>
                                </div>
                            </div>

                            {{-- ============ APPROVE MODAL ============ --}}
                            <div x-show="step === 'approve'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" style="display: none;">
                                <div class="relative bg-white rounded-xl w-full max-w-md overflow-hidden" @click.outside="step = 'review'">
                                    <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between">
                                        <h3 class="font-bold">Approve Application</h3>
                                        <button type="button" @click="step = 'review'" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
                                    </div>

                                    <form method="POST" action="{{ route('admin.founder-applications.approve', $application) }}" class="p-6 space-y-4"
                                        @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                                        <input type="hidden" name="admin_remarks" x-bind:value="remarks">

                                        <div class="flex justify-center">
                                            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-center font-bold text-lg text-gray-900">Approve Founder Application</p>
                                        <p class="text-center text-sm text-gray-500">Are you sure you want to approve this founder application?</p>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Cohort <span class="text-red-600">*</span></label>
                                            <select name="cohort_id" x-model="cohortId" required class="w-full border rounded-lg px-3 py-2 text-sm">
                                                <option value="">Select cohort</option>
                                                @foreach ($cohorts as $cohort)
                                                    <option value="{{ $cohort->cohort_id }}">{{ $cohort->display_label }}</option>
                                                @endforeach
                                            </select>
                                            @error('cohort_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign Portfolio Coordinator <span class="text-gray-400 font-normal">(Optional)</span></label>
                                            <select name="coordinator_id" x-model="coordinatorId" class="w-full border rounded-lg px-3 py-2 text-sm">
                                                <option value="">Select coordinator</option>
                                                @foreach ($coordinators as $coordinator)
                                                    <option value="{{ $coordinator->coordinator_id }}">{{ $coordinator->display_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="flex gap-3 pt-2">
                                            <button type="button" @click="step = 'review'" :disabled="submitting" class="flex-1 border rounded-lg py-2.5 text-sm font-medium text-gray-700 disabled:opacity-50">Cancel</button>
                                            <button type="submit" :disabled="submitting"
                                                class="flex-1 bg-green-700 hover:bg-green-800 disabled:opacity-60 disabled:cursor-not-allowed text-white rounded-lg py-2.5 text-sm font-medium transition">
                                                <span x-show="!submitting">Confirm Approve</span>
                                                <span x-show="submitting">Processing…</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- ============ REJECT MODAL ============ --}}
                            <div x-show="step === 'reject'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" style="display: none;">
                                <div class="relative bg-white rounded-xl w-full max-w-md overflow-hidden" @click.outside="step = 'review'">
                                    <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between">
                                        <h3 class="font-bold">Reject Application</h3>
                                        <button type="button" @click="step = 'review'" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
                                    </div>

                                    <form method="POST" action="{{ route('admin.founder-applications.reject', $application) }}" class="p-6 space-y-4"
                                        @submit="submitting = true">
                                        @csrf
                                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                                        <input type="hidden" name="per_page" value="{{ $perPage }}">

                                        <div class="flex justify-center">
                                            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-center font-bold text-lg text-gray-900">Reject Founder Application</p>
                                        <p class="text-center text-sm text-gray-500">Are you sure you want to reject this application?<br>This applicant will be notified about your decision.</p>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection <span class="text-red-600">*</span></label>
                                            <input type="text" name="rejection_reason" x-model="reason" required placeholder="Enter reason here"
                                                class="w-full border rounded-lg px-3 py-2 text-sm">
                                            @error('rejection_reason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Additional Remarks <span class="text-gray-400 font-normal">(Optional)</span></label>
                                            <textarea name="admin_remarks" x-model="remarks" rows="2" placeholder="Add remarks here"
                                                class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                                        </div>

                                        <div class="flex gap-3 pt-2">
                                            <button type="button" @click="step = 'review'" :disabled="submitting" class="flex-1 border rounded-lg py-2.5 text-sm font-medium text-gray-700 disabled:opacity-50">Cancel</button>
                                            <button type="submit" :disabled="submitting"
                                                class="flex-1 bg-rose-900 hover:bg-rose-950 disabled:opacity-60 disabled:cursor-not-allowed text-white rounded-lg py-2.5 text-sm font-medium transition">
                                                <span x-show="!submitting">Confirm Rejection</span>
                                                <span x-show="submitting">Processing…</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- ============ DELETE MODAL ============ --}}
                            <div x-show="step === 'delete'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" style="display: none;">
                                <div class="relative bg-white rounded-xl w-full max-w-md overflow-hidden" @click.outside="step = null">
                                    <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between">
                                        <h3 class="font-bold">Delete Application</h3>
                                        <button type="button" @click="step = null" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
                                    </div>

                                    <form method="POST" action="{{ route('admin.founder-applications.destroy', $application) }}" class="p-6 space-y-4"
                                        @submit="submitting = true">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                                        <input type="hidden" name="per_page" value="{{ $perPage }}">

                                        <div class="flex justify-center">
                                            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0-1 12a2 2 0 01-2 2H9a2 2 0 01-2-2L6 7h12z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-center font-bold text-lg text-gray-900">Delete this application?</p>
                                        <p class="text-center text-sm text-gray-500">This permanently removes <strong>{{ $founder->name }}</strong>'s account and startup entry ({{ $application->company_name }}). This can't be undone, and no notification is sent.</p>

                                        <div class="flex gap-3 pt-2">
                                            <button type="button" @click="step = null" :disabled="submitting" class="flex-1 border rounded-lg py-2.5 text-sm font-medium text-gray-700 disabled:opacity-50">Cancel</button>
                                            <button type="submit" :disabled="submitting"
                                                class="flex-1 bg-red-700 hover:bg-red-800 disabled:opacity-60 disabled:cursor-not-allowed text-white rounded-lg py-2.5 text-sm font-medium transition">
                                                <span x-show="!submitting">Confirm Delete</span>
                                                <span x-show="submitting">Processing…</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            {{-- ============ VIEW MODAL (read-only, decided applications) ============ --}}
                            <div x-show="step === 'view'" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" style="display: none;">
                                <div class="relative bg-white rounded-xl w-full max-w-3xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="step = null">
                                    <div class="bg-gradient-to-r from-rose-950 to-blue-950 text-white px-6 py-4 flex items-center justify-between flex-shrink-0">
                                        <h3 class="font-bold">View Founder Application</h3>
                                        <button type="button" @click="step = null" class="text-white/80 hover:text-white text-xl leading-none">&times;</button>
                                    </div>

                                    <div class="p-6 overflow-y-auto space-y-6">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <h4 class="font-bold text-sm text-gray-900 mb-3">Applicant Information</h4>
                                                <dl class="space-y-2 text-sm">
                                                    <div><dt class="text-gray-500 text-xs">Full Name</dt><dd class="font-medium">{{ $founder->name }}</dd></div>
                                                    <div><dt class="text-gray-500 text-xs">Email</dt><dd class="font-medium">{{ $founder->email }}</dd></div>
                                                    <div><dt class="text-gray-500 text-xs">Startup</dt><dd class="font-medium">{{ $application->company_name }}</dd></div>
                                                </dl>
                                            </div>
                                            <div class="border border-gray-200 rounded-lg p-4">
                                                <h4 class="font-bold text-sm text-gray-900 mb-3">Account Information</h4>
                                                <dl class="space-y-2 text-sm">
                                                    <div class="flex items-center justify-between">
                                                        <dt class="text-gray-500 text-xs">Email Verified</dt>
                                                        <dd>
                                                            @if ($founder->hasVerifiedEmail())
                                                                <span class="rounded-full border border-green-300 text-green-700 text-[11px] font-semibold px-2 py-0.5">● Verified</span>
                                                            @else
                                                                <span class="rounded-full border border-gray-300 text-gray-500 text-[11px] font-semibold px-2 py-0.5">Not Verified</span>
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div><dt class="text-gray-500 text-xs">Date Applied</dt><dd class="font-medium">{{ $founder->created_at->format('M j, Y g:i A') }}</dd></div>
                                                    <div><dt class="text-gray-500 text-xs">Application ID</dt><dd class="font-medium">{{ $application->application_id }}</dd></div>
                                                    @if ($founder->isApprovedAccount())
                                                        <div><dt class="text-gray-500 text-xs">Cohort</dt><dd class="font-medium">{{ $application->cohort?->display_label ?? ('Cohort '.$application->cohort_number) }}</dd></div>
                                                        <div><dt class="text-gray-500 text-xs">Portfolio Coordinator</dt><dd class="font-medium">{{ optional($application->activeCoordinatorAssignment?->coordinator)->display_name ?? '—' }}</dd></div>
                                                    @endif
                                                </dl>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 class="font-bold text-sm text-gray-900 mb-2">Status History</h4>
                                            <x-founder-application-timeline :startup="$application" />
                                        </div>

                                        @if ($founder->isRejected())
                                            <div>
                                                <h4 class="font-bold text-sm text-gray-900 mb-2">Reason for Rejection</h4>
                                                <div class="border border-gray-200 rounded-lg p-4 text-sm text-gray-700">{{ $application->rejection_reason }}</div>
                                            </div>
                                        @endif

                                        @if ($application->admin_remarks)
                                            <div>
                                                <h4 class="font-bold text-sm text-gray-900 mb-2">Additional Remarks</h4>
                                                <div class="border border-gray-200 rounded-lg p-4 text-sm text-gray-700">
                                                    {{ $application->admin_remarks }}
                                                    <p class="text-xs text-gray-400 mt-2">{{ optional($application->application_decided_at)->format('M j, Y g:i A') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500">No applications found for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($applications->total() > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3">
                <div class="flex items-center gap-1.5">
                    <a href="{{ $applications->previousPageUrl() ?? '#' }}"
                        @class([
                            'flex h-8 w-8 items-center justify-center rounded-md border text-sm transition',
                            'border-gray-200 text-gray-400 pointer-events-none opacity-50' => $applications->onFirstPage(),
                            'border-gray-300 text-gray-600 hover:bg-gray-50' => ! $applications->onFirstPage(),
                        ])>&lsaquo;</a>

                    @foreach (range(1, $applications->lastPage()) as $page)
                        <a href="{{ $applications->url($page) }}"
                            @class([
                                'flex h-8 w-8 items-center justify-center rounded-md border text-sm font-medium transition',
                                'border-transparent bg-[#6D0D23] text-white' => $page === $applications->currentPage(),
                                'border-gray-300 text-gray-600 hover:bg-gray-50' => $page !== $applications->currentPage(),
                            ])>{{ $page }}</a>
                    @endforeach

                    <a href="{{ $applications->nextPageUrl() ?? '#' }}"
                        @class([
                            'flex h-8 w-8 items-center justify-center rounded-md border text-sm transition',
                            'border-gray-200 text-gray-400 pointer-events-none opacity-50' => ! $applications->hasMorePages(),
                            'border-gray-300 text-gray-600 hover:bg-gray-50' => $applications->hasMorePages(),
                        ])>&rsaquo;</a>
                </div>

                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <label for="per_page" class="text-xs text-gray-500">Items per page</label>
                    <select id="per_page" name="per_page" onchange="this.form.submit()"
                        class="rounded-md border border-gray-300 py-1 pl-2 pr-6 text-xs text-gray-700">
                        @foreach ($perPageOptions as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif
    </div>

    @if (session('application_result'))
        <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" style="display: none;">
            <div class="relative bg-white rounded-xl w-full max-w-lg overflow-hidden">
                @php $result = session('application_result'); @endphp
                <div class="bg-gradient-to-r from-rose-950 to-blue-950 flex items-center justify-center py-8">
                    <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center">
                        <svg class="w-8 h-8 text-[#11386A]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <div class="p-8 text-center">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        {{ $result['type'] === 'approved' ? 'Application Approved' : 'Application Processed' }}
                    </h3>
                    <p class="text-gray-500 mb-6">
                        @if ($result['type'] === 'approved')
                            The founder has been successfully accepted into the cohort, and their welcome notification has been sent.
                        @else
                            The applicant has been moved to the rejected list and notified.
                        @endif
                    </p>
                    <button type="button" @click="open = false"
                        class="w-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white font-semibold rounded-full py-3 hover:opacity-90 transition">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-layouts.admin>
