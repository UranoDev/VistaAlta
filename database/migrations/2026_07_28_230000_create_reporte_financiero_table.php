<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de un solo renglón, como `recepcion_de_comentarios`: el sitio
     * publica el Reporte financiero de un Periodo a la vez.
     *
     * `cifras` es JSON y no una tabla aparte a propósito. El resumen existe
     * para ser mostrado —la hoja de cálculo es la fuente de verdad— así que sus
     * renglones no son registros que se consulten, se sumen ni se relacionen
     * con nada: son el texto de un comprobante, y se editan de una sentada.
     */
    public function up(): void
    {
        Schema::create('reporte_financiero', function (Blueprint $table) {
            $table->id();
            $table->string('periodo')->nullable();
            $table->json('cifras')->nullable();
            $table->string('hoja_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_financiero');
    }
};
