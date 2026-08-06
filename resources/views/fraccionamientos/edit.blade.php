<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-contraste-azul leading-tight">
            {{ __('Editar Fraccionamiento') }}: {{ $fraccionamiento->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-terracota">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('fraccionamientos.update', $fraccionamiento) }}">
                        @csrf
                        @method('PATCH')

                        <!-- Nombre -->
                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $fraccionamiento->name)" required autofocus onkeyup="slugify(this.value)" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Slug -->
                        <div class="mt-4">
                            <x-input-label for="slug" :value="__('Slug (URL)')" />
                            <x-text-input id="slug" class="block mt-1 w-full bg-gray-50" type="text" name="slug" :value="old('slug', $fraccionamiento->slug)" required />
                            <p class="text-xs text-gray-500 mt-1">Precaución: cambiar el slug romperá los enlaces compartidos anteriormente.</p>
                            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                        </div>

                        <!-- Dirección -->
                        <div class="mt-4">
                            <x-input-label for="address" :value="__('Dirección')" />
                            <textarea id="address" name="address" class="block mt-1 w-full border-gray-300 focus:border-terracota focus:ring-terracota rounded-md shadow-sm" rows="3">{{ old('address', $fraccionamiento->address) }}</textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <!-- Contacto -->
                        <div class="mt-4">
                            <x-input-label for="contact" :value="__('Contacto')" />
                            <x-text-input id="contact" class="block mt-1 w-full" type="text" name="contact" :value="old('contact', $fraccionamiento->contact)" />
                            <x-input-error :messages="$errors->get('contact')" class="mt-2" />
                        </div>

                        <!-- Administrador del Fraccionamiento (Solo SuperAdmin) -->
                        @if(auth()->user()->isSuperAdmin())
                            <div class="mt-4">
                                <x-input-label for="admin_owner_id" :value="__('Administrador del Fraccionamiento')" />
                                @if($propietarios->isEmpty())
                                    <p class="mt-2 text-sm text-gray-500 italic">
                                        No hay propietarios registrados en este fraccionamiento. Agrega propietarios primero para poder asignar un administrador.
                                    </p>
                                @else
                                    <select id="admin_owner_id" name="admin_owner_id" class="block mt-1 w-full border-gray-300 focus:border-terracota focus:ring-terracota rounded-md shadow-sm">
                                        <option value="">— Sin administrador —</option>
                                        @foreach($propietarios as $propietario)
                                            <option value="{{ $propietario->id }}" {{ old('admin_owner_id', $fraccionamiento->admin_owner_id) == $propietario->id ? 'selected' : '' }}>
                                                {{ $propietario->name }}{{ $propietario->phone ? ' · ' . $propietario->phone : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                                <x-input-error :messages="$errors->get('admin_owner_id')" class="mt-2" />
                            </div>
                        @endif

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('fraccionamientos.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">
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
        function slugify(text) {
            // Solo auto-generar si el usuario no ha editado manualmente el slug? 
            // Para simplicidad en edición, a veces es mejor dejarlo quieto a menos que se pida.
            // Pero mantengamos la función por si acaso el usuario quiere que siga el nombre.
        }
    </script>
</x-app-layout>
