<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\EntregaDeDatos;
use App\Support\Datos\ArchivoDeEntrega;
use App\Support\Datos\ReunirDatosDeUnaPersona;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Entrega a una persona todo lo que el sitio tiene de ella: un ZIP con un TXT para
 * leerlo y un JSON para llevárselo, mandado por correo. Es la respuesta a una
 * solicitud de acceso (derechos ARCO, sección 5 del Aviso de Privacidad).
 *
 * ```
 * php artisan datos:entregar marta@correo.com
 * php artisan datos:entregar 5512345678 --a=marta@correo.com
 * php artisan datos:entregar marta@correo.com --clave=una-clave-larga
 * php artisan datos:entregar marta@correo.com --sin-enviar
 * ```
 *
 * ## Quién lo corre, y qué no hace
 *
 * Lo corre alguien de la Administración **después de comprobar quién pide los
 * datos**: el comando no puede saber si quien escribió al buzón es la persona. Lo
 * único que hace por su cuenta es no mandar los datos a un correo que nadie
 * verificó (ver `DatosDeUnaPersona::correoDeEntrega()`); con `--a` el operador
 * asume esa decisión.
 *
 * El ZIP se escribe en `storage/app/entregas`, se manda y se borra. No queda copia:
 * otra copia de los datos de la persona sería justo lo que el Aviso dice que se
 * borra. Con `--sin-enviar` se queda ahí para que el operador lo revise, y es
 * suyo borrarlo.
 *
 * `--clave` cifra el ZIP (AES-256). La clave **no va en el correo**: se le da a la
 * persona por otro medio, por ejemplo por SMS a su celular.
 */
#[Signature('datos:entregar
    {identificador : El correo o el celular (a 10 dígitos) de la persona}
    {--a= : Correo al que se manda. Por omisión, el correo de la persona}
    {--clave= : Protege el ZIP con esta clave, que se le da por otro medio}
    {--sin-enviar : Deja el ZIP en storage/app/entregas y no manda nada}')]
#[Description('Entrega a una persona todo lo que el sitio tiene de ella, por correo')]
class EntregarDatosDeUnaPersona extends Command
{
    public function handle(ReunirDatosDeUnaPersona $reunir, ArchivoDeEntrega $archivo): int
    {
        try {
            $persona = $reunir->para((string) $this->argument('identificador'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($persona->fuentes as $fuente) {
            $this->line(sprintf('%s: %d', $fuente['titulo'], count($fuente['filas'])));
        }

        if ($persona->estaVacio()) {
            $this->error('No hay datos de esa persona; no se generó ni se mandó nada.');

            return self::FAILURE;
        }

        $sinEnviar = (bool) $this->option('sin-enviar');
        $destino = null;

        if (! $sinEnviar) {
            $destino = $this->option('a') !== null && $this->option('a') !== ''
                ? (string) $this->option('a')
                : $persona->correoDeEntrega();

            if ($destino === null) {
                $this->error('No hay un correo verificado de esa persona al cual mandar. Indica uno con --a=correo@ejemplo.com, después de comprobar quién pide los datos.');

                return self::FAILURE;
            }

            if (filter_var($destino, FILTER_VALIDATE_EMAIL) === false) {
                $this->error("«{$destino}» no es un correo válido.");

                return self::FAILURE;
            }
        }

        $clave = $this->option('clave') !== null && $this->option('clave') !== '' ? (string) $this->option('clave') : null;

        $carpeta = storage_path('app/entregas');
        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0700, true);
        }

        // El nombre no se puede adivinar: dentro hay datos de una persona.
        $ruta = $carpeta.DIRECTORY_SEPARATOR.'entrega-'.Str::random(32).'.zip';

        try {
            $archivo->zip($persona, $ruta, $clave);
        } catch (Throwable $e) {
            @unlink($ruta);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($sinEnviar) {
            $this->warn("ZIP en {$ruta}. Tiene datos personales: bórralo cuando termines.");

            return self::SUCCESS;
        }

        try {
            Mail::to($destino)->send(new EntregaDeDatos($ruta, conClave: $clave !== null));
        } catch (Throwable $e) {
            $this->error('No se pudo mandar el correo: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($ruta);
        }

        // Quién y cuándo, para poder demostrar que se atendió; sin los datos mismos.
        Log::info('datos:entregar. Se entregaron los datos de una persona.', [
            'identificador' => substr(hash('sha256', $persona->tipo.':'.$persona->valor), 0, 12),
            'renglones' => $persona->total(),
            'cifrado' => $clave !== null,
        ]);

        $this->info("Se mandó a {$destino}.");

        if ($clave !== null) {
            $this->warn('El ZIP lleva clave y el correo no la trae: dásela a la persona por otro medio.');
        }

        return self::SUCCESS;
    }
}
