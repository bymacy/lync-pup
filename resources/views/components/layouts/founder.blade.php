<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Founder Portal' }} - LYNC PUP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-data class="antialiased bg-gray-50">

    @php
    $icon = function (string $name, string $class = 'w-5 h-5') {
    $path = public_path('images/icons/' . $name);

    if (! file_exists($path)) {
    return '<span class="' . $class . ' inline-block rounded bg-white/10"></span>';
    }

    $svg = file_get_contents($path);

    $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 class="' . $class . ' block">', $svg, 1);
            $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="currentColor"', $svg);
            $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="currentColor"', $svg);

            return $svg;
            };

            $navItems = [
            ['route' => 'startup.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard.svg'],
            ['route' => 'startup.profile.edit', 'label' => 'Startup Profile', 'icon' => 'startupProfile.svg'],
            ['route' => 'startup.information-sheet.edit', 'label' => 'Information Sheet', 'icon' => 'info-sheet.svg'],
            ['route' => 'startup.meetings.index', 'label' => 'Meeting', 'icon' => 'coordProfile.svg'],
            ['route' => 'startup.submissions.index', 'label' => 'Submission', 'icon' => 'assessmentHub.svg'],
            ['route' => 'startup.readiness.index', 'label' => 'Readiness Result', 'icon' => 'riskMon.svg'],
            ];
            @endphp

            <div
                class="h-screen flex overflow-hidden"
                x-data="{ sidebarOpen: false }"
                @keydown.escape.window="sidebarOpen = false">

                {{-- Mobile backdrop --}}
                <div
                    x-show="sidebarOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="sidebarOpen = false"
                    class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

                {{-- Sidebar: drawer under lg, sticky column at lg and up --}}
                <aside
                    class="w-64 bg-gradient-to-b from-[#6D0D23] to-[#11386A] text-white flex flex-col justify-between overflow-y-auto
                    fixed inset-y-0 left-0 z-50 -translate-x-full transition-transform duration-300 ease-in-out
                    lg:sticky lg:top-0 lg:h-screen lg:flex-shrink-0 lg:translate-x-0"
                    :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
                    <div>
                        <div class="flex items-center gap-3 px-5 pt-6 pb-5">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <img src="/images/logo/logo-sidebar.png"
                                    alt="LYNC PUP"
                                    class="w-8 h-8 object-contain">
                            </div>

                            <div class="min-w-0">
                                <p class="font-bold text-sm leading-tight tracking-wide">
                                    LYNC PUP
                                </p>
                                <p class="text-[11px] text-white/60 leading-tight tracking-wide">
                                    FOUNDER PORTAL
                                </p>
                            </div>

                            {{-- Close drawer (mobile only) --}}
                            <button
                                type="button"
                                @click="sidebarOpen = false"
                                aria-label="Close menu"
                                class="lg:hidden ml-auto -mr-1 p-1.5 rounded-lg text-white/70 hover:bg-white/10 hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mx-5 border-b border-white/15"></div>

                        <nav class="mt-2 space-y-1 px-3">
                            @foreach ($navItems as $item)
                            @php $isActive = request()->routeIs($item['route'] . '*'); @endphp

                            <a
                                href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                @click="
                                if ($store.navigation.hasUnsavedChanges) {
                                    $event.preventDefault();
                                    $store.navigation.nextUrl = $el.href;
                                    $store.navigation.showLeaveModal = true;
                                } else {
                                    sidebarOpen = false;
                                }
                            "
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

                <main class="flex-1 h-screen overflow-y-auto">

                    {{-- Mobile top bar --}}
                    {{-- Mobile top bar --}}
                    <div class="lg:hidden sticky top-0 z-30 flex items-center gap-3 bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white px-4 py-3 shadow-sm">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            aria-label="Open menu"
                            class="-ml-2 p-2 rounded-lg text-white/85 hover:bg-white/10 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                            </svg>
                        </button>

                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center overflow-hidden flex-shrink-0">
                            <img src="/images/logo/logo-sidebar.png" alt="LYNC PUP" class="w-7 h-7 object-contain">
                        </div>

                        <div class="min-w-0">
                            <p class="font-bold text-sm leading-tight tracking-wide">LYNC PUP</p>
                            <p class="text-[11px] text-white/60 leading-tight tracking-wide">FOUNDER PORTAL</p>
                        </div>
                    </div>

                    @if (session('status'))
                    <div
                        x-data="{ show: true }"
                        x-init="setTimeout(() => show = false, 3000)"
                        x-show="show"
                        x-transition:enter="transform ease-out duration-300"
                        x-transition:enter-start="translate-x-full opacity-0"
                        x-transition:enter-end="translate-x-0 opacity-100"
                        x-transition:leave="transform ease-in duration-200"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="translate-x-full opacity-0"
                        class="fixed top-20 right-4 lg:top-6 lg:right-6 z-50">

                        <div class="flex items-center gap-3 rounded-xl border border-[#6D0D23]/20 bg-white shadow-xl px-5 py-4 w-[calc(100vw-2rem)] max-w-sm sm:min-w-[340px]">

                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white shadow-lg ring-2 ring-white">
                                <span class="text-xl font-bold">✓</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">
                                    Success
                                </p>

                                <p class="text-sm text-gray-600">
                                    {{ session('status') }}
                                </p>
                            </div>

                            <button
                                @click="show = false"
                                class="text-gray-400 hover:text-gray-600 transition">
                                ✕
                            </button>
                        </div>
                    </div>
                    @endif

                    <div
                        x-show="$store.toast.show"
                        x-cloak
                        x-transition:enter="transform ease-out duration-300"
                        x-transition:enter-start="translate-x-full opacity-0"
                        x-transition:enter-end="translate-x-0 opacity-100"
                        x-transition:leave="transform ease-in duration-200"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="translate-x-full opacity-0"
                        class="fixed top-20 right-4 lg:top-6 lg:right-6 z-50">

                        <div class="flex items-center gap-3 rounded-xl border border-[#6D0D23]/20 bg-white shadow-xl px-5 py-4 w-[calc(100vw-2rem)] max-w-sm sm:min-w-[340px]">

                            <div
                                class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-white text-xl font-bold shadow-lg ring-2 ring-white"
                                :class="{
                            'bg-gradient-to-r from-[#6D0D23] to-[#11386A]': $store.toast.type === 'success',
                            'bg-gradient-to-r from-red-600 to-red-700': $store.toast.type === 'error',
                            'bg-gradient-to-r from-amber-500 to-orange-500': $store.toast.type === 'warning',
                            'bg-gradient-to-r from-sky-500 to-blue-600': $store.toast.type === 'info'
                        }">
                                <span x-text="{
                            success: '✓',
                            error: '✕',
                            warning: '!',
                            info: 'i'
                        }[$store.toast.type] ?? '✓'"></span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">
                                    <span x-text="$store.toast.title"></span>
                                </p>

                                <p class="text-sm text-gray-600">
                                    <span x-text="$store.toast.message"></span>
                                </p>
                            </div>

                            <button
                                @click="$store.toast.hide()"
                                class="text-gray-400 hover:text-gray-600">
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6 lg:p-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>

            {{-- Unsaved changes modal --}}
            <div
                x-data
                x-show="$store.navigation.showLeaveModal"
                x-cloak
                class="fixed inset-0 z-[999] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">

                <div
                    @click.outside="$store.navigation.showLeaveModal = false"
                    class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">

                    <button
                        type="button"
                        @click="$store.navigation.showLeaveModal = false"
                        class="absolute right-4 top-4 text-gray-400 hover:text-gray-600">
                        ✕
                    </button>

                    <div class="flex justify-center mb-4">
                        <div class="h-14 w-14 rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] flex items-center justify-center text-white text-xl font-bold">
                            !
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-center text-[#5B1933]">
                        Unsaved Changes
                    </h2>

                    <p class="mt-2 text-center text-sm text-gray-600">
                        You have unsaved changes.
                        If you leave this page, your edits will be lost.
                    </p>

                    <div class="mt-6 flex gap-3">
                        <button
                            type="button"
                            @click="$store.navigation.showLeaveModal = false"
                            class="flex-1 rounded-lg border border-gray-300 py-2.5 font-medium text-gray-700 hover:bg-gray-50">
                            Stay
                        </button>

                        <button
                            type="button"
                            @click="
                        $store.navigation.hasUnsavedChanges = false;
                        $store.navigation.showLeaveModal = false;
                        window.location = $store.navigation.nextUrl;
                    "
                            class="flex-1 rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] py-2.5 font-medium text-white">
                            Leave
                        </button>
                    </div>
                </div>
            </div>
</body>

</html>