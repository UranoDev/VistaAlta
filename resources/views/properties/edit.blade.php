<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-contraste-azul leading-tight">
            {{ __('Editar Propiedad') }}: {{ $property->fraccionamiento->name }} — {{ $property->section ? $property->section . ' / ' : '' }}{{ $property->unit }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-terracota">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('properties.update', $property) }}">
                        @csrf
                        @method('PATCH')

                        <!-- Fraccionamiento -->
                        <div>
                            <x-input-label for="fraccionamiento_id" :value="__('Fraccionamiento')" />
                            <select id="fraccionamiento_id" name="fraccionamiento_id" required
                                class="block mt-1 w-full border-gray-300 focus:border-terracota focus:ring-terracota rounded-md shadow-sm">
                                <option value="">— Selecciona un fraccionamiento —</option>
                                @foreach($fraccionamientos as $fraccionamiento)
                                    <option value="{{ $fraccionamiento->id }}" {{ old('fraccionamiento_id', $property->fraccionamiento_id) == $fraccionamiento->id ? 'selected' : '' }}>
                                        {{ $fraccionamiento->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('fraccionamiento_id')" class="mt-2" />
                        </div>

                        <!-- Sección -->
                        <div class="mt-4">
                            <x-input-label for="section" :value="__('Sección')" />
                            <x-text-input id="section" class="block mt-1 w-full" type="text" name="section" :value="old('section', $property->section)" placeholder="Ej. A, B, Norte, Sur…" />
                            <x-input-error :messages="$errors->get('section')" class="mt-2" />
                        </div>

                        <!-- Unidad -->
                        <div class="mt-4">
                            <x-input-label for="unit" :value="__('Unidad')" />
                            <x-text-input id="unit" class="block mt-1 w-full" type="text" name="unit" :value="old('unit', $property->unit)" required placeholder="Ej. 12, Lote 5, Casa 3B…" />
                            <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                        </div>

                        <!-- Propietario -->
                        <div class="mt-4">
                            <x-input-label for="owner_id" :value="__('Propietario')" />
                            <select id="owner_id" name="owner_id"
                                class="block mt-1 w-full border-gray-300 focus:border-terracota focus:ring-terracota rounded-md shadow-sm">
                                <option value="">— Sin propietario —</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Los propietarios disponibles se filtran según el fraccionamiento seleccionado.</p>
                            <x-input-error :messages="$errors->get('owner_id')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('properties.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">
                                {{ __('Cancelar') }}
                            </a>
                            <x-primary-button class="bg-terracota hover:bg-terracota-dark">
                                {{ __('Actualizar') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allOwners = @json($owners);
        const selectedOwnerId = '{{ old('owner_id', $property->owner_id ?? '') }}';

        function filterOwners(fraccionamientoId) {
            const select = document.getElementById('owner_id');
            select.innerHTML = '<option value="">— Sin propietario —</option>';

            if (!fraccionamientoId) return;

            const filtered = allOwners.filter(o => o.fraccionamiento_id == fraccionamientoId);
            filtered.forEach(owner => {
                const opt = document.createElement('option');
                opt.value = owner.id;
                opt.textContent = owner.name;
                if (owner.id == selectedOwnerId) opt.selected = true;
                select.appendChild(opt);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const fracSelect = document.getElementById('fraccionamiento_id');
            filterOwners(fracSelect.value);
            fracSelect.addEventListener('change', function () {
                filterOwners(this.value);
            });
        });
    </script>
</x-app-layout>
