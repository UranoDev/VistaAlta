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
        Schema::create('contactos_del_registro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_de_propietario_id')->constrained('registros_de_propietarios')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('telefono', 10)->nullable();
            $table->string('correo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactos_del_registro');
    }
};
