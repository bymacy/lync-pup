<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} - PUP TBIDO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-gray-50">
    @php
        // Safely inline an SVG icon and make it recolorable via Tailwind text-color classes.
        // Falls back to an empty placeholder if the file doesn't exist yet, instead of crashing.
        $icon = function (string $name, string $class = 'w-5 h-5') {
            $path = public_path('images/icons/' . $name);

            if (! file_exists($path)) {
                return '<span class="' . $class . ' inline-block rounded bg-white/10"></span>';
            }

            $svg = file_get_contents($path);

            // Let Tailwind control size (CSS width/height beats SVG presentation attrs).
            $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 class="' . $class . ' block">', $svg, 1);

            // Force any hardcoded colors to inherit from the parent's text color.
            $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="currentColor"', $svg);
            $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="currentColor"', $svg);

            return $svg;
        };
    @endphp

    <div class="min-h-screen flex">
        <aside class="w-64 flex-shrink-0 bg-gradient-to-b from-[#6D0D23] to-[#11386A] text-white flex flex-col justify-between">
            <div>
                {{-- Logo / header --}}
                <div class="flex items-center gap-3 px-5 pt-6 pb-5">
                    <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center overflow-hidden flex-shrink-0">
                        <img src="/images/logo/logo-sidebar.png" alt="PUP TBIDO" class="w-8 h-8 object-contain">
                    </div>
                    <div>
                        <p class="font-bold text-sm leading-tight tracking-wide">PUP TBIDO</p>
                        <p class="text-[11px] text-white/60 leading-tight tracking-wide">ADMIN CONSOLE</p>
                    </div>
                </div>

                <div class="mx-5 border-b border-white/15"></div>

                {{-- Nav --}}
                <nav class="mt-4 space-y-1.5 px-3">
                    @php
                        $navItems = [
                            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard.svg'],
                            ['route' => 'admin.startups.index', 'label' => 'Startup Profile', 'icon' => 'startupProfile.svg'],
                            ['route' => 'admin.mentors.index', 'label' => 'Mentor Profile', 'icon' => 'mentorProfile.svg'],
                            ['route' => 'admin.coordinators.index', 'label' => 'Coordinator Profile', 'icon' => 'coordProfile.svg'],
                            ['route' => 'admin.assessment-hub.index', 'label' => 'Assessment Hub', 'icon' => 'assessmentHub.svg'],
                            ['route' => 'admin.roadblocks.index', 'label' => 'Roadblock Management', 'icon' => 'roadblock.svg'],
                            [
                                'route' => 'admin.risk-monitoring.index',
                                'label' => 'Risk Monitoring',
                                'icon' => 'riskMon.svg',
                                'hasUnseen' => false,
                            ],
                        ];
                    @endphp

                    @foreach ($navItems as $item)
                        @php $isActive = request()->routeIs($item['route'] . '*'); @endphp
                        <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                                {{ $isActive
                                    ? 'bg-white text-[#6D0D23] shadow-sm'
                                    : 'text-white/85 hover:bg-white/10 hover:text-white' }}">

                            <span class="w-5 h-5 flex items-center justify-center flex-shrink-0">
                                {!! $icon($item['icon']) !!}
                            </span>

                            <span class="flex-1">
                                {{ $item['label'] }}
                            </span>

                            @if (!empty($item['hasUnseen']))
                                <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Footer --}}
            <div class="border-t border-white/15">
                <div class="flex items-center gap-3 px-5 py-4">
                    <div class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center flex-shrink-0">
                        <span class="w-4 h-4 text-white">
                            {!! $icon('user.svg', 'w-4 h-4') !!}
                        </span>
                    </div>
                    <div class="text-xs leading-tight">
                        <p class="font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-white/60">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="px-5 pb-5">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 text-sm text-white/80 hover:text-white transition">
                        <span class="w-4 h-4 text-white/80">
                            {!! $icon('signout.svg', 'w-4 h-4') !!}
                        </span>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 p-8 overflow-y-auto">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>

</html>