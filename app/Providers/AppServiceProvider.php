<?php

namespace App\Providers;

use App\Support\Otp\ArrayOtpSender;
use App\Support\Otp\LogOtpSender;
use App\Support\Otp\OtpSender;
use App\Support\Otp\TwilioOtpSender;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
