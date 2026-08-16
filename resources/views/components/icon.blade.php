@props(['name', 'class' => 'w-5 h-5'])

@php
    $path = public_path('images/icons/' . $name);
@endphp

@if (! file_exists($path))
    <span class="{{ $class }} inline-block rounded bg-gray-200"></span>
@else
    @php
        $svg = file_get_contents($path);
        $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 class="' . $class . ' block">', $svg, 1);
        $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="currentColor"', $svg);
        $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="currentColor"', $svg);
    @endphp
    {!! $svg !!}
@endif
