@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'labelledby' => $name.'-title',
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<dialog
    x-data="{
        show: @js($show),
        trigger: null,
        openDialog() {
            this.trigger = document.activeElement;
            this.show = true;
            if (! this.$refs.dialog.open) this.$refs.dialog.showModal();
            document.body.classList.add('overflow-y-hidden');
            this.$nextTick(() => {
                const target = this.$refs.dialog.querySelector('[autofocus], input:not([type=hidden]), button, a, select, textarea');
                target?.focus();
            });
        },
        closeDialog() {
            this.show = false;
            if (this.$refs.dialog.open) this.$refs.dialog.close();
            document.body.classList.remove('overflow-y-hidden');
            this.$nextTick(() => this.trigger?.focus());
        },
    }"
    x-ref="dialog"
    x-init="if (show) $nextTick(() => openDialog())"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? openDialog() : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? closeDialog() : null"
    x-on:close.stop="closeDialog()"
    x-on:cancel.prevent="closeDialog()"
    aria-modal="true"
    aria-labelledby="{{ $labelledby }}"
    class="tp-native-dialog fixed inset-0 m-0 h-full max-h-none w-full max-w-none overflow-y-auto border-0 bg-transparent px-4 py-6 sm:px-6"
>
    <div class="flex min-h-full items-center justify-center">
        <div class="w-full {{ $maxWidth }} overflow-hidden rounded-lg bg-[var(--tp-surface-raised)] shadow-xl">
            {{ $slot }}
        </div>
    </div>
</dialog>
