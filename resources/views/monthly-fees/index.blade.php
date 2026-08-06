<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-contraste-azul leading-tight">
                    Cuotas — {{ $fraccionamiento->name }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    <a href="{{ route('fraccionamientos.index') }}" class="hover:underline">Fraccionamientos</a>
                    / {{ $fraccionamiento->name }}
                </p>
            </div>
            @can('update', $fraccionamiento)
                <a href="{{ route('fraccionamientos.fees.create', $fraccionamiento) }}"
                   class="inline-flex items-center px-4 py-2 bg-terracota border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-terracota-dark transition ease-in-out duration-150 shadow-md"
                   style="background-color: #A64B35; color: white;">
                    Nueva Cuota
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-cantera-clara border border-terracota text-terracota-dark px-4 py-3 rounded relative" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Cuota vigente y programada --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Vigente --}}
                <div class="bg-white shadow-sm sm:rounded-lg border-t-4 border-contraste-azul p-6">
                    <h3 class="text-sm font-semibold text-contraste-azul uppercase tracking-wider mb-3">Cuota Vigente</h3>
                    @if($currentFee)
                        <p class="text-3xl font-bold text-contraste-azul">${{ number_format($currentFee->amount, 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Desde {{ $currentFee->start_date->format('d/m/Y') }}</p>
                        @if($currentFee->surcharge_type)
                            <div class="mt-3 text-sm text-gray-600">
                                <span class="font-medium">Recargo por atraso:</span>
                                @if($currentFee->surcharge_type === 'percentage')
                                    {{ number_format($currentFee->surcharge_value, 2) }}%
                                    (= ${{ number_format($currentFee->amountWithSurcharge(), 2) }} total)
                                @else
                                    ${{ number_format($currentFee->surcharge_value, 2) }} fijo
                                    (= ${{ number_format($currentFee->amountWithSurcharge(), 2) }} total)
                                @endif
                            </div>
                        @else
                            <p class="mt-3 text-xs text-gray-400 italic">Sin recargo configurado</p>
                        @endif
                    @else
                        <p class="text-gray-500 italic">No hay cuota vigente.</p>
                    @endif
                </div>

                {{-- Programada --}}
                <div class="bg-white shadow-sm sm:rounded-lg border-t-4 border-cantera-rosa p-6">
                    <h3 class="text-sm font-semibold text-contraste-azul uppercase tracking-wider mb-3">Cuota Programada</h3>
                    @if($scheduledFee)
                        <p class="text-3xl font-bold text-gray-700">${{ number_format($scheduledFee->amount, 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Entrará en vigor el {{ $scheduledFee->start_date->format('d/m/Y') }}</p>
                        @if($scheduledFee->surcharge_type)
                            <div class="mt-3 text-sm text-gray-600">
                                <span class="font-medium">Recargo:</span>
                                @if($scheduledFee->surcharge_type === 'percentage')
                                    {{ number_format($scheduledFee->surcharge_value, 2) }}%
                                @else
                                    ${{ number_format($scheduledFee->surcharge_value, 2) }} fijo
                                @endif
                            </div>
                        @endif
                        @can('update', $fraccionamiento)
                            <form action="{{ route('fraccionamientos.fees.destroy', [$fraccionamiento, $scheduledFee]) }}"
                                  method="POST" class="inline mt-4 block">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('¿Cancelar la cuota programada?')"
                                        class="text-xs text-red-600 hover:text-red-800 underline">
                                    Cancelar cuota programada
                                </button>
                            </form>
                        @endcan
                    @else
                        <p class="text-gray-500 italic">No hay cuota programada.</p>
                        @can('update', $fraccionamiento)
                            <p class="text-xs text-gray-400 mt-2">Puedes registrar una con fecha futura.</p>
                        @endcan
                    @endif
                </div>
            </div>

            {{-- Historial --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-contraste-azul">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-contraste-azul uppercase tracking-wider mb-4">Historial de Cuotas</h3>
                    @if($history->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-cantera-rosa">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 bg-cantera-clara text-left text-xs font-bold text-contraste-azul uppercase tracking-wider">Fecha inicio</th>
                                        <th class="px-4 py-3 bg-cantera-clara text-left text-xs font-bold text-contraste-azul uppercase tracking-wider">Cuota base</th>
                                        <th class="px-4 py-3 bg-cantera-clara text-left text-xs font-bold text-contraste-azul uppercase tracking-wider">Recargo</th>
                                        <th class="px-4 py-3 bg-cantera-clara text-left text-xs font-bold text-contraste-azul uppercase tracking-wider">Total con recargo</th>
                                        <th class="px-4 py-3 bg-cantera-clara text-left text-xs font-bold text-contraste-azul uppercase tracking-wider">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($history as $fee)
                                        <tr class="hover:bg-cantera-clara/20 transition-colors">
                                            <td class="px-4 py-3 text-sm font-medium">{{ $fee->start_date->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3 text-sm">${{ number_format($fee->amount, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                @if($fee->surcharge_type === 'percentage')
                                                    {{ number_format($fee->surcharge_value, 2) }}%
                                                @elseif($fee->surcharge_type === 'fixed')
                                                    ${{ number_format($fee->surcharge_value, 2) }} fijo
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium">${{ number_format($fee->amountWithSurcharge(), 2) }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                @if($fee->isFuture())
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Programada</span>
                                                @elseif($currentFee && $fee->id === $currentFee->id)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Vigente</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">Histórica</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $history->links() }}</div>
                    @else
                        <p class="text-gray-500 italic text-center py-6">No hay cuotas registradas para este fraccionamiento.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
