{{-- Sello de goma, ligeramente torcido: marca un hecho consumado. --}}
<span {{ $attributes->class([
        'inline-block border-2 border-tinta px-3 py-1 -rotate-3',
        'cifra text-xs font-bold uppercase tracking-[0.08em] text-tinta',
    ]) }}>
    {{ $slot }}
</span>
