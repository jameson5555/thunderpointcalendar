@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-[rgba(253,251,247,0.96)]', 'id' => 'dropdown-menu'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div
    class="relative z-[70]"
    x-data="{
        open: false,
        closeAndRestoreFocus() {
            if (! this.open) return;
            this.open = false;
            this.$nextTick(() => this.$refs.trigger.querySelector('button, a')?.focus());
        },
    }"
    @click.outside="open = false"
    @close.stop="closeAndRestoreFocus()"
    @keydown.escape.window="closeAndRestoreFocus()"
>
    <div x-ref="trigger" @click="open = ! open">
        {{ $trigger }}
    </div>

    <div id="{{ $id }}" x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-[80] mt-2 {{ $width }} rounded-[1rem] shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded-[1rem] border border-[var(--tp-border)] shadow-[var(--tp-shadow)] {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
