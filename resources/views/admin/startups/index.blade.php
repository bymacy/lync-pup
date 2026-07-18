<x-layouts.admin title="Startup Profile">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Startup Profile</h1>
        <p class="text-gray-500 mt-1">Monitor readiness, detect weak spots, and act on each startup.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl border border-[#F7C5CF] bg-[#FFE8EE] p-5">
            <p class="text-gray-600 text-sm">Total Startup</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['total'] }}</p>
        </div>
        <div class="rounded-xl border border-[#B8D7FF] bg-[#CDE2FF] p-5">
            <p class="text-gray-600 text-sm">Active</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['active'] }}</p>
        </div>
        <div class="rounded-xl border border-[#F5D27B] bg-[#FFE2AA] p-5">
            <p class="text-gray-600 text-sm">Assign Coordinator</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['needsCoordinator'] }}</p>
        </div>
        <div class="rounded-xl border border-[#DEC8FF] bg-[#E3D4FF] p-5">
            <p class="text-gray-600 text-sm">Pending</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['pending'] }}</p>
        </div>
    </div>


    <div class="border-b border-gray-300 mb-8">
        <nav class="flex overflow-x-auto whitespace-nowrap">
            @foreach ([
            'all' => 'All',
            'active' => 'Active',
            'assign-coordinator' => 'Assign Coordinator',
            'pending' => 'Pending'
            ] as $key => $label)

            <a
                href="{{ route('admin.startups.index', ['tab' => $key]) }}"
                class="
                    px-6 sm:px-10 lg:px-16
                    py-3
                    text-sm
                    font-medium
                    border-b-2
                    -mb-px
                    transition-colors duration-200
                    {{ $activeTab === $key
                        ? 'border-[#6D0D23] text-[#6D0D23]'
                        : 'border-transparent text-gray-700 hover:text-[#6D0D23]'
                    }}
                ">
                {{ $label }}
            </a>

            @endforeach
        </nav>
    </div>

    <div
    class="grid gap-6"
    style="grid-template-columns: repeat(auto-fit, minmax(290px, 320px));">
        @forelse ($startups as $startup)
        <x-startup-card :startup="$startup" />
        @empty
        <p class="text-gray-500 col-span-full">No startups found for this filter.</p>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $startups->links() }}
    </div>
</x-layouts.admin>