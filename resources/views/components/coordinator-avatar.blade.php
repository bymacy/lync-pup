@props(['src' => null, 'size' => 'h-10 w-10'])

<span {{ $attributes->merge(['class' => "$size flex-shrink-0 rounded-full bg-gray-200 overflow-hidden flex items-center justify-center"]) }}>
    @if ($src)
    <img src="{{ \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '/']) ? $src : asset('storage/' . $src) }}"
        alt="" class="h-full w-full object-cover">
    @else
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
        <circle cx="8.5" cy="8.5" r="1.5"></circle>
        <polyline points="21 15 16 10 5 21"></polyline>
    </svg>
    @endif
</span>
