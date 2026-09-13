<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MetodoDePago;
use App\Enums\Rubro;
use App\Models\Categoria;
use App\Models\ContactoDelPadron;
use App\Models\Cuota;
use App\Models\CuotaVigencia;
use App\Models\Egreso;
use App\Models\OtroIngreso;
use App\Models\Recibo;
use App\Models\Seccion;
use App\Models\SolicitudIdentidad;
use App\Models\Unidad;
use App\Models\User;
use App\Support\Cobranza\GeneracionDeCuotas;
use App\Support\Cobranza\PagoDeCuota;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Padrón, cobranza y gastos de mentiras, para poder **mirar** las pantallas.
 *
 * No es material de arranque: eso es `ContenidoInicialSeeder`, que sí viaja a
 * producción. Este existe porque la mitad de las pantallas nuevas —el tablero de
 * cobranza, el estado de cuenta, el reporte derivado— no dicen nada con la base
 * vacía, y capturar veinte unidades a mano antes de cada revisión es la clase de
 * fricción que termina en «mejor lo reviso con tres y le creo al resto».
 *
 * **Los números están escogidos, no son al azar.** El padrón se reparte en tres
 * grupos de morosidad (al corriente / debe dos meses / debe todo) porque un
 * tablero donde todos deben lo mismo se ve igual de bien roto que funcionando. Y
 * son veinte unidades y no tres porque la página pública de cobranza promete
 * cifras agregadas sin nombres: con tres, cualquiera deduce quién debe, y no se
 * puede juzgar si la promesa se cumple.
 *
 * Lo que **deliberadamente no siembra**, porque es justo lo que hay que caminar a
 * mano al revisar: cortes de caja (los recibos quedan sin cortar, listos para que
 * hagas uno), saldo inicial, y reportes financieros publicados.
 */
final class DemoSeeder extends Seeder
{
    /** Contraseña de todas las cuentas de panel que siembra. */
    private const PASSWORD = 'password';

    /**
     * El celular de quien está probando. Recibe de verdad el SMS del código y el
     * WhatsApp del Recibo, así que es el único número por el que sale algo hacia
     * afuera.
     */
    private const TELEFONO_DE_PRUEBA = '5531269267';

    /**
     * A qué Unidad se le da ese número como **cuenta de Colono**. Una sola, y no
     * es capricho: `users.telefono` es UNIQUE porque el celular *es* la identidad
     * de acceso —el `/entrar` manda el código a un número y espera una cuenta—,
     * así que dos colonos con el mismo celular volverían ambiguo el ingreso.
     *
     * Va sobre una Unidad con adeudo para que el flujo completo —cobrar, emitir,
     * entregar por WhatsApp— se pueda caminar de una sentada.
     */
    private const UNIDAD_DE_PRUEBA = 'Casa 9';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('DemoSeeder no corre en producción: siembra un padrón inventado.');
        }

        // Sembrar encima duplicaría unidades y colonos con los mismos nombres, y
        // el resultado se ve peor que la base vacía: dos «Casa 1» en distinta
        // sección y un tablero que no cuadra con nada.
        if (Seccion::query()->exists()) {
            $this->command?->warn('Ya hay padrón en esta base. Para volver a sembrar:');
            $this->command?->warn('  php artisan migrate:fresh');
            $this->command?->warn('  php artisan db:seed --class=ContenidoInicialSeeder');
            $this->command?->warn('  php artisan db:seed --class=DemoSeeder');

            return;
        }

        $this->call(RoleSeeder::class);

        $panel = $this->cuentasDelPanel();
        $unidades = $this->padron();
        $this->cuotas();
        $this->cobros($unidades, $panel['cobrador']);
        $this->gastos($panel['mesa']);
        $this->solicitudPendiente($unidades[18]);

        $this->resumen();
    }

    /**
     * Una cuenta por rol, cada una con su correo predecible. Sin acumularlos a
     * propósito: el chiste de revisar por rol es entrar con uno solo y ver qué
     * te queda enfrente.
     *
     * @return array{mesa: User, cobrador: User, vigilancia: User}
     */
    private function cuentasDelPanel(): array
    {
        return [
            'mesa' => User::factory()->mesaDirectiva()->create([
                'name' => 'Mesa Directiva (demo)',
                'email' => 'mesa@demo.test',
            ]),
            'cobrador' => User::factory()->cobrador()->conTelefonoConfirmadoPorOtp('5510000001')->create([
                'name' => 'Cobrador (demo)',
                'email' => 'cobrador@demo.test',
            ]),
            'vigilancia' => User::factory()->comiteDeVigilancia()->create([
                'name' => 'Comité de Vigilancia (demo)',
                'email' => 'vigilancia@demo.test',
            ]),
        ];
    }

    /**
     * Veinte unidades en dos secciones. Las dos últimas se quedan **sin titular**
     * a propósito: es el estado normal de un padrón recién cargado —las casas
     * llegan completas y las personas después— y es el que rompe cualquier
     * pantalla que dé por hecho que toda Unidad tiene dueño.
     *
     * @return array<int, Unidad> indexado desde 0
     */
    private function padron(): array
    {
        $primera = Seccion::factory()->llamada('Etapa 1')->create();
        $segunda = Seccion::factory()->llamada('Etapa 2')->create();

        $unidades = [];

        foreach (range(1, 20) as $i) {
            $unidad = Unidad::factory()
                ->enLaSeccion($i <= 12 ? $primera : $segunda)
                ->llamada('Casa '.$i)
                ->create();

            // El contacto que la carga masiva dejó para la Unidad. Lleva el
            // celular de prueba en **todas**: esta tabla no tiene índice único
            // sobre `telefono` —es lo que la Mesa anotó, no una credencial—, así
            // que el flujo de `/acceso` («ya estoy en el padrón, mándame el
            // código») se puede probar reclamando cualquier Unidad.
            ContactoDelPadron::query()->create([
                'unidad_id' => $unidad->getKey(),
                'nombre' => $this->nombreDelColono($i),
                'correo' => $i % 3 === 0 ? null : 'colono'.$i.'@demo.test',
                'telefono' => self::TELEFONO_DE_PRUEBA,
            ]);

            if ($i <= 18) {
                // Uno de cada tres sin correo: es la proporción real del padrón,
                // y el dato obligatorio es el celular. Si todos tuvieran correo,
                // la entrega del Recibo por correo se vería siempre disponible.
                $colono = User::factory()
                    ->colono()
                    ->conTelefonoConfirmadoPorOtp(
                        $unidad->name === self::UNIDAD_DE_PRUEBA
                            ? self::TELEFONO_DE_PRUEBA
                            : '55'.str_pad((string) (20000000 + $i), 8, '0', STR_PAD_LEFT)
                    )
                    ->when($i % 3 === 0, fn ($f) => $f->sinCorreo())
                    ->create(['name' => $this->nombreDelColono($i)]);

                $unidad->asignarTitular($colono);
            }

            $unidades[] = $unidad;
        }

        return $unidades;
    }

    /**
     * Dos vigencias, no una: la pantalla de Vigencias de cuota existe para
     * mostrar que el monto cambia con el tiempo y que lo viejo se congela. Con
     * una sola vigencia esa pantalla y todo el congelamiento se ven idénticos a
     * no tenerlos.
     */
    private function cuotas(): void
    {
        CuotaVigencia::factory()->desde('2024-01-01')->de(700, 70, 10)->create();
        CuotaVigencia::factory()->desde(CarbonImmutable::now()->subMonths(1)->startOfMonth()->toDateString())
            ->de(900, 90, 10)
            ->create();

        $generacion = new GeneracionDeCuotas;

        // Seis meses hacia atrás: suficiente para que la última vigencia parta el
        // historial a la mitad y para que los meses viejos ya tengan sobrecargo.
        foreach (range(5, 0) as $atras) {
            $generacion->delMes(CarbonImmutable::now()->subMonths($atras)->startOfMonth());
        }

        $generacion->aplicarSobrecargos();
    }

    /**
     * Cuántos meses recientes deja sin pagar cada Unidad, y si hay que forzarle
     * el vencimiento del mes en curso.
     *
     * **Es una tabla por nombre y no aritmética sobre el índice**, y el cambio no
     * es de estilo: la versión anterior repartía la morosidad con un `match` sobre
     * la posición en el arreglo y no producía lo que decía producir —Casa 10 y 11
     * salían pagadas cuando debían deber dos meses—. Un fixture cuya distribución
     * hay que verificar contra la base no sirve para revisar nada.
     *
     * La tabla cubre **los cinco estados de URVA-63**, en este orden:
     *
     * | Unidades | Debe | Icono |
     * | --- | --- | --- |
     * | Casa 1-6   | nada                        | palomita verde |
     * | Casa 7, 8  | solo el mes en curso        | reloj **verde** (en gracia) |
     * | Casa 9, 10 | solo el mes en curso        | reloj **ámbar** (vencido) |
     * | Casa 11,12 | el mes en curso y el previo | triángulo rojo |
     * | Casa 13-20 | los seis meses              | tache rojo |
     *
     * @return array{int, bool} meses sin pagar (del más reciente hacia atrás) y
     *                          si se le fuerza el vencimiento del mes en curso
     */
    private function plan(string $unidad): array
    {
        return match ($unidad) {
            'Casa 1', 'Casa 2', 'Casa 3', 'Casa 4', 'Casa 5', 'Casa 6' => [0, false],
            'Casa 7', 'Casa 8' => [1, false],
            'Casa 9', 'Casa 10' => [1, true],
            'Casa 11', 'Casa 12' => [2, false],
            default => [6, false],
        };
    }

    /**
     * Cobra según el plan. Cada Unidad paga con **un** Recibo que cubre todas sus
     * Cuotas saldadas, que es como cobra el Cobrador en la calle: una visita, un
     * comprobante.
     *
     * Ninguno se mete a un Corte: quedan en tránsito para que la revisión del
     * corte de caja tenga con qué.
     *
     * @param  array<int, Unidad>  $unidades
     */
    private function cobros(array $unidades, User $cobrador): void
    {
        $mesEnCurso = CarbonImmutable::now()->startOfMonth()->toDateString();
        $forzarVencidas = [];

        foreach ($unidades as $i => $unidad) {
            [$sinPagar, $forzar] = $this->plan($unidad->name);

            if ($forzar) {
                $forzarVencidas[] = $unidad->getKey();
            }

            $cuotas = $unidad->cuotas()->orderBy('mes')->get();
            $porPagar = $sinPagar === 0
                ? $cuotas
                : $cuotas->slice(0, max($cuotas->count() - $sinPagar, 0));

            if ($porPagar->isEmpty()) {
                continue;
            }

            // `values()` antes del map: `slice()` conserva las llaves originales
            // y `emitir()` recibe una lista, no un mapa.
            $pagos = fn (): array => $unidad->cuotas()
                ->orderBy('mes')
                ->get()
                ->take($porPagar->count())
                ->values()
                ->map(fn (Cuota $cuota): PagoDeCuota => PagoDeCuota::completo($cuota))
                ->all();

            $metodo = $i % 4 === 0 ? MetodoDePago::RetiroSinTarjeta : MetodoDePago::Efectivo;

            $recibo = Recibo::emitir($cobrador, $metodo, $pagos());

            // Casa 1 lleva además un Recibo **cancelado**, para que ese estado
            // exista en pantalla sin fabricarlo a mano. Se cancela y se vuelve a
            // cobrar: cancelar devuelve el saldo a las Cuotas, así que sin el
            // segundo cobro la casa quedaría debiendo todo y dejaría de ser el
            // ejemplo de «al corriente» que esta tabla promete.
            if ($unidad->name === 'Casa 1') {
                $recibo->cancelar($cobrador, 'Cobro duplicado: el Colono ya había pagado en línea.');

                Recibo::emitir($cobrador, $metodo, $pagos());
            }
        }

        // El reloj ámbar pide una Cuota del mes en curso **ya vencida**, y con
        // diez días de gracia eso no puede pasar sino hasta mediados de mes. Se
        // le adelanta el `vence_el` a esas Unidades: es un fixture, y sin esto el
        // quinto estado no se puede ver hasta el día 11.
        if ($forzarVencidas !== []) {
            Cuota::query()
                ->whereIn('unidad_id', $forzarVencidas)
                ->whereDate('mes', $mesEnCurso)
                ->update(['vence_el' => CarbonImmutable::now()->subDay()->toDateString()]);

            (new GeneracionDeCuotas)->aplicarSobrecargos();
        }
    }

    /**
     * Catálogo y movimientos del gasto, repartidos en los últimos tres meses para
     * que el resumen derivado tenga de dónde calcular y la página de detalle
     * tenga renglones.
     */
    private function gastos(User $mesa): void
    {
        $mantenimiento = Categoria::factory()->de(Rubro::Mantenimiento)->create(['nombre' => 'Jardinería']);
        $servicios = Categoria::factory()->de(Rubro::Servicios)->create(['nombre' => 'Energía eléctrica']);
        $administracion = Categoria::factory()->de(Rubro::Administracion)->create(['nombre' => 'Papelería']);
        $otros = Categoria::factory()->de(Rubro::OtrosIngresos)->create(['nombre' => 'Renta del salón']);

        // Archivada, no borrada: el catálogo promete que lo que ya se usó se
        // conserva. Sin una archivada esa promesa no se puede ver.
        Categoria::factory()->de(Rubro::Servicios)->create(['nombre' => 'Fumigación (descontinuada)'])->archivar();

        foreach (range(2, 0) as $atras) {
            $mes = CarbonImmutable::now()->subMonths($atras)->startOfMonth();

            Egreso::factory()->create([
                'fecha' => $mes->addDays(4)->toDateString(),
                'categoria_id' => $mantenimiento->getKey(),
                'monto' => 4800,
                'proveedor' => 'Jardines del Norte',
                'capturado_por' => $mesa->getKey(),
            ]);

            Egreso::factory()->create([
                'fecha' => $mes->addDays(9)->toDateString(),
                'categoria_id' => $servicios->getKey(),
                'monto' => 3150.75,
                'proveedor' => 'CFE',
                'capturado_por' => $mesa->getKey(),
            ]);

            Egreso::factory()->create([
                'fecha' => $mes->addDays(18)->toDateString(),
                'categoria_id' => $administracion->getKey(),
                'monto' => 620.50,
                'proveedor' => 'Papelería La Esquina',
                'capturado_por' => $mesa->getKey(),
            ]);

            OtroIngreso::factory()->create([
                'fecha' => $mes->addDays(22)->toDateString(),
                'categoria_id' => $otros->getKey(),
                'monto' => 1500,
                'origen' => 'Evento del sábado',
                'capturado_por' => $mesa->getKey(),
            ]);
        }
    }

    /**
     * Una solicitud pendiente sobre una de las Unidades sin titular, que es el
     * caso que la pantalla existe para resolver: alguien dice ser de esa casa y
     * la Mesa Directiva tiene que decidir.
     */
    private function solicitudPendiente(Unidad $unidad): void
    {
        SolicitudIdentidad::factory()
            ->paraLaUnidad($unidad)
            ->sinCorreo()
            ->create([
                'nombre' => 'Rosa María Villaseñor',
                'telefono' => '5530000019',
            ]);
    }

    private function nombreDelColono(int $i): string
    {
        $nombres = [
            'Alejandra Rentería', 'Beto Camarena', 'Carmen Ochoa', 'Daniel Escobedo',
            'Elena Zúñiga', 'Fernando Palomo', 'Gabriela Ruvalcaba', 'Héctor Manzo',
            'Irene Cásares', 'Joaquín Bermúdez', 'Karla Sandoval', 'Luis Ángel Treviño',
            'Mónica Iturbe', 'Nicolás Peralta', 'Olivia Fuentes', 'Pablo Guerrero',
            'Quetzalli Ramos', 'Rodrigo Vázquez',
        ];

        return $nombres[$i - 1] ?? 'Colono '.$i;
    }

    private function resumen(): void
    {
        $this->command?->info('Padrón de demo sembrado.');
        $this->command?->info('  Panel: mesa@demo.test / cobrador@demo.test / vigilancia@demo.test');
        $this->command?->info('  Contraseña: '.self::PASSWORD);
        $this->command?->info('  Colonos: 5520000001 .. 5520000018 (celular, para /entrar)');
        $this->command?->info('  TU CELULAR ('.self::TELEFONO_DE_PRUEBA.'):');
        $this->command?->info('    - cuenta de Colono de '.self::UNIDAD_DE_PRUEBA.' (users.telefono es UNIQUE: solo una)');
        $this->command?->info('    - contacto del padron de las 20 Unidades (para probar /acceso con cualquiera)');
        $this->command?->info('  Sin titular: Casa 19 y Casa 20 (Casa 19 con solicitud pendiente)');
        $this->command?->info('  Sin cortar: todos los recibos, para que puedas hacer el primer corte.');
        $this->command?->info('  Estados de cobro (URVA-63):');
        $this->command?->info('    Casa 1-6    al corriente ......... palomita verde');
        $this->command?->info('    Casa 7, 8   solo el mes en curso . reloj VERDE (en gracia)');
        $this->command?->info('    Casa 9, 10  solo el mes en curso . reloj AMBAR (vencido)');
        $this->command?->info('    Casa 11, 12 mes en curso + previo . triangulo rojo');
        $this->command?->info('    Casa 13-20  los seis meses ....... tache rojo');
        $this->command?->info('  Casa 1 lleva ademas un recibo cancelado.');
    }
}
