<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de un solo renglón: el interruptor de la Recepción de comentarios.
     * Sin renglón, la lectura del modelo responde "abierta" — nace abierta.
     */
    public function up(): void
    {
        Schema::create('recepcion_de_comentarios', function (Blueprint $table) {
            $table->id();
            $table->boolean('abierta')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepcion_de_comentarios');
    }
};
