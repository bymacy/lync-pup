@props([
    'name',
    'value' => '',
    'options' => [],
    'placeholder' => 'Select',
    'form' => 'info-sheet-form',
    'allowOther' => false,
    'required' => true,
    'compact' => false,
])

{{--
    Dropdown for the Information Sheet's fixed-choice fields (height, weight,
    blood type, sex, civil status, year graduated, highest level/units).

    The value is submitted through a hidden input, so nothing changes for the
    controllers or the validation rules — the sheet still receives a plain
    string under the same field name. Options highlight with the maroon -> navy
    gradient, matching the dropdowns elsewhere in the admin.

    `editing` and `dirty` come from the page's root Alpine component, the same
    way every other field on the sheet reads them.
--}}
<div
    x-data="{
        open: false,
        value: {{ \Illuminate\Support\Js::from((string) $value) }},
        options: {{ \Illuminate\Support\Js::from(array_values($options)) }},
        other: false,

        init() {
            this.other = this.value !== '' && ! this.options.includes(this.value);
        },

        get label() {
            return this.value !== '' ? this.value : '{{ $placeholder }}';
        },

        choose(option) {
            if (option === '__other__') {
                this.other = true;
                this.value = '';
                this.open = false;
                this.$nextTick(() => this.$refs.otherInput?.focus());
            } else {
                this.other = false;
                this.value = option;
                this.open = false;
            }

            dirty = true;
        },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative w-full"
>
    {{-- No form attribute inside a sub-form (core team rows): the input is
         already a descendant of the form it belongs to. --}}
    <input type="hidden" name="{{ $name }}" :value="value"
        @if ($form) form="{{ $form }}" @endif
        @if ($required) required @endif>

    <button type="button"
        @click="editing && (open = ! open)"
        :aria-expanded="open"
        aria-haspopup="listbox"
        :disabled="! editing"
        class="flex w-full items-center justify-between gap-2 rounded border text-left transition
               {{ $compact ? 'px-2 py-1.5 text-sm' : 'px-3 py-1.5 text-sm' }}
               disabled:cursor-default disabled:bg-gray-50 disabled:text-gray-500"
        :class="open ? 'border-rose-400 ring-2 ring-rose-100' : 'border-gray-300 hover:border-gray-400'">
        <span class="min-w-0 truncate" :class="value ? 'text-gray-900' : 'text-gray-300'" x-text="label"></span>
        <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-180'"
            fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-cloak role="listbox"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="absolute left-0 z-30 mt-1 max-h-56 w-full min-w-[8rem] overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg">

        <template x-for="option in options" :key="option">
            <button type="button" role="option" :aria-selected="value === option"
                @click="choose(option)"
                class="w-full rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white"
                :class="value === option ? 'rounded-md bg-rose-50 font-medium text-rose-900' : 'text-gray-700'"
                x-text="option"></button>
        </template>

        @if ($allowOther)
            <button type="button" @click="choose('__other__')"
                class="w-full rounded-md px-3 py-2 text-left text-sm text-[#11386A] transition-colors hover:bg-gradient-to-r hover:from-[#6D0D23] hover:to-[#11386A] hover:text-white">
                Other - type it
            </button>
        @endif
    </div>

    {{-- Free-text escape for a value outside the list. --}}
    <template x-if="other">
        <input type="text" x-model="value" x-ref="otherInput"
            :readonly="! editing" @input="dirty = true"
            placeholder="{{ $placeholder }}"
            class="mt-1 w-full rounded border border-gray-300 px-3 py-1.5 text-sm uppercase placeholder:normal-case placeholder:text-gray-300">
    </template>
</div>
