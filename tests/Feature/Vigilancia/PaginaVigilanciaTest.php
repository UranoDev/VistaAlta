<?php

declare(strict_types=1);

namespace Tests\Feature\Vigilancia;

use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * La página pública de Vigilancia.
 *
 * Más de la mitad de esta clase prueba lo que la página **no** dice, y es a
 * propósito. Publicar nombre y cara de cuatro trabajadores vino con tres
 * restricciones decididas en URVA-79 —sin horarios, sin liga al grupo, sin
 * situación laboral— y ninguna de las tres se defiende sola: son omisiones, y
 * una omisión se repone sin querer con un renglón bienintencionado seis meses
 * después, cuando ya nadie recuerda por qué no estaba.
 */
class PaginaVigilanciaTest extends TestCase
{
    /**
     * Un miércoles a las 10:37, hora del acceso: turno de mañana.
     *
     * La hora importa para las pruebas de abajo. La página imprime el momento de
     * consulta —«Consultado el … 10:37 h»—, así que si se congelara a las 06:00
     * o a las 14:00 el `assertDontSee` de los horarios fallaría por el reloj y no
     * por lo que se está midiendo.
     */
    private const AHORA = '2026-08-05 10:37';

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(self::AHORA, config('contenido.vigilancia.zona_horaria'))
        );

        config(['contenido.vigilancia.vigilantes' => [
            [
                'nombre' => 'Marisol V.',
                'etiqueta' => 'Turno de mañana',
                'foto' => null,
                'desde' => null,
                'turnos' => [['dias' => [1, 2, 3, 4, 5, 6], 'entra' => '06:00', 'sale' => '14:00']],
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
                'foto' => 'luis.jpg',
                'desde' => '2026-08-02',
                'turnos' => [['dias' => [7], 'entra' => '06:00', 'sale' => '06:00']],
            ],
        ]]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_la_pagina_responde_sin_autenticacion(): void
    {
        $this->get(route('vigilancia'))
            ->assertOk()
            ->assertSee('Quién cuida Vista Alta', escape: false);
    }

    public function test_muestra_a_los_cuatro_con_su_rotulo_de_turno(): void
    {
        $respuesta = $this->get(route('vigilancia'));

        foreach (['Marisol V.', 'Ernesto S.', 'Luis M.'] as $nombre) {
            $respuesta->assertSee($nombre, escape: false);
        }

        foreach (['Turno de mañana', 'Turno de noche', 'Turno de domingo'] as $etiqueta) {
            $respuesta->assertSee($etiqueta, escape: false);
        }
    }

    public function test_anuncia_a_quien_esta_de_guardia_segun_la_hora_del_acceso(): void
    {
        $this->get(route('vigilancia'))->assertSeeInOrder(
            ['En este momento', 'Marisol V.'],
            escape: false,
        );
    }

    public function test_a_las_22_00_ya_anuncia_al_de_la_noche(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-08-05 22:00', config('contenido.vigilancia.zona_horaria'))
        );

        $this->get(route('vigilancia'))->assertSeeInOrder(
            ['En este momento', 'Ernesto S.'],
            escape: false,
        );
    }

    /**
     * La restricción central (URVA-79, decisiones 2 y 3): las horas viven en la
     * configuración y **no salen al HTML**. Ni el horario de cada quien, ni la
     * hora del relevo.
     *
     * Quien consulte una página que dice «hasta las 06:00» cuatro veces ya
     * reconstruyó el rol completo de las cuatro personas que cubren el acceso.
     */
    public function test_no_publica_ningun_horario(): void
    {
        $respuesta = $this->get(route('vigilancia'));

        foreach (['06:00', '14:00', '22:00'] as $hora) {
            $respuesta->assertDontSee($hora);
        }

        $respuesta->assertDontSee('Entrega la guardia');
        $respuesta->assertDontSee('le sigue', escape: false);
    }

    /**
     * La otra restricción que no se defiende sola: la liga de invitación es una
     * credencial al portador, y hay quien las cosecha de páginas públicas. La
     * página nombra el grupo y manda con la Mesa Directiva.
     */
    public function test_no_publica_la_liga_del_grupo_de_whatsapp(): void
    {
        $respuesta = $this->get(route('vigilancia'));

        $respuesta->assertDontSee('chat.whatsapp.com');
        $respuesta->assertDontSee('wa.me');
        $respuesta->assertSee('grupo de WhatsApp de Colonos', escape: false);
        $respuesta->assertSee('solicita tu incorporación a la Mesa Directiva', escape: false);
    }

    /**
     * De la cuarta persona se publica cuándo entró y nada más. Su situación
     * laboral no va en el sitio de su lugar de trabajo.
     */
    public function test_de_la_incorporacion_solo_dice_la_fecha(): void
    {
        $this->get(route('vigilancia'))
            ->assertSee('Se incorporó el 2 de agosto de 2026', escape: false)
            ->assertDontSee('prueba', escape: false)
            ->assertDontSee('periodo de prueba', escape: false);
    }

    public function test_queda_fuera_de_los_buscadores(): void
    {
        $this->get(route('vigilancia'))->assertSee('noindex', escape: false);

        $this->assertStringContainsString(
            'Disallow: /vigilancia',
            (string) file_get_contents(public_path('robots.txt')),
        );
    }

    /**
     * Sin foto no hay hueco ni marco vacío: van las iniciales. Que alguien haya
     * preferido no publicar su cara no puede notarse como una falta, y son
     * cuatro tarjetas juntas donde la diferencia saltaría a la vista.
     */
    public function test_quien_no_tiene_foto_sale_con_sus_iniciales(): void
    {
        $respuesta = $this->get(route('vigilancia'));

        $respuesta->assertSee('MV', escape: false);
        $respuesta->assertSee('ES', escape: false);
        $respuesta->assertSee('img/vigilantes/luis.jpg', escape: false);
    }

    /**
     * Que la configuración esté incompleta no puede tirar la página con un 500.
     */
    public function test_sin_nadie_de_guardia_lo_dice_en_vez_de_reventar(): void
    {
        config(['contenido.vigilancia.vigilantes' => []]);

        $this->get(route('vigilancia'))
            ->assertOk()
            ->assertSee('No tenemos registrado quién está de guardia', escape: false);
    }
}
