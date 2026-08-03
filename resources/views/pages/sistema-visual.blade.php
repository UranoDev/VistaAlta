{{--
    Referencia del sistema visual "Palette Receipt". Existe para construir las
    páginas encima con piezas ya resueltas, y para revisar de un vistazo que la
    paleta, la tipografía y los componentes siguen coherentes. No se sirve en
    producción.
--}}
<x-layout.app title="Sistema visual">
    <x-palette-receipt.seccion rotulo="Referencia interna" titulo="Sistema visual «Palette Receipt»" :lectura="false">
        <p class="max-w-(--container-lectura) text-base text-grafito/80">
            Tinta de folio, números tabulares, papel térmico. La marca es la prueba de que
            se rindió cuentas bien.
        </p>

        <h3 class="mt-12 text-lg font-bold">Paleta</h3>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ([
                ['Tinta Verde', '#1E4D3B', 'bg-tinta', 'acento principal'],
                ['Rojo Sello', '#A22E2E', 'bg-sello', 'reservado a alertas'],
                ['Papel Térmico', '#EEEDE4', 'bg-papel', 'fondo base'],
                ['Grafito', '#2A2A28', 'bg-grafito', 'texto'],
                ['Menta Pálida', '#BFE0CE', 'bg-menta', 'resalte de éxito'],
            ] as [$nombre, $hex, $clase, $uso])
                <div>
                    <div class="{{ $clase }} h-16 w-full border border-linea"></div>
                    <p class="mt-2 text-sm font-semibold">{{ $nombre }}</p>
                    <p class="cifra text-xs text-grafito/70">{{ $hex }}</p>
                    <p class="text-xs text-grafito/60">{{ $uso }}</p>
                </div>
            @endforeach
        </div>

        <h3 class="mt-12 text-lg font-bold">Tipografía</h3>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <x-palette-receipt.tarjeta :troquel="false">
                <p class="cifra text-3xl font-bold text-tinta">$1,350.00</p>
                <p class="mt-2 text-xs text-grafito/60">IBM Plex Mono · cifras y rótulos, con números tabulares</p>
            </x-palette-receipt.tarjeta>
            <x-palette-receipt.tarjeta :troquel="false">
                <p class="text-base">
                    La Mesa Directiva somete a consideración de la Asamblea formalizar el
                    fraccionamiento como figura legal.
                </p>
                <p class="mt-2 text-xs text-grafito/60">IBM Plex Sans · texto</p>
            </x-palette-receipt.tarjeta>
        </div>

        <h3 class="mt-12 text-lg font-bold">Comprobante</h3>
        <div class="mt-4 max-w-sm">
            <x-palette-receipt.tarjeta>
                <x-palette-receipt.rotulo>Periodo</x-palette-receipt.rotulo>
                <div class="mt-4">
                    <x-palette-receipt.renglon concepto="Actividades publicadas">14</x-palette-receipt.renglon>
                    <x-palette-receipt.renglon concepto="Ingresos">$48,600.00</x-palette-receipt.renglon>
                    <x-palette-receipt.renglon concepto="Egresos">$41,275.00</x-palette-receipt.renglon>
                    <x-palette-receipt.renglon concepto="Saldo">$7,325.00</x-palette-receipt.renglon>
                </div>
                <x-palette-receipt.sello class="mt-5">Presentado</x-palette-receipt.sello>
            </x-palette-receipt.tarjeta>
        </div>

        <h3 class="mt-12 text-lg font-bold">Botones</h3>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-palette-receipt.boton>Enviar comentario</x-palette-receipt.boton>
            <x-palette-receipt.boton variante="contorno">Ver el detalle</x-palette-receipt.boton>
            <x-palette-receipt.boton disabled>Deshabilitado</x-palette-receipt.boton>
        </div>

        <h3 class="mt-12 text-lg font-bold">Notas</h3>
        <div class="mt-4 grid max-w-(--container-lectura) gap-3">
            <x-palette-receipt.nota variante="exito">Tu teléfono quedó validado por 30 minutos.</x-palette-receipt.nota>
            <x-palette-receipt.nota variante="aviso">El código expiró. Pide uno nuevo para continuar.</x-palette-receipt.nota>
            <x-palette-receipt.nota>La Recepción de comentarios está cerrada.</x-palette-receipt.nota>
        </div>

        <h3 class="mt-12 text-lg font-bold">Campos</h3>
        <div class="mt-4 grid max-w-(--container-lectura) gap-5 sm:grid-cols-2">
            <x-palette-receipt.campo
                nombre="telefono"
                etiqueta="Teléfono celular"
                tipo="tel"
                ayuda="A este número llega el código de validación."
                placeholder="10 dígitos" />
            <x-palette-receipt.campo
                nombre="codigo"
                etiqueta="Código"
                inputmode="numeric"
                error="El código no coincide." />
        </div>
    </x-palette-receipt.seccion>
</x-layout.app>
