<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-contraste-azul leading-tight">
                Nueva Cuota — {{ $fraccionamiento->name }}
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                <a href="{{ route('fraccionamientos.index') }}" class="hover:underline">Fraccionamientos</a>
                /
                <a href="{{ route('fraccionamientos.fees.index', $fraccionamiento) }}" class="hover:underline">Cuotas</a>
                / Nueva
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-terracota">
                <div class="p-6">
                    <form method="POST" action="{{ route('fraccionamientos.fees.store', $fraccionamiento) }}">
                        @csrf

                        {{-- Importe base --}}
                        <div>
                            <x-input-label for="amount" :value="__('Cuota mensual ($)')" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01"
                                class="block mt-1 w-full" :value="old('amount')" required placeholder="800.00" />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        {{-- Fecha de inicio --}}
                        <div class="mt-4">
                            <x-input-label for="start_date" :value="__('Fecha de inicio de vigencia')" />
                            <x-text-input id="start_date" name="start_date" type="date"
                                class="block mt-1 w-full" :value="old('start_date', today()->toDateString())" required />
                            <p class="text-xs text-gray-500 mt-1">Puedes indicar una fecha futura para programar el cambio de cuota.</p>
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>

                        {{-- Recargo --}}
                        <div class="mt-6">
                            <x-input-label :value="__('Recargo por pago vencido (opcional)')" />
                            <p class="text-xs text-gray-500 mt-1 mb-3">
                                El recargo se aplica una sola vez al mes adeudado, sin importar cuántos meses tarde el propietario en pagar.
                            </p>

                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="surcharge_type" value="" id="surcharge_none"
                                        class="rounded-full border-gray-300 text-terracota shadow-sm focus:ring-terracota"
                                        {{ old('surcharge_type', '') === '' ? 'checked' : '' }}
                                        onchange="toggleSurcharge(this)">
                                    <span class="ml-2 text-sm text-gray-700">Sin recargo</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="surcharge_type" value="percentage" id="surcharge_pct"
                                        class="rounded-full border-gray-300 text-terracota shadow-sm focus:ring-terracota"
                                        {{ old('surcharge_type') === 'percentage' ? 'checked' : '' }}
                                        onchange="toggleSurcharge(this)">
                                    <span class="ml-2 text-sm text-gray-700">Porcentaje (%)</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="surcharge_type" value="fixed" id="surcharge_fixed"
                                        class="rounded-full border-gray-300 text-terracota shadow-sm focus:ring-terracota"
                                        {{ old('surcharge_type') === 'fixed' ? 'checked' : '' }}
                                        onchange="toggleSurcharge(this)">
                                    <span class="ml-2 text-sm text-gray-700">Importe fijo ($)</span>
                                </label>
                            </div>

                            <div id="surcharge_value_wrapper" class="mt-3" style="display: none;">
                                <x-input-label for="surcharge_value" :value="__('Valor del recargo')" />
                                <div class="relative mt-1">
                                    <x-text-input id="surcharge_value" name="surcharge_value" type="number"
                                        step="0.01" min="0.01" class="block w-full"
                                        :value="old('surcharge_value')" placeholder="0.00" />
                                    <span id="surcharge_unit" class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500"></span>
                                </div>
                                <x-input-error :messages="$errors->get('surcharge_value')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('fraccionamientos.fees.index', $fraccionamiento) }}"
                               class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">
                                Cancelar
                            </a>
                            <x-primary-button class="bg-terracota hover:bg-terracota-dark">
                                Guardar Cuota
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSurcharge(radio) {
            const wrapper = document.getElementById('surcharge_value_wrapper');
            const unit = document.getElementById('surcharge_unit');

            if (radio.value === 'percentage') {
                wrapper.style.display = 'block';
                unit.textContent = '%';
            } else if (radio.value === 'fixed') {
                wrapper.style.display = 'block';
                unit.textContent = '$';
            } else {
                wrapper.style.display = 'none';
            }
        }

        // Inicializar en carga si hay un valor previo (validación fallida)
        document.addEventListener('DOMContentLoaded', function () {
            const checked = document.querySelector('input[name="surcharge_type"]:checked');
            if (checked && checked.value !== '') {
                toggleSurcharge(checked);
            }
        });
    </script>
</x-app-layout>
