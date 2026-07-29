<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Otp;

use App\Support\Otp\ArrayOtpSender;
use App\Support\Otp\LogOtpSender;
use App\Support\Otp\OtpSender;
use App\Support\Otp\TwilioOtpSender;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OtpSenderBindingTest extends TestCase
{
    public static function canales(): array
    {
        return [
            'log' => ['log', LogOtpSender::class],
            'array' => ['array', ArrayOtpSender::class],
            'twilio' => ['twilio', TwilioOtpSender::class],
            'desconocido cae en log' => ['no-existe', LogOtpSender::class],
        ];
    }

    #[DataProvider('canales')]
    public function test_el_canal_configurado_decide_el_sender(string $canal, string $clase): void
    {
        config(['services.otp.channel' => $canal]);

        $this->assertInstanceOf($clase, $this->app->make(OtpSender::class));
    }
}
