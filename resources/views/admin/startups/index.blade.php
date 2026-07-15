<x-layouts.admin title="Startup Profile">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Startup Profile</h1>
        <p class="text-gray-500 mt-1">Monitor readiness, detect weak spots, and act on each startup.</p>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
            <p class="text-gray-600 text-sm">Total Startup</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['total'] }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
            <p class="text-gray-600 text-sm">Active</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['active'] }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-gray-600 text-sm">Assign Coordinator</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['needsCoordinator'] }}</p>
        </div>
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-5">
            <p class="text-gray-600 text-sm">Pending</p>
            <p class="text-4xl font-bold mt-1">{{ $totals['pending'] }}</p>
        </div>
    </div>

    <div class="flex gap-8 border-b border-gray-200 mb-6">
        @foreach (['all' => 'All', 'active' => 'Active', 'assign-coordinator' => 'Assign Coordinator', 'pending' => 'Pending'] as $key => $label)
            <a href="{{ route('admin.startups.index', ['tab' => $key]) }}"
               class="pb-3 text-sm font-medium border-b-2 -mb-px
                      {{ $activeTab === $key ? 'border-rose-800 text-rose-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
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