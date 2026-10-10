<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Los datos personales que llevan dos años sin actualizarse se borran solos, como
 * dice el Aviso de Privacidad. Necesita que el servidor corra `schedule:run` cada
 * minuto (una entrada de cron en Plesk): ver docs/deploy-plesk.md.
 */
Schedule::command('datos:depurar')->dailyAt('03:30');
