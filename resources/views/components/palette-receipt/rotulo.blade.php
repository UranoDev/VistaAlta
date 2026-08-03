{{-- Rótulo: la línea corta en mono que encabeza una sección, como el renglón de folio. --}}
<p {{ $attributes->class(['cifra text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-tinta']) }}>
    {{ $slot }}
</p>
