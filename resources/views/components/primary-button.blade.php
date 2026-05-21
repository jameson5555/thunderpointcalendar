<button {{ $attributes->merge(['type' => 'submit', 'class' => 'tp-button-primary focus:outline-none focus:ring-2 focus:ring-[rgba(95,72,56,0.26)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]']) }}>
    {{ $slot }}
</button>
