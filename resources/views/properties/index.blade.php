<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-contraste-azul leading-tight">
                {{ __('Propiedades') }}
            </h2>
            <a href="{{ route('properties.create') }}" class="inline-flex items-center px-4 py-2 bg-terracota border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-terracota-dark active:bg-terracota transition ease-in-out duration-150 shadow-md" style="background-color: #A64B35; color: white;">
                {{ __('Nueva Propiedad') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-cantera-clara border border-terracota text-terracota-dark px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-contraste-azul">
                <div class="p-6 text-gray-900">
                    @if($properties->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-cantera-rosa">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold">Fraccionamiento</th>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold">Sección</th>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold">Unidad</th>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold">Propietario</th>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($properties as $property)
                                        <tr class="hover:bg-cantera-clara/20 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-contraste-azul">{{ $property->fraccionamiento->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $property->section ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $property->unit }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $property->owner?->name ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div class="flex justify-center space-x-3">
                                                    <a href="{{ route('properties.edit', $property) }}" class="text-contraste-azul hover:text-terracota transition-colors" title="Editar">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    </a>
                                                    <form action="{{ route('properties.destroy', $property) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 transition-colors" onclick="return confirm('¿Estás seguro de eliminar esta propiedad?')" title="Eliminar">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $properties->links() }}
                        </div>
                    @else
                        <div class="py-12 flex flex-col items-center justify-center text-center">
                            <div class="bg-cantera-clara/30 p-6 rounded-full mb-4">
                                <svg class="w-16 h-16 text-contraste-azul" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-contraste-azul mb-2">No hay propiedades registradas</h3>
                            <p class="text-gray-500 max-w-sm mb-8">Registra las propiedades del fraccionamiento para asociarlas a sus propietarios.</p>
                            <a href="{{ route('properties.create') }}" class="inline-flex items-center px-6 py-3 bg-contraste-azul text-white font-bold rounded-lg hover:bg-contraste-azul/90 transition-all shadow-lg hover:shadow-xl active:scale-95" style="background-color: #1B365D; color: white;">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Crear la primera propiedad
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
