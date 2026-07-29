<?php

declare(strict_types=1);

namespace Tests\Feature\Otp;

use App\Models\Otp;
use App\Support\Otp\ArrayOtpSender;
use App\Support\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private const PROPOSITO = 'comentario';

    protected function setUp(): void
    {
        parent::setUp();

        ArrayOtpSender::$enviados = [];
    }

    private function servicio(): OtpService
    {
        return $this->app->make(OtpService::class);
    }

    public function test_generar_guarda_el_codigo_hasheado_y_lo_manda_al_sender(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);

        $codigo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $codigo);

        $otp = Otp::sole();

        $this->assertSame('4421234567', $otp->telefono);
        $this->assertSame(self::PROPOSITO, $otp->proposito);
        $this->assertNotSame($codigo, $otp->codigo_hash);
        $this->assertTrue(Hash::check($codigo, $otp->codigo_hash));
        $this->assertNull($otp->verificado_en);
    }

    public function test_verificar_acepta_el_codigo_vigente_y_lo_marca_verificado(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $codigo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->assertTrue($this->servicio()->verificar('4421234567', self::PROPOSITO, $codigo));
        $this->assertNotNull(Otp::sole()->verificado_en);
    }

    public function test_un_codigo_ya_verificado_no_sirve_dos_veces(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $codigo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->servicio()->verificar('4421234567', self::PROPOSITO, $codigo);

        $this->assertFalse($this->servicio()->verificar('4421234567', self::PROPOSITO, $codigo));
    }

    public function test_un_codigo_equivocado_falla_y_cuenta_el_intento(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);

        $this->assertFalse($this->servicio()->verificar('4421234567', self::PROPOSITO, '000000'));
        $this->assertSame(1, Otp::sole()->intentos);
    }

    public function test_se_agota_a_los_cinco_intentos_fallidos(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $codigo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        foreach (range(1, 5) as $ignorado) {
            $this->servicio()->verificar('4421234567', self::PROPOSITO, '000000');
        }

        $this->assertFalse($this->servicio()->verificar('4421234567', self::PROPOSITO, $codigo));
    }

    public function test_un_codigo_expirado_no_sirve(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $codigo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->travel(6)->minutes();

        $this->assertFalse($this->servicio()->verificar('4421234567', self::PROPOSITO, $codigo));
    }

    public function test_generar_de_nuevo_invalida_el_codigo_anterior(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $primero = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $segundo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->assertSame(1, Otp::count());
        $this->assertFalse($this->servicio()->verificar('4421234567', self::PROPOSITO, $primero));
        $this->assertTrue($this->servicio()->verificar('4421234567', self::PROPOSITO, $segundo));
    }

    public function test_los_codigos_estan_scopeados_por_proposito(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $codigo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->assertFalse($this->servicio()->verificar('4421234567', 'otro-proposito', $codigo));
        $this->assertTrue($this->servicio()->verificar('4421234567', self::PROPOSITO, $codigo));
    }

    public function test_el_codigo_de_un_telefono_no_valida_a_otro(): void
    {
        $this->servicio()->generar('4421234567', self::PROPOSITO);
        $codigo = ArrayOtpSender::ultimoCodigoPara('4421234567');

        $this->assertFalse($this->servicio()->verificar('4429999999', self::PROPOSITO, $codigo));
    }
}
