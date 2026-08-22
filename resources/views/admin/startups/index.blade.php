<x-layouts.admin title="Startup Profile">

    @php
    // The layout is a component, so its $icon closure doesn't reach this view.
    // Redeclared here: rewrites fills and strokes to currentColor so the silhouette
    // inherits its color from the wrapper, and renders an empty span rather than
    // erroring if a file is missing.
    $icon = function (string $name, string $class = 'w-4 h-4') {
    $path = public_path('images/icons/' . $name);

    if (! file_exists($path)) {
    return '<span class="' . $class . ' inline-block"></span>';
    }

    $svg = file_get_contents($path);

    $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 class="' . $class . ' block">', $svg, 1);
            $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="currentColor"', $svg);
            $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="currentColor"', $svg);

            return $svg;
            };

            // One definition per card so the four stay structurally identical — a change to
            // padding or watermark size happens once, not four times.
            $stats = [
            ['label' => 'Total Startup', 'value' => $totals['total'], 'icon' => '3person.svg', 'border' => 'border-[#F7C5CF]', 'bg' => 'bg-[#FFE8EE]'],
            ['label' => 'Active', 'value' => $totals['active'], 'icon' => 'personcheck.svg', 'border' => 'border-[#B8D7FF]', 'bg' => 'bg-[#CDE2FF]'],
            ['label' => 'Assign Coordinator', 'value' => $totals['needsCoordinator'], 'icon' => 'mentorProfile.svg', 'border' => 'border-[#F5D27B]', 'bg' => 'bg-[#FFE2AA]'],
            ['label' => 'Pending', 'value' => $totals['pending'], 'icon' => 'profileArrow.svg', 'border' => 'border-[#DEC8FF]', 'bg' => 'bg-[#E3D4FF]'],
            ];
            @endphp

            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Startup Profile</h1>
                <p class="text-gray-500 mt-1">Monitor readiness, detect weak spots, and act on each startup.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
                @foreach ($stats as $stat)
                {{-- relative + overflow-hidden are what let the silhouette bleed off the card
             edge without spilling into the grid gap. --}}
                <div class="relative overflow-hidden rounded-xl border {{ $stat['border'] }} {{ $stat['bg'] }} p-5">

                    {{-- Watermark. aria-hidden because it carries no meaning — the label and
                 number already say everything. black/10 rather than a tinted color so
                 the same value reads correctly on all four card backgrounds. --}}
                    <span aria-hidden="true"
                        class="pointer-events-none absolute -bottom-4 right-2 text-black/10 [&>svg]:h-28 [&>svg]:w-28">
                        {!! $icon($stat['icon'], 'h-28 w-28') !!}
                    </span>

                    {{-- relative lifts the text above the watermark without needing z-index
                 on the watermark itself. --}}
                    <div class="relative">
                        <p class="text-gray-600 text-sm">{{ $stat['label'] }}</p>
                        <p class="text-4xl font-bold mt-1">{{ $stat['value'] }}</p>
                    </div>
                </div>
                @endforeach
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