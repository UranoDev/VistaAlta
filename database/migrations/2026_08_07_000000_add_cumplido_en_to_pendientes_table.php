<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo se cumplió un Pendiente.
 *
 * Revierte una decisión que el modelo traía escrita: «Sin marca de cumplido. El
 * pendiente que se cumple no se archiva: se convierte en Actividad y se retira…
 * a cambio de una trazabilidad que nadie ha pedido».
 *
 * Ya se pidió. «Lo que sigue» tiene que poder mostrar tachado, durante unos
 * días, el pendiente que se acaba de cumplir — para que quien vuelve a la página
 * vea que algo se cerró y no solo que una línea desapareció. Un renglón borrado
 * no se puede tachar.
 *
 * Nulo es lo normal: un pendiente vive con `cumplido_en` en nulo y solo se
 * estampa cuando se cumple. Después de la ventana de novedad deja de publicarse
 * solo, sin que nadie tenga que ir a borrarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendientes', function (Blueprint $tabla): void {
            $tabla->timestamp('cumplido_en')->nullable()->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('pendientes', function (Blueprint $tabla): void {
            $tabla->dropColumn('cumplido_en');
        });
    }
};
