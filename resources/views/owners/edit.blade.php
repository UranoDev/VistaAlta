<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Propietario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('owners.update', $owner) }}">
                        @csrf
                        @method('PATCH')

                        <!-- Fraccionamiento -->
                        <div>
                            <x-input-label for="fraccionamiento_id" :value="__('Fraccionamiento')" />
                            <select id="fraccionamiento_id" name="fraccionamiento_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                <option value="">Seleccione un fraccionamiento</option>
                                @foreach($fraccionamientos as $fraccionamiento)
                                    <option value="{{ $fraccionamiento->id }}" {{ old('fraccionamiento_id', $owner->fraccionamiento_id) == $fraccionamiento->id ? 'selected' : '' }}>
                                        {{ $fraccionamiento->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('fraccionamiento_id')" class="mt-2" />
                        </div>

                        <!-- Nombre -->
                        <div class="mt-4">
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $owner->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div class="mt-4">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $owner->email)" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Celular -->
                        <div class="mt-4">
                            <x-input-label for="phone" :value="__('Celular')" />
                            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $owner->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <!-- Miembro del Comité -->
                        <div class="block mt-4">
                            <label for="is_committee_member" class="inline-flex items-center">
                                <input id="is_committee_member" type="hidden" name="is_committee_member" value="0">
                                <input id="is_committee_member" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_committee_member" value="1" {{ old('is_committee_member', $owner->is_committee_member) ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-gray-600">{{ __('Es miembro del comité') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('is_committee_member')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('owners.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                {{ __('Cancelar') }}
                            </a>

                            <x-primary-button class="ms-4">
                                {{ __('Actualizar') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
