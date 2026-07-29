<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Otp;

use App\Support\Otp\TwilioOtpSender;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwilioOtpSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twilio.sid' => 'ACtest',
            'services.twilio.token' => 'token-secreto',
            'services.twilio.from' => '+15005550006',
            'services.twilio.pais_lada' => '+52',
        ]);
    }

    public function test_manda_el_codigo_por_la_messages_api_de_twilio(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SMtest'], 201),
        ]);

        (new TwilioOtpSender)->send('4421234567', '123456');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/ACtest/Messages.json'
                && $request['To'] === '+524421234567'
                && $request['From'] === '+15005550006'
                && str_contains($request['Body'], '123456');
        });
    }

    public function test_no_altera_los_numeros_que_ya_vienen_en_formato_e164(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMtest'], 201)]);

        (new TwilioOtpSender)->send('+14155552671', '654321');

        Http::assertSent(fn ($request) => $request['To'] === '+14155552671');
    }

    public function test_revienta_cuando_twilio_responde_con_error(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'Invalid From number'], 400)]);

        $this->expectException(RequestException::class);

        (new TwilioOtpSender)->send('4421234567', '123456');
    }
}
