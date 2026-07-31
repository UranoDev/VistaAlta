<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de un solo renglón, como `recepcion_de_comentarios`: la Vía de
     * recepción y el número de WhatsApp al que apunta.
     *
     * El número va aquí y no en `.env` a propósito: cambiar el celular de la
     * Mesa Directiva no debe requerir un despliegue. Nace nulo y el modelo
     * responde con el número de fábrica, así que el sitio funciona sin sembrar
     * nada.
     */
    public function up(): void
    {
        Schema::create('via_de_recepcion', function (Blueprint $table) {
            $table->id();
            $table->string('via')->default('whatsapp');
            $table->string('numero_whatsapp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('via_de_recepcion');
    }
};
