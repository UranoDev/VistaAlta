<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-terracota border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-terracota-dark focus:bg-terracota-dark active:bg-terracota transition ease-in-out duration-150 shadow-sm', 'style' => 'background-color: #A64B35; color: white;']) }}>
    {{ $slot }}
</button>
