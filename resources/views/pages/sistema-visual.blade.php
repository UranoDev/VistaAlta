{{--
    Referencia del sistema visual "Recibo". Existe para construir las páginas
    encima con piezas ya resueltas, y para revisar de un vistazo que la paleta, la
    tipografía y los componentes siguen coherentes. No se sirve en producción.
--}}
<x-layout.app title="Sistema visual">
    <x-recibo.seccion rotulo="Referencia interna" titulo="Sistema visual «Recibo»" :lectura="false">
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
            <x-recibo.tarjeta :troquel="false">
                <p class="cifra text-3xl font-bold text-tinta">$1,350.00</p>
                <p class="mt-2 text-xs text-grafito/60">IBM Plex Mono · cifras y rótulos, con números tabulares</p>
            </x-recibo.tarjeta>
            <x-recibo.tarjeta :troquel="false">
                <p class="text-base">
                    La Mesa Directiva somete a consideración de la Asamblea formalizar el
                    fraccionamiento como figura legal.
                </p>
                <p class="mt-2 text-xs text-grafito/60">IBM Plex Sans · texto</p>
            </x-recibo.tarjeta>
        </div>

        <h3 class="mt-12 text-lg font-bold">Comprobante</h3>
        <div class="mt-4 max-w-sm">
            <x-recibo.tarjeta>
                <x-recibo.rotulo>Periodo</x-recibo.rotulo>
                <div class="mt-4">
                    <x-recibo.renglon concepto="Actividades publicadas">14</x-recibo.renglon>
                    <x-recibo.renglon concepto="Ingresos">$48,600.00</x-recibo.renglon>
                    <x-recibo.renglon concepto="Egresos">$41,275.00</x-recibo.renglon>
                    <x-recibo.renglon concepto="Saldo">$7,325.00</x-recibo.renglon>
                </div>
                <x-recibo.sello class="mt-5">Presentado</x-recibo.sello>
            </x-recibo.tarjeta>
        </div>

        <h3 class="mt-12 text-lg font-bold">Botones</h3>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-recibo.boton>Enviar comentario</x-recibo.boton>
            <x-recibo.boton variante="contorno">Ver el detalle</x-recibo.boton>
            <x-recibo.boton disabled>Deshabilitado</x-recibo.boton>
        </div>

        <h3 class="mt-12 text-lg font-bold">Notas</h3>
        <div class="mt-4 grid max-w-(--container-lectura) gap-3">
            <x-recibo.nota variante="exito">Tu teléfono quedó validado por 30 minutos.</x-recibo.nota>
            <x-recibo.nota variante="aviso">El código expiró. Pide uno nuevo para continuar.</x-recibo.nota>
            <x-recibo.nota>La Recepción de comentarios está cerrada.</x-recibo.nota>
        </div>

        <h3 class="mt-12 text-lg font-bold">Campos</h3>
        <div class="mt-4 grid max-w-(--container-lectura) gap-5 sm:grid-cols-2">
            <x-recibo.campo
                nombre="telefono"
                etiqueta="Teléfono celular"
                tipo="tel"
                ayuda="A este número llega el código de validación."
                placeholder="10 dígitos" />
            <x-recibo.campo
                nombre="codigo"
                etiqueta="Código"
                inputmode="numeric"
                error="El código no coincide." />
        </div>
    </x-recibo.seccion>
</x-layout.app>
