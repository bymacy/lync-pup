<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} - PUP TBIDO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-data class="antialiased bg-gray-50">

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

            $navItems = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard.svg'],
            ['route' => 'admin.startups.index', 'label' => 'Startup Profile', 'icon' => 'startupProfile.svg'],
            ['route' => 'admin.founder-applications.index', 'label' => 'Founder Application', 'icon' => 'founderApplication.svg'],
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
                        {{-- Logo / header --}}
                        <div class="flex items-center gap-3 px-5 pt-6 pb-5">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <img src="/images/logo/logo-sidebar.png" alt="PUP TBIDO" class="w-8 h-8 object-contain">
                            </div>

                            <div class="min-w-0">
                                <p class="font-bold text-sm leading-tight tracking-wide">PUP TBIDO</p>
                                <p class="text-[11px] text-white/60 leading-tight tracking-wide">ADMIN CONSOLE</p>
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

                        {{-- Nav --}}
                        <nav class="mt-4 space-y-1.5 px-3">
                            @foreach ($navItems as $item)
                            @php $isActive = request()->routeIs($item['route'] . '*'); @endphp

                            <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                                @click="sidebarOpen = false"
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
                                <span class="text-white">
                                    {!! $icon('login-admin.svg', 'w-5 h-5') !!}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-white/60 truncate">{{ auth()->user()->email }}</p>
                            </div>
                        </div>

                        {{-- Sign out. The button no longer submits directly — signing out mid-task
                     is easy to do by accident on a nav item this close to the others. --}}
                        <div x-data="{ confirmSignOut: false }">
                            <button
                                type="button"
                                @click="confirmSignOut = true"
                                class="w-full flex items-center gap-3 px-5 py-3 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-all duration-200">

                                {!! $icon('sign-out.svg', 'w-4 h-4 flex-shrink-0') !!}

                                <span>Sign Out</span>
                            </button>

                            {{-- Teleported to body on purpose: <aside> carries translate-x-*, and a
                         transform makes an element the containing block for fixed-position
                         descendants. Left in place, this overlay would be trapped inside the
                         256px sidebar instead of covering the viewport. --}}
                            <template x-teleport="body">
                                <div x-show="confirmSignOut" x-cloak x-transition.opacity
                                    @keydown.escape.window="confirmSignOut = false"
                                    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4"
                                    style="display:none;">

                                    <div @click.outside="confirmSignOut = false"
                                        class="relative w-full max-w-lg rounded-2xl bg-white px-8 pb-8 pt-10 text-center shadow-2xl">

                                        <button type="button" @click="confirmSignOut = false"
                                            class="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full border border-[#6D0D23] text-[#6D0D23] transition hover:bg-[#6D0D23] hover:text-white"
                                            aria-label="Close">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                                            </svg>
                                        </button>

                                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white">
                                            {!! $icon('sign-out.svg', 'w-6 h-6') !!}
                                        </div>

                                        <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-2xl font-bold text-transparent">
                                            Sign Out
                                        </h3>

                                        <p class="mt-2 text-sm text-gray-600">
                                            Are you sure you want to sign out?<br>
                                            You'll need to log in again to continue.
                                        </p>

                                        <div class="mt-6 grid grid-cols-2 gap-4">
                                            <button type="button" @click="confirmSignOut = false"
                                                class="h-11 rounded-lg border border-[#6D0D23] bg-white text-sm font-bold text-[#6D0D23] transition hover:bg-rose-50">
                                                Cancel
                                            </button>

                                            {{-- A real POST form, not a link: Laravel's logout route
                                         rejects GET, and the CSRF token has to ride along. --}}
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit"
                                                    class="h-11 w-full rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-95">
                                                    Sign Out
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </aside>

                <main class="flex-1 h-screen overflow-y-auto">

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
                            <img src="/images/logo/logo-sidebar.png" alt="PUP TBIDO" class="w-7 h-7 object-contain">
                        </div>

                        <div class="min-w-0">
                            <p class="font-bold text-sm leading-tight tracking-wide">PUP TBIDO</p>
                            <p class="text-[11px] text-white/60 leading-tight tracking-wide">ADMIN CONSOLE</p>
                        </div>
                    </div>

                    @if (session('status'))
                    <div
                        x-data="{ show: true }"
                        x-init="setTimeout(() => show = false, 4000)"
                        x-show="show"
                        x-transition:enter="transform transition ease-out duration-300"
                        x-transition:enter-start="translate-x-full opacity-0"
                        x-transition:enter-end="translate-x-0 opacity-100"
                        x-transition:leave="transform transition ease-in duration-200"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="translate-x-full opacity-0"
                        class="fixed top-20 right-4 lg:top-6 lg:right-6 z-[9999]">

                        <div class="flex items-center gap-4 rounded-xl bg-white shadow-2xl border border-gray-200 px-5 py-4 w-[calc(100vw-2rem)] max-w-sm sm:min-w-[360px]">

                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-white">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-900">
                                    Success
                                </h4>

                                <p class="text-sm text-gray-500">
                                    {{ session('status') }}
                                </p>
                            </div>

                            <button
                                @click="show = false"
                                class="text-gray-400 hover:text-gray-700 transition text-xl leading-none">
                                &times;
                            </button>

                        </div>

                    </div>
                    @endif

                    @if ($errors->any())
                    <div x-data x-init="$store.toast.error('Please check your input', @js($errors->first()))"></div>
                    @endif

                    @if (session('error'))
                    <div x-data x-init="$store.toast.error('Error', @js(session('error')))"></div>
                    @endif

                    <div
                        x-show="$store.toast.show"
                        x-cloak
                        x-transition:enter="transform transition ease-out duration-300"
                        x-transition:enter-start="translate-x-full opacity-0"
                        x-transition:enter-end="translate-x-0 opacity-100"
                        x-transition:leave="transform transition ease-in duration-200"
                        x-transition:leave-start="translate-x-0 opacity-100"
                        x-transition:leave-end="translate-x-full opacity-0"
                        class="fixed top-20 right-4 lg:top-6 lg:right-6 z-[9999]">

                        <div class="flex items-center gap-4 rounded-xl bg-white shadow-2xl border border-gray-200 px-5 py-4 w-[calc(100vw-2rem)] max-w-sm sm:min-w-[360px]">

                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-white shadow-lg"
                                :class="{
                            'bg-gradient-to-r from-[#6D0D23] to-[#11386A]': $store.toast.type === 'success',
                            'bg-gradient-to-r from-red-600 to-red-700': $store.toast.type === 'error',
                            'bg-gradient-to-r from-amber-500 to-orange-500': $store.toast.type === 'warning',
                            'bg-gradient-to-r from-sky-500 to-blue-600': $store.toast.type === 'info',
                        }">
                                <template x-if="$store.toast.type === 'success'"><span class="text-xl font-bold">&#10003;</span></template>
                                <template x-if="$store.toast.type === 'error'"><span class="text-xl font-bold">&#10005;</span></template>
                                <template x-if="$store.toast.type === 'warning'"><span class="text-xl font-bold">!</span></template>
                                <template x-if="$store.toast.type === 'info'"><span class="text-xl font-bold">i</span></template>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900" x-text="$store.toast.title"></p>
                                <p class="text-sm text-gray-500" x-text="$store.toast.message"></p>
                            </div>

                            <button
                                @click="$store.toast.hide()"
                                class="text-gray-400 hover:text-gray-700 transition text-xl leading-none">
                                &times;
                            </button>

                        </div>

                    </div>

                    <div class="p-4 sm:p-6 lg:p-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
</body>

</html>