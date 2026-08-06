<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Tarjeta Fraccionamientos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-terracota">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-contraste-azul">Fraccionamientos</h3>
                            <span class="bg-cantera-clara text-terracota px-3 py-1 rounded-full text-sm font-bold">
                                {{ $fraccionamientosCount }} registrados
                            </span>
                        </div>
                        <p class="text-gray-600 mb-6">Administra los fraccionamientos, configura sus datos básicos y slugs para acceso público.</p>
                        <div class="flex space-x-3">
                            <a href="{{ route('fraccionamientos.index') }}" class="inline-flex items-center px-4 py-2 bg-contraste-azul border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-contraste-azul/90 active:bg-contraste-azul transition ease-in-out duration-150" style="background-color: #1B365D; color: white;">
                                Ver Listado
                            </a>
                            <a href="{{ route('fraccionamientos.create') }}" class="inline-flex items-center px-4 py-2 bg-terracota border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-terracota-dark active:bg-terracota transition ease-in-out duration-150" style="background-color: #A64B35; color: white;">
                                Nuevo
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Propietarios -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-contraste-azul">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-contraste-azul">Propietarios</h3>
                            <span class="bg-cantera-clara text-contraste-azul px-3 py-1 rounded-full text-sm font-bold">
                                {{ $ownersCount }} registrados
                            </span>
                        </div>
                        <p class="text-gray-600 mb-6">Gestiona la información de los propietarios, sus datos de contacto y pertenencia al comité.</p>
                        <div class="flex space-x-3">
                            <a href="{{ route('owners.index') }}" class="inline-flex items-center px-4 py-2 bg-contraste-azul border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-contraste-azul/90 active:bg-contraste-azul transition ease-in-out duration-150" style="background-color: #1B365D; color: white;">
                                Ver Listado
                            </a>
                            <a href="{{ route('owners.create') }}" class="inline-flex items-center px-4 py-2 bg-terracota border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-terracota-dark active:bg-terracota transition ease-in-out duration-150" style="background-color: #A64B35; color: white;">
                                Nuevo
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Tarjeta Propiedades -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-terracota">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-contraste-azul">Propiedades</h3>
                            <span class="bg-cantera-clara text-terracota px-3 py-1 rounded-full text-sm font-bold">
                                {{ $propertiesCount }} registradas
                            </span>
                        </div>
                        <p class="text-gray-600 mb-6">Administra las propiedades de cada fraccionamiento y sus propietarios asignados.</p>
                        <div class="flex space-x-3">
                            <a href="{{ route('properties.index') }}" class="inline-flex items-center px-4 py-2 bg-contraste-azul border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-contraste-azul/90 active:bg-contraste-azul transition ease-in-out duration-150" style="background-color: #1B365D; color: white;">
                                Ver Listado
                            </a>
                            <a href="{{ route('properties.create') }}" class="inline-flex items-center px-4 py-2 bg-terracota border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-terracota-dark active:bg-terracota transition ease-in-out duration-150" style="background-color: #A64B35; color: white;">
                                Nueva
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
