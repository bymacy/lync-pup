<x-layouts.admin :title="$startup->company_name">
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('admin.startups.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Startup Profile</a>
    </div>

    <div class="rounded-2xl overflow-hidden mb-8 shadow-sm">
    <div class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white px-8 py-7">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-xl bg-white/10 flex items-center justify-center text-4xl font-bold">
                    {{ substr($startup->company_name, 0, 1) }}
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-4xl font-bold">{{ $startup->company_name }}</h1>
                        <span class="text-xs font-medium bg-white/95 text-[#6D0D23] rounded-full px-4 py-1 text-xs font-semibold">{{ $startup->status }}</span>
                    </div>
                    <p class="text-white/90 text-sm mt-2 leading-relaxed">{{ $startup->informationSheet?->business_description }}</p>
                    <p class="text-white/60 text-sm mt-2">{{ $startup->industry_sector }} · Cohort {{ $startup->cohort_number }} · {{ $startup->location }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="font-bold text-gray-900 mb-2">About</h2>
                <p class="text-gray-600 text-sm">{{ $startup->informationSheet?->business_description ?? 'No information sheet submitted yet.' }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-gray-900">Readiness Level</h2>
                    <span class="text-sm text-gray-500">Pre-Assessment</span>
                </div>

                @if ($startup->latestReadinessAssessment)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <x-readiness-radar
                        :trl="$startup->latestReadinessAssessment->trl_score"
                        :mrl="$startup->latestReadinessAssessment->mrl_score"
                        :tmrl="$startup->latestReadinessAssessment->tmrl_score"
                        :srl="$startup->latestReadinessAssessment->srl_score" />
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-gray-200 p-4 shadow-sm">
                            <p class="text-xs text-gray-500">TECHNOLOGY</p>
                            <p class="text-xl font-bold">TRL {{ $startup->latestReadinessAssessment->trl_score }}<span class="text-sm text-gray-400">/9</span></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4 shadow-sm">
                            <p class="text-xs text-gray-500">MANUFACTURING</p>
                            <p class="text-xl font-bold">MRL {{ $startup->latestReadinessAssessment->mrl_score }}<span class="text-sm text-gray-400">/9</span></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4 shadow-sm">
                            <p class="text-xs text-gray-500">TEAM & MGMT</p>
                            <p class="text-xl font-bold">TMRL {{ $startup->latestReadinessAssessment->tmrl_score }}<span class="text-sm text-gray-400">/9</span></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4 shadow-sm">
                            <p class="text-xs text-gray-500">SYSTEM / MARKET</p>
                            <p class="text-xl font-bold">SRL {{ $startup->latestReadinessAssessment->srl_score }}<span class="text-sm text-gray-400">/9</span></p>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-4">Composite RLS score: <strong>{{ number_format($startup->latestReadinessAssessment->overall_score, 1) }}/9</strong></p>
                @else
                <p class="text-sm text-gray-500">No readiness assessment has been conducted yet.</p>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <h2 class="font-bold text-gray-900 mb-4">Team</h2>
                <div class="grid grid-cols-2 gap-3">
                    @forelse ($startup->teamMembers as $member)
                    <div class="bg-gray-100 rounded-lg px-4 py-2 text-sm">
                        {{ $member->full_name }} @if($member->role) <span class="text-gray-500">({{ $member->role }})</span> @endif
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 col-span-2">No team members listed yet.</p>
                    @endforelse
                </div>
            </div>

            <div id="assign-coordinator" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-900">Profile Coordinator</h2>
                </div>

                @if ($startup->activeCoordinatorAssignment)
                <div class="border border-gray-300 rounded-lg px-4 py-2 text-sm hover:border-[#6D0D23] transition">
                    {{ $startup->activeCoordinatorAssignment->coordinator->name }}
                </div>
                @else
                <x-coordinator-assign-modal :startup="$startup" />
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-[#F2B8C2] shadow-sm p-6 h-fit">
            <h2 class="font-bold text-gray-900 mb-4">Contact & Links</h2>
            <div class="space-y-3 text-sm text-gray-700">
                <p>{{ $startup->user->email }}</p>
                <p>{{ $startup->contact_phone }}</p>
                <p>{{ $startup->location }}</p>
            </div>
            @if ($startup->status === 'Pending')
            <a href="{{ route('admin.information-sheet.show', $startup) }}"
                class="mt-6 block text-center bg-rose-900 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-rose-950">
                View Information Sheet
            </a>
            @endif
        </div>
    </div>
</x-layouts.admin>