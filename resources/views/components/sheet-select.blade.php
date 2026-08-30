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

        // The panel is position:fixed, not absolute. Core Team rows sit inside
        // overflow-hidden + overflow-x-auto wrappers, which clip an absolutely
        // positioned panel at the table edge - that is what hid the lower half
        // of the list. Fixed escapes both, so the coordinates are measured off
        // the trigger each time it opens.
        coords: { top: 0, left: 0, width: 0, maxHeight: 0 },
        rowHeight: {{ $compact ? 30 : 34 }},

        place() {
            const r = this.$refs.trigger.getBoundingClientRect();
            const wanted = Math.min(this.options.length * this.rowHeight + 8, 224);
            const below = window.innerHeight - r.bottom - 8;
            const above = r.top - 8;

            // Flip upward when the row is near the bottom of the viewport.
            const dropUp = below < wanted && above > below;
            const maxHeight = Math.min(wanted, dropUp ? above : below);

            this.coords = {
                top: dropUp ? r.top - maxHeight - 4 : r.bottom + 4,
                left: r.left,
                width: Math.max(r.width, 132),
                maxHeight,
            };
        },

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
    @scroll.window.capture="open = false"
    @resize.window="open = false"
    {{-- The value rides on a hidden input, which has nowhere to show a message.
         The validator walks up to this wrapper instead (see flag() on the
         Information Sheet), so each row anchors to its own control. --}}
    data-field-anchor="{{ $name }}"
    class="relative w-full"
>
    {{-- No form attribute inside a sub-form (core team rows): the input is
         already a descendant of the form it belongs to. --}}
    <input type="hidden" name="{{ $name }}" :value="value"
        @if ($form) form="{{ $form }}" @endif
        @if ($required) required @endif>

    <button type="button" x-ref="trigger"
        @click="if (editing) { open = ! open; if (open) place() }"
        :aria-expanded="open"
        aria-haspopup="listbox"
        :disabled="! editing"
        class="flex w-full items-center justify-between gap-2 rounded border text-left transition
               {{ $compact ? 'px-2 py-1 text-xs' : 'px-3 py-1.5 text-sm' }}
               disabled:cursor-default disabled:bg-gray-50 disabled:text-gray-500"
        :class="open ? 'border-rose-400 ring-2 ring-rose-100' : 'border-gray-300 hover:border-gray-400'">
        <span class="min-w-0 truncate" :class="value ? 'text-gray-900' : 'text-gray-300'" x-text="label"></span>
        <svg class="{{ $compact ? 'h-3.5 w-3.5' : 'h-4 w-4' }} shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-180'"
            fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-cloak role="listbox"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        :style="`position:fixed; top:${coords.top}px; left:${coords.left}px; width:${coords.width}px; max-height:${coords.maxHeight}px`"
        class="z-50 overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-xl">

        <template x-for="option in options" :key="option">
            <button type="button" role="option" :aria-selected="value === option"
                @click="choose(option)"
                class="w-full rounded-md text-left transition-colors hover:bg-gradient-to-r hover:from-[#6C0E24] hover:to-[#11386A] hover:text-white
                       {{ $compact ? 'px-2.5 py-1.5 text-xs' : 'px-3 py-1.5 text-sm' }}"
                :class="value === option ? 'rounded-md bg-rose-50 font-medium text-rose-900' : 'text-gray-700'"
                x-text="option"></button>
        </template>

        @if ($allowOther)
            <button type="button" @click="choose('__other__')"
                class="w-full rounded-md px-3 py-1.5 text-left text-sm text-[#11386A] transition-colors hover:bg-gradient-to-r hover:from-[#6C0E24] hover:to-[#11386A] hover:text-white">
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
