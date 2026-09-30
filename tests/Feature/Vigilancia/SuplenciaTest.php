<?php

declare(strict_types=1);

namespace Tests\Feature\Vigilancia;

use App\Support\Vigilancia\RolDeVigilancia;
use App\Support\Vigilancia\Suplencia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Exceptions;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Los días en que alguien cubre los turnos de otro.
 *
 * Todo corre contra un rol de mentira y no contra la configuración que se
 * despliega, por una razón que es la contraria a la de `RolDeVigilanciaTest`: la
 * suplencia real vive en el `.env` de cada máquina, y una prueba que la leyera
 * pasaría o fallaría según quién la corra. `phpunit.xml` además la fuerza a
 * vacía, así que aquí no se cuela la de nadie.
 *
 * El rol es mínimo pero completo: Marisol de día, Ernesto de noche de lunes a
 * sábado y Luis el domingo corrido, sin huecos ni traslapes. La suplencia hace
 * que Luis cubra los turnos de Ernesto el miércoles 30 de septiembre y el jueves
 * 1 de octubre de 2026.
 */
class SuplenciaTest extends TestCase
{
    private const ZONA = 'America/Mexico_City';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'contenido.vigilancia.zona_horaria' => self::ZONA,
            'contenido.vigilancia.vigilantes' => [
                [
                    'nombre' => 'Marisol V.',
                    'etiqueta' => 'Turno de día',
                    'foto' => null,
                    'desde' => null,
                    'turnos' => [['dias' => [1, 2, 3, 4, 5, 6], 'entra' => '06:00', 'sale' => '22:00']],
                ],
                [
                    'nombre' => 'Ernesto S.',
                    'etiqueta' => 'Turno de noche',
                    'foto' => null,
                    'desde' => null,
                    'turnos' => [['dias' => [1, 2, 3, 4, 5, 6], 'entra' => '22:00', 'sale' => '06:00']],
                ],
                [
                    'nombre' => 'Luis M.',
                    'etiqueta' => 'Turno de domingo',
                    'foto' => null,
                    'desde' => null,
                    'turnos' => [['dias' => [7], 'entra' => '06:00', 'sale' => '06:00']],
                ],
            ],
            'contenido.vigilancia.suplencias' => [
                ['ausente' => 'Ernesto S.', 'cubre' => 'Luis M.', 'desde' => '2026-09-30', 'dias' => 2],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function enElAcceso(string $momento): CarbonImmutable
    {
        return CarbonImmutable::parse($momento, self::ZONA);
    }

    /**
     * Lo que se anuncia es quién está **y qué turno cubre**. El rótulo de Luis
     * —«Turno de domingo»— no describe un miércoles a las 23:00, así que sale el
     * de Ernesto, que es el turno que de verdad se está cubriendo.
     */
    public function test_dentro_de_la_suplencia_anuncia_al_suplente_con_el_rotulo_del_turno_que_cubre(): void
    {
        $deGuardia = RolDeVigilancia::deLaConfiguracion()->deGuardia($this->enElAcceso('2026-09-30 23:00'));

        $this->assertSame('Luis M.', $deGuardia?->nombre);
        $this->assertSame('Turno de noche', $deGuardia?->etiqueta);
    }

    /**
     * El día que cuenta es el de **entrada**. A las 03:00 del jueves quien está
     * entró el miércoles, y es el miércoles el que decide. Los tres momentos son
     * los tres bordes: el turno que entró el día antes de empezar la suplencia
     * lo termina su titular, el que entró el último día se cubre entero hasta la
     * mañana siguiente, y el que entró el día después ya es del titular.
     */
    public function test_la_madrugada_es_del_dia_en_que_entro_el_turno(): void
    {
        $rol = RolDeVigilancia::deLaConfiguracion();

        // Entró el martes 29, un día antes de que empiece: lo termina Ernesto.
        $this->assertSame('Ernesto S.', $rol->deGuardia($this->enElAcceso('2026-09-30 03:00'))?->nombre);

        // Entró el miércoles 30, primer día: madrugada del jueves cubierta.
        $this->assertSame('Luis M.', $rol->deGuardia($this->enElAcceso('2026-10-01 03:00'))?->nombre);

        // Entró el jueves 1, último día: madrugada del viernes todavía cubierta.
        $this->assertSame('Luis M.', $rol->deGuardia($this->enElAcceso('2026-10-02 03:00'))?->nombre);

        // Entró el viernes 2, ya fuera: la madrugada del sábado es de Ernesto.
        $this->assertSame('Ernesto S.', $rol->deGuardia($this->enElAcceso('2026-10-03 03:00'))?->nombre);
        $this->assertSame('Ernesto S.', $rol->deGuardia($this->enElAcceso('2026-10-02 23:00'))?->nombre);
    }

    /**
     * `dias` incluye el primer día. Con un solo día se cubre la noche de `desde`
     * y ya no la siguiente: el error fácil es entender «14 días» como «14 después
     * de hoy» y dejar el rol un día más largo de lo que se dijo.
     */
    public function test_dias_cuenta_el_primer_dia(): void
    {
        config(['contenido.vigilancia.suplencias' => [
            ['ausente' => 'Ernesto S.', 'cubre' => 'Luis M.', 'desde' => '2026-09-30', 'dias' => 1],
        ]]);

        $rol = RolDeVigilancia::deLaConfiguracion();

        $this->assertSame('Luis M.', $rol->deGuardia($this->enElAcceso('2026-09-30 23:00'))?->nombre);
        $this->assertSame('Ernesto S.', $rol->deGuardia($this->enElAcceso('2026-10-01 23:00'))?->nombre);
    }

    /**
     * Catorce días contando el primero terminan el decimocuarto, no el
     * siguiente. Con fechas inventadas a propósito: las de una ausencia de verdad
     * no van a un archivo que se versiona.
     */
    public function test_catorce_dias_contando_el_primero_terminan_el_decimocuarto(): void
    {
        $suplencia = new Suplencia('Ernesto S.', 'Luis M.', '2026-03-02', 14);

        $this->assertSame('2026-03-15', $suplencia->ultimoDia());
        $this->assertTrue($suplencia->alcanzaA('Ernesto S.', $this->enElAcceso('2026-03-15 22:00')));
        $this->assertFalse($suplencia->alcanzaA('Ernesto S.', $this->enElAcceso('2026-03-16 22:00')));
        $this->assertFalse($suplencia->alcanzaA('Ernesto S.', $this->enElAcceso('2026-03-01 22:00')));
    }

    /**
     * Las tarjetas son las mismas personas con los mismos rótulos: la suplencia
     * mueve a quién se anuncia, no quién está en el rol. Por eso el ausente
     * sigue saliendo entre los vigilantes.
     */
    public function test_el_rol_de_tarjetas_no_cambia_durante_la_suplencia(): void
    {
        $tarjetas = collect(RolDeVigilancia::deLaConfiguracion()->vigilantes())
            ->mapWithKeys(fn ($v): array => [$v->nombre => $v->etiqueta])
            ->all();

        $this->assertSame([
            'Marisol V.' => 'Turno de día',
            'Ernesto S.' => 'Turno de noche',
            'Luis M.' => 'Turno de domingo',
        ], $tarjetas);
    }

    /**
     * Si la suplencia nombra a alguien que no está en el rol —un error de dedo
     * en el `.env`— la página dice que no sabe. Anunciar al ausente sería dar el
     * nombre y la cara de quien sabemos que no está en el acceso.
     */
    public function test_un_suplente_que_no_existe_deja_sin_guardia_en_vez_de_anunciar_al_ausente(): void
    {
        config(['contenido.vigilancia.suplencias' => [
            ['ausente' => 'Ernesto S.', 'cubre' => 'Nadie Asi', 'desde' => '2026-09-30', 'dias' => 2],
        ]]);

        $this->assertNull(RolDeVigilancia::deLaConfiguracion()->deGuardia($this->enElAcceso('2026-09-30 23:00')));
    }

    /**
     * El barrido: doce días en pasos de media hora, cruzando los dos bordes de la
     * suplencia. Exige lo mismo que el barrido de la semana —que siempre haya
     * alguien— y dos cosas propias: que Ernesto nunca se anuncie en un turno que
     * entró dentro de la suplencia, y que quien suple no esté cubriendo a la vez
     * su propio turno, que lo dejaría en dos lugares con una sola persona.
     */
    public function test_el_barrido_nunca_deja_un_hueco_ni_anuncia_al_ausente_ni_dobla_al_suplente(): void
    {
        $rol = RolDeVigilancia::deLaConfiguracion();
        $vigilantes = collect($rol->vigilantes())->keyBy('nombre');

        $momento = $this->enElAcceso('2026-09-27 00:00');
        $fin = $this->enElAcceso('2026-10-09 00:00');

        while ($momento < $fin) {
            $deGuardia = $rol->deGuardia($momento);

            $this->assertNotNull($deGuardia, 'Nadie de guardia el '.$momento->format('D d/m H:i'));

            $entradaDeErnesto = $vigilantes['Ernesto S.']->entradaDelTurno($momento);

            if ($entradaDeErnesto !== null && in_array($entradaDeErnesto->toDateString(), ['2026-09-30', '2026-10-01'], true)) {
                $this->assertSame('Luis M.', $deGuardia->nombre, 'Debía cubrir Luis el '.$momento->format('D d/m H:i'));

                $this->assertFalse(
                    $vigilantes['Luis M.']->estaDeGuardia($momento),
                    'Luis estaba cubriendo dos turnos a la vez el '.$momento->format('D d/m H:i'),
                );
            }

            $momento = $momento->addMinutes(30);
        }
    }

    /**
     * En el `.env` la lista llega como texto JSON, no como arreglo. Es la forma
     * en que se despliega, así que se prueba tal cual llega.
     */
    public function test_lee_la_suplencia_cuando_llega_como_texto_json_del_env(): void
    {
        config(['contenido.vigilancia.suplencias' => '[{"ausente":"Ernesto S.","cubre":"Luis M.","desde":"2026-09-30","dias":2}]']);

        $this->assertSame(
            'Luis M.',
            RolDeVigilancia::deLaConfiguracion()->deGuardia($this->enElAcceso('2026-09-30 23:00'))?->nombre,
        );
    }

    /**
     * Vacío, ausente o `null` significan que nadie falta, y no son un error que
     * haya que reportar: es el estado de casi todos los días.
     */
    public function test_sin_suplencias_el_rol_es_el_normal_y_no_se_reporta_nada(): void
    {
        Exceptions::fake();

        foreach ([null, '', '   ', []] as $vacio) {
            config(['contenido.vigilancia.suplencias' => $vacio]);

            $this->assertSame(
                'Ernesto S.',
                RolDeVigilancia::deLaConfiguracion()->deGuardia($this->enElAcceso('2026-09-30 23:00'))?->nombre,
            );
        }

        Exceptions::assertNothingReported();
    }

    /**
     * Un JSON roto se reporta y se ignora: tumbar `/vigilancia` por un error de
     * dedo en una variable opcional dejaría a todo el fraccionamiento sin saber
     * quién cuida. El rol sale como es normalmente y el error queda en el
     * registro.
     */
    public function test_un_json_roto_se_reporta_y_el_rol_sale_sin_suplencias(): void
    {
        Exceptions::fake();

        config(['contenido.vigilancia.suplencias' => "[{'ausente': Ernesto}"]);

        $this->assertSame(
            'Ernesto S.',
            RolDeVigilancia::deLaConfiguracion()->deGuardia($this->enElAcceso('2026-09-30 23:00'))?->nombre,
        );

        Exceptions::assertReported(InvalidArgumentException::class);
    }

    /**
     * Un renglón incompleto no tumba la página: simplemente no alcanza a nadie.
     */
    public function test_un_renglon_incompleto_no_alcanza_a_nadie_ni_revienta(): void
    {
        config(['contenido.vigilancia.suplencias' => [
            ['ausente' => 'Ernesto S.'],
            ['ausente' => 'Ernesto S.', 'cubre' => 'Luis M.', 'desde' => 'mañana', 'dias' => 2],
            ['ausente' => 'Ernesto S.', 'cubre' => 'Luis M.', 'desde' => '2026-09-30', 'dias' => 0],
            'esto ni es un renglón',
        ]]);

        $this->assertSame(
            'Ernesto S.',
            RolDeVigilancia::deLaConfiguracion()->deGuardia($this->enElAcceso('2026-09-30 23:00'))?->nombre,
        );
    }

    /**
     * La página anuncia al suplente en «En este momento» y **no dice nada más**:
     * ni que alguien descansa, ni por qué, ni hasta cuándo. Unas fechas de
     * ausencia son la situación laboral de una persona identificada, y saber
     * cuándo el acceso queda con otra es parte del rol que la página se niega a
     * imprimir (URVA-79). Es una omisión, y una omisión se repone sin querer con
     * un renglón bienintencionado.
     */
    public function test_la_pagina_anuncia_al_suplente_y_no_dice_nada_de_la_ausencia(): void
    {
        CarbonImmutable::setTestNow($this->enElAcceso('2026-09-30 23:00'));

        $respuesta = $this->get(route('vigilancia'));

        $respuesta->assertOk();
        $respuesta->assertSeeInOrder(['En este momento', 'Luis M.', 'Turno de noche'], escape: false);

        // El ausente sigue en su tarjeta: es el rol, no el aviso.
        $respuesta->assertSee('Ernesto S.', escape: false);

        foreach (['descans', 'suplen', 'ausen', 'vacacion', 'incapacidad', 'cubre a', '"dias"', '2026-10-01'] as $palabra) {
            $respuesta->assertDontSee($palabra, escape: false);
        }
    }
}
