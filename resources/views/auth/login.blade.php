<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - LYNC PUP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen flex" x-data="{ activeTab: 'Startup' }">

        {{-- Left panel --}}
        <div class="hidden lg:flex lg:w-1/2 relative bg-[#5c0f1e] text-white flex-col justify-between p-10 overflow-hidden bg-cover bg-center"
             style="background-image: linear-gradient(rgba(60,10,20,0.85), rgba(60,10,20,0.9)), url('/images/tbido-building.jpg');">

            <a href="{{ url('/') }}" class="text-white/80 hover:text-white w-fit">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>

            <div>
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center">
                        <img src="/images/lync-pup-logo.png" alt="LYNC PUP" class="w-8 h-8">
                    </div>
                    <div>
                        <p class="font-bold tracking-wide leading-tight">LYNC PUP</p>
                        <p class="text-xs text-white/70 leading-tight">Startup Incubation Management System</p>
                    </div>
                </div>

                <h1 class="text-4xl font-bold leading-tight mb-2">Empowering Ideas.</h1>
                <h1 class="text-4xl font-bold text-amber-400 leading-tight mb-4">Building the future.</h1>
                <p class="text-white/80 max-w-sm mb-8">
                    The PUP Technology Business Incubator nurtures innovative startups and connects
                    them with mentors and opportunities for growth.
                </p>

                <div class="w-16 h-0.5 bg-amber-400 mb-8"></div>

                <div class="space-y-4">
                    @foreach ([
                        ['icon' => 'check-square', 'title' => 'Readiness', 'desc' => 'Track TRL, MRL, TMRL & SRL signals across every venture.'],
                        ['icon' => 'trending-up', 'title' => 'Progress Analytics', 'desc' => 'Identify at-risk ventures through real-time monitoring'],
                        ['icon' => 'check-square', 'title' => 'Mentoring', 'desc' => 'Connect with experts to clear roadblocks.'],
                        ['icon' => 'trending-up', 'title' => 'Centralized', 'desc' => 'Incubation lifecycle through a unified growth portal.'],
                    ] as $feature)
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                                <div class="w-4 h-4 border-2 border-amber-400 rounded-sm"></div>
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ $feature['title'] }}</p>
                                <p class="text-xs text-white/60">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div></div>
        </div>

        {{-- Right panel --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center bg-white p-6">
            <div class="w-full max-w-sm">

                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-rose-50 flex items-center justify-center">
                        <img src="/images/lync-pup-icon.png" alt="LYNC PUP" class="w-9 h-9">
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-center text-gray-900 mb-1">Welcome back!</h2>
                <p class="text-sm text-gray-500 text-center mb-6">
                    Founders are admitted via TBIDO. Use your issued account to continue.
                </p>

                @if (session('status'))
                    <div class="mb-4 text-sm font-medium text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Tab toggle --}}
                <div class="flex bg-gray-100 rounded-lg p-1 mb-6">
                    <button type="button"
                            @click="activeTab = 'Startup'"
                            :class="activeTab === 'Startup' ? 'bg-white shadow text-gray-900' : 'text-gray-500'"
                            class="flex-1 flex items-center justify-center gap-2 text-sm font-medium py-2 rounded-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Founder
                    </button>
                    <button type="button"
                            @click="activeTab = 'Admin'"
                            :class="activeTab === 'Admin' ? 'bg-white shadow text-gray-900' : 'text-gray-500'"
                            class="flex-1 flex items-center justify-center gap-2 text-sm font-medium py-2 rounded-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Admin
                    </button>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="role" :value="activeTab">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="relative">
                            <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                   :placeholder="activeTab === 'Startup' ? 'founder@startup.ph' : 'admin@startup.ph'"
                                   class="w-full pl-9 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-800 focus:border-rose-800">
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm text-rose-800 hover:underline">Forgot Password?</a>
                            @endif
                        </div>
                        <div class="relative" x-data="{ show: false }">
                            <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required
                                   class="w-full pl-9 pr-9 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-800 focus:border-rose-800">
                            <button type="button" @click="show = !show" class="absolute right-3 top-2.5 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input id="remember" type="checkbox" name="remember"
                               class="rounded border-gray-300 text-rose-800 focus:ring-rose-800">
                        <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                    </div>

                    <button type="submit"
                            class="w-full bg-rose-900 hover:bg-rose-950 text-white font-semibold py-3 rounded-lg transition">
                        Sign in as <span x-text="activeTab === 'Startup' ? 'Founder' : 'Admin'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>