<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-contraste-azul leading-tight">
                {{ __('Fraccionamientos') }}
            </h2>
            <a href="{{ route('fraccionamientos.create') }}" class="inline-flex items-center px-4 py-2 bg-terracota border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-terracota-dark active:bg-terracota transition ease-in-out duration-150 shadow-md" style="background-color: #A64B35; color: white;">
                {{ __('Nuevo Fraccionamiento') }}
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-terracota">
                <div class="p-6 text-gray-900">
                    @if($fraccionamientos->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-cantera-rosa">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold">Nombre</th>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold">Slug</th>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold">Contacto</th>
                                        <th class="px-6 py-3 bg-cantera-clara text-left text-xs font-medium text-contraste-azul uppercase tracking-wider font-bold text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($fraccionamientos as $fraccionamiento)
                                        <tr class="hover:bg-cantera-clara/20 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-contraste-azul">{{ $fraccionamiento->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $fraccionamiento->slug }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $fraccionamiento->contact }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div class="flex justify-center space-x-3">
                                                    <a href="{{ route('fraccionamientos.edit', $fraccionamiento) }}" class="text-terracota hover:text-terracota-dark transition-colors" title="Editar">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    </a>
                                                    <form action="{{ route('fraccionamientos.destroy', $fraccionamiento) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 transition-colors" onclick="return confirm('¿Estás seguro?')" title="Eliminar">
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
                            {{ $fraccionamientos->links() }}
                        </div>
                    @else
                        <div class="py-12 flex flex-col items-center justify-center text-center">
                            <div class="bg-cantera-clara/30 p-6 rounded-full mb-4">
                                <svg class="w-16 h-16 text-cantera-rosa" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-10V4a1 1 0 011-1h2a1 1 0 011 1v3M12 7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-contraste-azul mb-2">No hay fraccionamientos registrados</h3>
                            <p class="text-gray-500 max-w-sm mb-8">Comienza por agregar tu primer fraccionamiento para gestionar sus propiedades y propietarios.</p>
                            <a href="{{ route('fraccionamientos.create') }}" class="inline-flex items-center px-6 py-3 bg-terracota text-white font-bold rounded-lg hover:bg-terracota-dark transition-all shadow-lg hover:shadow-xl active:scale-95" style="background-color: #A64B35; color: white;">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Crear el primer fraccionamiento
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
