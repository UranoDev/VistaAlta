<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-contraste-azul leading-tight">
            {{ __('Crear Fraccionamiento') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-terracota">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('fraccionamientos.store') }}">
                        @csrf

                        <!-- Nombre -->
                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus onkeyup="slugify(this.value)" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Slug -->
                        <div class="mt-4">
                            <x-input-label for="slug" :value="__('Slug (URL)')" />
                            <x-text-input id="slug" class="block mt-1 w-full bg-gray-50" type="text" name="slug" :value="old('slug')" required />
                            <p class="text-xs text-gray-500 mt-1">Se usará para la página pública de atrasos.</p>
                            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                        </div>

                        <!-- Dirección -->
                        <div class="mt-4">
                            <x-input-label for="address" :value="__('Dirección')" />
                            <textarea id="address" name="address" class="block mt-1 w-full border-gray-300 focus:border-terracota focus:ring-terracota rounded-md shadow-sm" rows="3">{{ old('address') }}</textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <!-- Contacto -->
                        <div class="mt-4">
                            <x-input-label for="contact" :value="__('Contacto')" />
                            <x-text-input id="contact" class="block mt-1 w-full" type="text" name="contact" :value="old('contact')" placeholder="Nombre o datos de contacto" />
                            <x-input-error :messages="$errors->get('contact')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('fraccionamientos.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">
                                {{ __('Cancelar') }}
                            </a>
                            <x-primary-button class="bg-terracota hover:bg-terracota-dark">
                                {{ __('Guardar') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function slugify(text) {
            const slug = text.toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('slug').value = slug;
        }
    </script>
</x-app-layout>
