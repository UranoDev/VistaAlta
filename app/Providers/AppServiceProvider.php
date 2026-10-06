<?php

namespace App\Providers;

use App\Support\Otp\ArrayOtpSender;
use App\Support\Otp\ArrayWhatsAppOtpSender;
use App\Support\Otp\CloudApiWhatsAppOtpSender;
use App\Support\Otp\LogOtpSender;
use App\Support\Otp\LogWhatsAppOtpSender;
use App\Support\Otp\OtpSender;
use App\Support\Otp\TwilioOtpSender;
use App\Support\Otp\WhatsAppOtpSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSender::class, fn () => match (config('services.otp.channel')) {
            'array' => new ArrayOtpSender,
            'twilio' => new TwilioOtpSender,
            default => new LogOtpSender,
        });

        $this->app->bind(WhatsAppOtpSender::class, fn () => match (config('services.whatsapp.channel')) {
            'array' => new ArrayWhatsAppOtpSender,
            'cloud' => new CloudApiWhatsAppOtpSender,
            default => new LogWhatsAppOtpSender,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
