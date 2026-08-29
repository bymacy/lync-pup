@props([
    'name',
    'label',
    'number' => null,
    'value' => '',
    'options' => [],
    'allowOther' => false,
])

{{--
    A dropdown laid out like the sheet's typed rows: numbered label on the left,
    control on the right, with room underneath for a validation message. Kept as
    its own component so $select() in the two sheet views can render it without
    duplicating the row markup.
--}}
<div class="flex flex-col gap-1 py-1.5 text-sm sm:flex-row sm:items-start sm:gap-2">
    <label class="w-full flex-shrink-0 text-gray-800 sm:w-48 sm:pt-1.5">
        @if ($number)<span class="font-semibold">{{ $number }}.</span> @endif{{ $label }}:
        <span class="text-rose-600 text-base font-bold leading-none align-middle">*</span>
    </label>

    <div class="flex-1 min-w-0">
        <x-sheet-select :name="$name" :value="$value" :options="$options" :allow-other="$allowOther" />
    </div>
</div>
