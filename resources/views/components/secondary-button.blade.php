<button {{ $attributes->merge(['type' => 'button', 'class' => 'tp-button-secondary disabled:opacity-25']) }}>
    {{ $slot }}
</button>
