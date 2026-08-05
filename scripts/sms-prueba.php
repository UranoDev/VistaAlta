<?php

declare(strict_types=1);

/**
 * Manda **un** SMS por Twilio y espera a saber si llegó.
 *
 * Existe porque la aplicación es ciega a esto: `TwilioOtpSender` hace el POST,
 * Twilio contesta `201 queued`, y ahí se acaba lo que el sitio sabe. El 30008
 * —«Unknown error», el catch-all de fallas del carrier— llega minutos después
 * en el estado del mensaje, y sin `StatusCallback` nadie lo recoge. Este script
 * sí espera.
 *
 * Uso, desde la raíz del proyecto:
 *
 *     php scripts/sms-prueba.php 5531269267
 *     php scripts/sms-prueba.php 5531269267 --from=+12293049244
 *     php scripts/sms-prueba.php +525531269267        # a propósito sin el 1
 *
 * Corre igual en local y en producción: arranca el framework por su cuenta, así
 * que no necesita que el comando esté desplegado ni pasa por `artisan`.
 *
 * **No pasa por `OtpService`**, y es deliberado: no consume el límite de envíos
 * ni depende del canal configurado (`OTP_CHANNEL`). Lo que se está midiendo es
 * el carrier, no la aplicación. Cada corrida manda un SMS de verdad y se cobra.
 */

$raiz = dirname(__DIR__);

require $raiz.'/vendor/autoload.php';

$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

// ── Argumentos ───────────────────────────────────────────────────────────────

$destino = null;
$fromOverride = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--from=')) {
        $fromOverride = substr($arg, 7);

        continue;
    }

    $destino ??= $arg;
}

if ($destino === null) {
    fwrite(STDERR, "Falta el destino.\n\n  php scripts/sms-prueba.php 5531269267 [--from=+1...]\n\n");
    exit(1);
}

// ── Lo que se va a mandar ────────────────────────────────────────────────────

$sid = (string) config('services.twilio.sid');
$token = (string) config('services.twilio.token');
$from = $fromOverride ?? (string) config('services.twilio.from');
$lada = (string) config('services.twilio.pais_lada');

/*
 * Espeja `TwilioOtpSender::normalizarTelefono()`: si el número ya viene en
 * E.164 se respeta tal cual, y si no, se le antepone la lada. Va copiado y no
 * llamado porque aquel método es privado — si allá cambia la regla, aquí hay
 * que moverla también o el script deja de medir lo que la app manda.
 */
$to = str_starts_with($destino, '+') ? $destino : $lada.$destino;

$codigo = (string) random_int(100000, 999999);
$cuerpo = "Vista Alta: prueba de entrega {$codigo}";

$linea = str_repeat('─', 62);

echo $linea.PHP_EOL;
printf("%-16s %s\n", 'Entorno', app()->environment().'  ('.gethostname().')');
printf("%-16s %s\n", 'Cuenta', '…'.substr($sid, -6));
printf("%-16s %s\n", 'OTP_CHANNEL', (string) config('services.otp.channel'));
printf("%-16s %s\n", 'Lada', $lada);
printf("%-16s %s\n", 'From', $from.($fromOverride !== null ? '   ← forzado' : ''));
printf("%-16s %s\n", 'Tecleado', $destino);
printf("%-16s %s\n", 'To (real)', $to);
printf("%-16s %s\n", 'Cuerpo', $cuerpo.'  ('.mb_strlen($cuerpo).' car.)');
echo $linea.PHP_EOL;

if ($sid === '' || $token === '') {
    fwrite(STDERR, "Faltan TWILIO_SID o TWILIO_AUTH_TOKEN en la configuración.\n");
    exit(1);
}

// ── El envío ─────────────────────────────────────────────────────────────────

$respuesta = Http::withBasicAuth($sid, $token)
    ->asForm()
    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
        'From' => $from,
        'To' => $to,
        'Body' => $cuerpo,
    ]);

if (! $respuesta->successful()) {
    printf("RECHAZADO por la API  HTTP %d\n", $respuesta->status());
    printf("  código  %s\n", (string) $respuesta->json('code', '—'));
    printf("  mensaje %s\n", (string) $respuesta->json('message', $respuesta->body()));
    exit(1);
}

$mensajeSid = (string) $respuesta->json('sid');

printf("Aceptado por Twilio: %s  estado inicial «%s»\n", $mensajeSid, (string) $respuesta->json('status'));
echo 'Esperando el estado de entrega';

// ── La espera ────────────────────────────────────────────────────────────────

/*
 * `queued` y `sent` son estados de paso: dicen que Twilio lo tomó y que el
 * carrier lo aceptó, no que el teléfono lo haya recibido. El veredicto está en
 * `delivered` o en `undelivered`, y tarda de segundos a un minuto — que es
 * exactamente la ventana que la aplicación nunca mira.
 */
$finales = ['delivered', 'undelivered', 'failed'];
$estado = 'queued';
$error = null;
$tardanza = 0;

for ($intento = 0; $intento < 20; $intento++) {
    sleep(3);
    $tardanza += 3;

    $consulta = Http::withBasicAuth($sid, $token)
        ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages/{$mensajeSid}.json");

    if (! $consulta->successful()) {
        continue;
    }

    $estado = (string) $consulta->json('status');
    $error = $consulta->json('error_code');

    if (in_array($estado, $finales, true)) {
        break;
    }

    echo '.';
}

echo PHP_EOL.$linea.PHP_EOL;

$veredicto = match (true) {
    $estado === 'delivered' => 'ENTREGADO',
    in_array($estado, ['undelivered', 'failed'], true) => 'NO LLEGÓ',
    default => 'SIN RESOLVER (sigue en tránsito)',
};

printf("%-16s %s\n", 'Veredicto', $veredicto);
printf("%-16s %s\n", 'Estado', $estado);
printf("%-16s %s\n", 'Error', $error === null ? '—' : (string) $error);
printf("%-16s %s\n", 'Tardó', $tardanza.'s');
echo $linea.PHP_EOL;

if ((string) $error === '30008') {
    echo PHP_EOL;
    echo "30008 es «Unknown error»: el carrier aceptó y después descartó.".PHP_EOL;
    echo "No distingue entre número inválido, handset inalcanzable y filtrado".PHP_EOL;
    echo "de tráfico A2P. Con el mismo destino entregando otras veces, apunta".PHP_EOL;
    echo "al remitente y no al destino.".PHP_EOL;
}
