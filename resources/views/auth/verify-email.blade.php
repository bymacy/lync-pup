<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email - LYNC PUP</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="antialiased font-['Poppins'] bg-white">
    <div class="min-h-screen flex items-center justify-center p-6"
        x-data="{ secondsLeft: 60, init() { this.tick(); } , tick() { setInterval(() => { if (this.secondsLeft > 0) this.secondsLeft--; }, 1000); } }">
        <div class="w-full max-w-md">

            <a href="{{ route('login') }}" class="inline-flex text-gray-500 hover:text-gray-800 mb-6">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>

            <h1 class="text-2xl font-bold text-center text-gray-900 mb-2">Verify your email</h1>
            <p class="text-center text-gray-600 mb-8">
                We've sent a verification link to<br>
                <span class="font-bold text-rose-900">{{ auth()->user()?->email }}</span>
            </p>

            <div class="flex justify-center mb-8">
                <div class="relative w-40 h-32">
                    <svg viewBox="0 0 100 70" class="w-full h-full">
                        <path d="M5 10 h90 v50 h-90 z" fill="#5c0f1e" />
                        <path d="M5 10 L50 45 L95 10" fill="none" stroke="white" stroke-width="3" />
                        <rect x="30" y="0" width="40" height="30" fill="white" stroke="#e5e7eb" stroke-width="1" />
                        <line x1="35" y1="7" x2="65" y2="7" stroke="#d1d5db" stroke-width="2" />
                        <line x1="35" y1="13" x2="65" y2="13" stroke="#d1d5db" stroke-width="2" />
                        <line x1="35" y1="19" x2="55" y2="19" stroke="#d1d5db" stroke-width="2" />
                    </svg>
                    <div class="absolute -bottom-1 -right-1 w-9 h-9 rounded-full bg-green-600 flex items-center justify-center shadow">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
            </div>

            <p class="text-center text-gray-600 mb-8">
                Please check your inbox and click the link<br>to verify your email address.
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}"
                @submit="secondsLeft = 60">
                @csrf
                <div class="border border-rose-200 rounded-lg p-4 text-center mb-3">
                    <p class="text-sm text-gray-500 mb-1">Didn't receive the email?</p>
                    <button type="submit" :disabled="secondsLeft > 0"
                        class="text-sm font-semibold text-rose-800 disabled:text-gray-400 disabled:cursor-not-allowed hover:underline">
                        <span x-show="secondsLeft === 0">Resend verification email</span>
                        <span x-show="secondsLeft > 0">Resend verification email (<span x-text="secondsLeft"></span>s)</span>
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('verification.change-email') }}">
                @csrf
                <button type="submit"
                    class="w-full border border-rose-200 rounded-lg p-4 text-center text-sm text-gray-600 hover:bg-gray-50 transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Change email address
                </button>
            </form>
        </div>
    </div>
</body>
</html>
