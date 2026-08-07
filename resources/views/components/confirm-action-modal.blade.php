@props([
    'show',
    'close',
    'title',
    'message',
    'action',
    'method' => 'DELETE',
    'confirmLabel' => 'Confirm',
    'icon' => 'trash',
])

<div x-show="{{ $show }}" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" style="display:none;">
    <div @click.outside="{{ $close }}" class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="px-8 py-8 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-[#6D0D23] to-[#11386A]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    @if ($icon === 'trash')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7L5 7M10 11v6M14 11v6M6 7l1 13a2 2 0 002 2h6a2 2 0 002-2l1-13M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-3-6.65" />
                    @endif
                </svg>
            </div>

            <h2 class="mt-5 text-xl font-bold text-gray-900">{{ $title }}</h2>

            @if ($message)
                <p class="mt-3 text-sm leading-6 text-gray-600">{{ $message }}</p>
            @endif

            <div class="mt-8 flex justify-center gap-3">
                <button type="button" @click="{{ $close }}"
                    class="flex-1 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>

                <form method="POST" action="{{ $action }}" class="flex-1">
                    @csrf
                    @if (strtoupper($method) !== 'POST')
                        @method($method)
                    @endif
                    <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] px-5 py-2.5 text-sm font-medium text-white hover:opacity-90 transition">
                        {{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
