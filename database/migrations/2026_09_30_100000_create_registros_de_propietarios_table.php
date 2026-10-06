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
        Schema::create('registros_de_propietarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // Con uno de los dos basta; la regla vive en la validación del
            // formulario, no en la base, porque un registro viejo o corregido a
            // mano en el panel no debería romperse por ella.
            $table->string('telefono', 10)->nullable();
            $table->string('correo')->nullable();
            $table->string('emergencia_nombre')->nullable();
            $table->string('emergencia_telefono', 10)->nullable();
            $table->unsignedSmallInteger('residentes')->nullable();
            // Cuándo aceptó y qué versión del Aviso de Privacidad tenía enfrente.
            $table->timestamp('aceptado_en');
            $table->string('aviso_version');

            // La confirmación: un código de seis dígitos (por correo y por
            // WhatsApp) y un enlace (por correo). Se guardan solo sus hashes; lo
            // que sirve para confirmar viaja una vez y no queda en la base.
            $table->string('confirmacion_codigo_hash')->nullable();
            $table->string('confirmacion_enlace_hash', 64)->nullable();
            $table->timestamp('confirmacion_expira_en')->nullable();
            $table->unsignedTinyInteger('confirmacion_intentos')->default(0);
            $table->timestamp('confirmacion_enviada_en')->nullable();
            $table->timestamp('confirmado_en')->nullable()->index();
            $table->string('confirmado_por', 20)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_de_propietarios');
    }
};
