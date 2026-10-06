<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_de_internet', function (Blueprint $table) {
            $table->id();
            // El consecutivo del folio (INT-001, INT-002…). Es una columna y no el
            // `id` para que borrar una solicitud no deje un hueco que cambie el
            // folio de nadie, y para que el siguiente se calcule en la misma
            // transacción que lo guarda.
            $table->unsignedInteger('numero')->unique();
            $table->string('calle');
            $table->string('numero_oficial', 20);
            $table->string('manzana', 20);
            $table->string('lote', 20);
            $table->string('celular', 10);
            $table->timestamps();

            // El domicilio es único: una propiedad entra una sola vez a la lista.
            // El celular no lo es a propósito, porque una persona puede tener
            // varias propiedades. Los tres datos se guardan normalizados
            // (mayúsculas, sin espacios), así que «12a» y «12A» chocan.
            $table->unique(['calle', 'numero_oficial', 'manzana', 'lote'], 'solicitudes_de_internet_domicilio_unico');
            $table->index('celular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_de_internet');
    }
};
