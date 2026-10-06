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
        Schema::create('lotes_registrados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_de_propietario_id')->constrained('registros_de_propietarios')->cascadeOnDelete();
            $table->string('calle');
            $table->string('numero_oficial', 20);
            $table->string('manzana', 20);
            $table->string('lote', 20);
            $table->string('situacion');
            $table->timestamps();

            // Sin único: dos personas pueden declarar el mismo lote (copropiedad,
            // un error de captura), y eso se resuelve viéndolo, no rechazando a
            // quien llegó segundo.
            $table->index(['calle', 'numero_oficial']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_registrados');
    }
};
