<?php

declare(strict_types=1);

namespace Tests\Feature\Vigilancia;

use App\Support\Vigilancia\RolDeVigilancia;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * El rol de guardias: quién está en el acceso a cada hora.
 *
 * La prueba que sostiene todo lo demás es `test_la_semana_completa_queda_cubierta`,
 * y corre contra la **configuración que se despliega**, no contra un arreglo de
 * mentira. Los cuatro turnos se llenan a mano en `config/contenido.php`; el día
 * que alguien recorte media hora o adelante una entrada, la página anunciaría
 * «24 horas, los siete días» con un hueco adentro y nadie se enteraría hasta que
 * pasara algo a esa hora. Aquí se entera en el acto.
 */
class RolDeVigilanciaTest extends TestCase
{
    /** El lunes con el que arranca el barrido. 2026-08-03 es lunes. */
    private const LUNES = '2026-08-03';

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * Una semana entera en pasos de media hora, contra la configuración real.
     *
     * «Exactamente uno» es lo que se exige, y las dos mitades importan por
     * separado: cero es un hueco de cobertura, y dos es un traslape que además
     * volvería ambiguo lo que la página imprime. Los pasos de media hora caen
     * justo en 06:00, 14:00 y 22:00, que son los tres relevos.
     */
    public function test_la_semana_completa_queda_cubierta_por_exactamente_un_vigilante(): void
    {
        $rol = RolDeVigilancia::deLaConfiguracion();
        $zona = config('contenido.vigilancia.zona_horaria');

        $momento = CarbonImmutable::parse(self::LUNES, $zona)->startOfDay();
        $fin = $momento->addWeek();

        while ($momento < $fin) {
            $deGuardia = collect($rol->vigilantes())
                ->filter(fn ($vigilante): bool => $vigilante->estaDeGuardia($momento))
                ->values();

            $this->assertCount(
                1,
                $deGuardia,
                sprintf(
                    'El %s debería haber exactamente un vigilante de guardia, y hay %d (%s).',
                    $momento->format('D d/m H:i'),
                    $deGuardia->count(),
                    $deGuardia->isEmpty() ? 'nadie' : $deGuardia->pluck('etiqueta')->implode(', '),
                ),
            );

            $momento = $momento->addMinutes(30);
        }
    }

    /**
     * Los bordes, uno por uno.
     *
     * Los tres pares `:59` / `:00` son el minuto del relevo: el intervalo es
     * abierto por la derecha, así que a las 14:00 en punto ya entró el de la
     * tarde. Las costuras del fin de semana son las que más fácil se rompen al
     * editar la configuración: el domingo a la 01:00 quien está es el del sábado
     * por la noche, y el lunes a las 05:59 sigue siendo el del domingo, que
     * entró 24 horas antes.
     */
    #[DataProvider('momentos')]
    public function test_quien_esta_de_guardia_en_cada_borde(string $momento, string $etiquetaEsperada): void
    {
        $zona = config('contenido.vigilancia.zona_horaria');

        $deGuardia = RolDeVigilancia::deLaConfiguracion()
            ->deGuardia(CarbonImmutable::parse($momento, $zona));

        $this->assertNotNull($deGuardia, "Nadie de guardia el {$momento}.");
        $this->assertSame($etiquetaEsperada, $deGuardia->etiqueta);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function momentos(): array
    {
        return [
            // Un miércoles cualquiera, los tres relevos.
            'miércoles 05:59' => ['2026-08-05 05:59', 'Turno de noche'],
            'miércoles 06:00' => ['2026-08-05 06:00', 'Turno de mañana'],
            'miércoles 13:59' => ['2026-08-05 13:59', 'Turno de mañana'],
            'miércoles 14:00' => ['2026-08-05 14:00', 'Turno de tarde'],
            'miércoles 21:59' => ['2026-08-05 21:59', 'Turno de tarde'],
            'miércoles 22:00' => ['2026-08-05 22:00', 'Turno de noche'],

            // La costura del sábado a domingo: el turno de noche entra el sábado
            // y sale el domingo, antes de que empiece el de domingo.
            'sábado 22:00' => ['2026-08-08 22:00', 'Turno de noche'],
            'domingo 01:00' => ['2026-08-09 01:00', 'Turno de noche'],
            'domingo 05:59' => ['2026-08-09 05:59', 'Turno de noche'],

            // Las 24 horas corridas del domingo, y la entrega del lunes.
            'domingo 06:00' => ['2026-08-09 06:00', 'Turno de domingo'],
            'domingo 23:00' => ['2026-08-09 23:00', 'Turno de domingo'],
            'lunes 03:00' => ['2026-08-10 03:00', 'Turno de domingo'],
            'lunes 05:59' => ['2026-08-10 05:59', 'Turno de domingo'],
            'lunes 06:00' => ['2026-08-10 06:00', 'Turno de mañana'],
        ];
    }

    /**
     * La hora es la del acceso, no la del servidor. La aplicación corre en UTC y
     * la diferencia con la Ciudad de México es de seis horas: a las 03:00 UTC
     * aquí son las 21:00 del día anterior, que es turno de tarde y no de noche.
     * Sin la zona horaria, la página anunciaría al vigilante equivocado durante
     * buena parte del día.
     */
    public function test_la_guardia_se_calcula_con_la_hora_del_acceso_y_no_con_utc(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-05 03:00', 'UTC'));

        $this->assertSame('21:00', RolDeVigilancia::ahora()->format('H:i'));
        $this->assertSame('Turno de tarde', RolDeVigilancia::deLaConfiguracion()->deGuardia(RolDeVigilancia::ahora())?->etiqueta);
    }

    /**
     * Que no haya nadie tiene que ser un estado posible y no una excepción: la
     * configuración se llena a mano, y la página prefiere decir que no sabe.
     */
    public function test_sin_nadie_configurado_no_hay_guardia_en_vez_de_reventar(): void
    {
        config(['contenido.vigilancia.vigilantes' => []]);

        $this->assertNull(RolDeVigilancia::deLaConfiguracion()->deGuardia(CarbonImmutable::parse('2026-08-05 10:00')));
    }
}
