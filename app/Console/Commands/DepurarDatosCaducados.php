<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Comentario;
use App\Models\Otp;
use App\Models\RegistroDePropietario;
use App\Models\SolicitudDeInternet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Borra los datos personales que ya no se usan: los que llevan más de
 * `contenido.legal.conservacion_anos` años (dos) sin actualizarse.
 *
 * Es lo que el Aviso de Privacidad promete en su sección 6, así que el plazo sale
 * de la misma llave que el texto: si cambia uno, cambia el otro.
 *
 * «Usar» es **actualizar**: la fecha que cuenta es `updated_at`. Un registro de
 * propietario que se verifica, se valida o se corrige renueva su plazo; una
 * solicitud de internet o un comentario que nadie vuelve a tocar caduca a los dos
 * años de su última marca. Cuando el sistema de pagos empiece a leer estos datos,
 * tendrá que tocar `updated_at` al usarlos, o el plazo seguirá corriendo mientras
 * se usan.
 *
 * Corre solo, todos los días (`routes/console.php`), y no pide confirmación:
 * eso es lo que significa «se elimina automáticamente». `--simular` dice qué
 * borraría sin borrar nada, y es lo que conviene correr antes de cambiar el plazo.
 */
#[Signature('datos:depurar {--simular : Cuenta lo que se borraría, sin borrar nada}')]
#[Description('Borra los datos personales que llevan más de dos años sin actualizarse')]
class DepurarDatosCaducados extends Command
{
    /**
     * Cada tabla con datos personales que el sitio guarda. Una tabla nueva con
     * datos de personas se agrega aquí, o el Aviso estaría prometiendo borrar algo
     * que nadie borra.
     *
     * @var list<class-string<Model>>
     */
    public const MODELOS = [
        RegistroDePropietario::class,
        SolicitudDeInternet::class,
        Comentario::class,
        Otp::class,
    ];

    public function handle(): int
    {
        $anos = (int) config('contenido.legal.conservacion_anos');

        if ($anos < 1) {
            $this->error('contenido.legal.conservacion_anos debe ser de al menos 1 año; no se borró nada.');

            return self::FAILURE;
        }

        $limite = now()->subYears($anos);
        $simular = (bool) $this->option('simular');
        $total = 0;

        foreach (self::MODELOS as $modelo) {
            /** @var Builder<Model> $caducados */
            $caducados = $modelo::query()->where('updated_at', '<', $limite);

            $cuantos = (clone $caducados)->count();
            $total += $cuantos;

            if (! $simular && $cuantos > 0) {
                // Uno por uno: un registro de propietario arrastra sus lotes y sus
                // contactos, y que se vayan es cosa de la base y de los modelos, no
                // de un borrado masivo que se salte lo que haya que limpiar.
                $caducados->each(fn (Model $caduco) => $caduco->delete());
            }

            $this->line(sprintf('%s: %d', class_basename($modelo), $cuantos));
        }

        $resumen = sprintf(
            '%s %d registro(s) sin actualizarse desde antes del %s.',
            $simular ? 'Se borrarían' : 'Se borraron',
            $total,
            $limite->format('d/m/Y'),
        );

        $this->info($resumen);

        if (! $simular && $total > 0) {
            Log::info('datos:depurar. '.$resumen);
        }

        return self::SUCCESS;
    }
}
