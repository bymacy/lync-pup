<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Founder Portal' }} - LYNC PUP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <aside class="w-64 flex-shrink-0 bg-gradient-to-b from-rose-950 to-blue-950 text-white flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 p-6">
                    <img src="/images/lync-pup-logo.png" alt="LYNC PUP" class="w-9 h-9">
                    <div>
                        <p class="font-bold text-sm leading-tight">LYNC PUP</p>
                        <p class="text-xs text-white/60 leading-tight">FOUNDER PORTAL</p>
                    </div>
                </div>

                <nav class="mt-2 space-y-1 px-3">
                    @php
                        $navItems = [
                            ['route' => 'startup.dashboard', 'label' => 'Dashboard'],
                            ['route' => 'startup.profile.edit', 'label' => 'Startup Profile'],
                            ['route' => 'startup.information-sheet.edit', 'label' => 'Information Sheet'],
                            ['route' => 'startup.meetings.index', 'label' => 'Meeting'],
                            ['route' => 'startup.submissions.index', 'label' => 'Submission'],
                            ['route' => 'startup.readiness.index', 'label' => 'Readiness Result'],
                        ];
                    @endphp

                    @foreach ($navItems as $item)
                        @php $isActive = request()->routeIs($item['route'].'*'); @endphp
                        <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                                  {{ $isActive ? 'bg-white text-gray-900' : 'text-white/80 hover:bg-white/10' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 rounded-full bg-white/20"></div>
                    <div class="text-xs">
                        <p class="font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-white/60">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-white/80 hover:text-white">Sign Out</button>
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