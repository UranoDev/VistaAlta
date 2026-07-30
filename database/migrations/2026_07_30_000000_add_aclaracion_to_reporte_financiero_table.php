<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una aclaración en prosa para el Periodo completo, al lado de las cifras.
     *
     * Existe porque un resumen de números no siempre se explica solo: un mes
     * puede traer un ingreso extraordinario que infla el remanente, y quien lo
     * lea sin contexto concluye que ese excedente es lo normal. La aclaración es
     * el lugar donde la Mesa Directiva dice eso con sus palabras.
     *
     * Va aquí y no en cada `Cifra` a propósito: lo que hay que aclarar casi
     * siempre es la relación entre varios renglones —«sin este ingreso el
     * remanente habría sido otro»—, no un renglón aislado.
     */
    public function up(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->text('aclaracion')->nullable()->after('cifras');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->dropColumn('aclaracion');
        });
    }
};
