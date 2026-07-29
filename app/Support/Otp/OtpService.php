<?php

declare(strict_types=1);

namespace App\Support\Otp;

use App\Exceptions\LimiteDeEnvioDeOtpExcedido;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    private const TTL_MINUTOS = 5;

    private const MAX_INTENTOS = 5;

    public function __construct(
        private readonly OtpSender $sender,
        private readonly LimiteDeEnvioDeOtp $limite,
    ) {}

    /**
     * El límite se cobra antes de tocar nada: un envío rechazado no manda SMS
     * y tampoco invalida el código que el teléfono ya tenga vigente.
     *
     * `$ip` en null toma la de la petición en curso.
     *
     * @throws LimiteDeEnvioDeOtpExcedido
     */
    public function generar(string $telefono, string $proposito, ?string $ip = null): void
    {
        $this->limite->consumir($telefono, $ip);

        Otp::where('telefono', $telefono)
            ->where('proposito', $proposito)
            ->whereNull('verificado_en')
            ->delete();

        $codigo = (string) random_int(100000, 999999);

        Otp::create([
            'telefono' => $telefono,
            'proposito' => $proposito,
            'codigo_hash' => Hash::make($codigo),
            'expira_en' => now()->addMinutes(self::TTL_MINUTOS),
        ]);

        $this->sender->send($telefono, $codigo);
    }

    public function verificar(string $telefono, string $proposito, string $codigo): bool
    {
        $otp = Otp::where('telefono', $telefono)
            ->where('proposito', $proposito)
            ->whereNull('verificado_en')
            ->latest()
            ->first();

        if (! $otp || $otp->expira_en->isPast() || $otp->intentos >= self::MAX_INTENTOS) {
            return false;
        }

        if (! Hash::check($codigo, $otp->codigo_hash)) {
            $otp->increment('intentos');

            return false;
        }

        $otp->update(['verificado_en' => now()]);

        return true;
    }
}
