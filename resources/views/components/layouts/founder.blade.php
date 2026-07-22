<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Founder Portal' }} - LYNC PUP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-gray-50">
    @php
    $icon = function (string $name, string $class = 'w-5 h-5') {
    $path = public_path('images/icons/' . $name);

    if (! file_exists($path)) {
    return '<span class="'.$class.' inline-block rounded bg-white/10"></span>';
    }

    $svg = file_get_contents($path);

    $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 class="'.$class.' block">', $svg, 1);
            $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="currentColor"', $svg);
            $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="currentColor"', $svg);

            return $svg;
            };
            @endphp

            <div class="h-screen flex overflow-hidden">
                <aside class="w-64 h-screen sticky top-0 flex-shrink-0 bg-gradient-to-b from-[#6D0D23] to-[#11386A] text-white flex flex-col justify-between overflow-hidden">
                    <div>
                        <div class="flex items-center gap-3 px-5 pt-6 pb-5">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <img src="/images/logo/logo-sidebar.png"
                                    alt="LYNC PUP"
                                    class="w-8 h-8 object-contain">
                            </div>

                            <div>
                                <p class="font-bold text-sm leading-tight tracking-wide">
                                    LYNC PUP
                                </p>
                                <p class="text-[11px] text-white/60 leading-tight tracking-wide">
                                    FOUNDER PORTAL
                                </p>
                            </div>
                        </div>

                        <div class="mx-5 border-b border-white/15"></div>

                        <nav class="mt-2 space-y-1 px-3">
                            @php
                            $navItems = [
                            [
                            'route' => 'startup.dashboard',
                            'label' => 'Dashboard',
                            'icon' => 'dashboard.svg',
                            ],
                            [
                            'route' => 'startup.profile.edit',
                            'label' => 'Startup Profile',
                            'icon' => 'startupProfile.svg',
                            ],
                            [
                            'route' => 'startup.information-sheet.edit',
                            'label' => 'Information Sheet',
                            'icon' => 'info-sheet.svg',
                            ],
                            [
                            'route' => 'startup.meetings.index',
                            'label' => 'Meeting',
                            'icon' => 'coordProfile.svg',
                            ],
                            [
                            'route' => 'startup.submissions.index',
                            'label' => 'Submission',
                            'icon' => 'assessmentHub.svg',
                            ],
                            [
                            'route' => 'startup.readiness.index',
                            'label' => 'Readiness Result',
                            'icon' => 'riskMon.svg',
                            ],
                            ];
                            @endphp

                            @foreach ($navItems as $item)
                            @php $isActive = request()->routeIs($item['route'].'*'); @endphp
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
                            </a>
                            @endforeach
                        </nav>
                    </div>

                    <div class="border-t border-white/15">
                        <div class="flex items-center gap-3 px-5 py-4">
                            <div class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center flex-shrink-0">
                                <span class="text-white">
                                    {!! $icon('mentorProfile.svg', 'w-5 h-5') !!}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-white/60 truncate">{{ auth()->user()->email }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button
                                type="submit"
                                class="w-full flex items-center gap-3 px-5 py-3 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-all duration-200">

                                {!! $icon('sign-out.svg', 'w-4 h-4 flex-shrink-0') !!}

                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </aside>

                <main class="flex-1 h-screen overflow-y-auto p-8">
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