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
            // Con uno de los dos basta (la regla vive en la validación del
            // formulario). Cada medio que se da se verifica por separado: el correo
            // con un código y un enlace, el celular con un código por SMS.
            $table->string('telefono', 10)->nullable();
            $table->string('correo')->nullable();
            $table->string('emergencia_nombre')->nullable();
            $table->string('emergencia_telefono', 10)->nullable();
            $table->unsignedSmallInteger('residentes')->nullable();
            // Cuándo aceptó y qué versión del Aviso de Privacidad tenía enfrente.
            $table->timestamp('aceptado_en');
            $table->string('aviso_version');

            // Verificación del correo. Solo se guardan los hashes del código y del
            // enlace; lo que sirve para verificar viaja una vez y no queda aquí.
            $table->string('correo_codigo_hash')->nullable();
            $table->string('correo_enlace_hash', 64)->nullable();
            $table->timestamp('correo_expira_en')->nullable();
            $table->unsignedTinyInteger('correo_intentos')->default(0);
            $table->timestamp('correo_enviado_en')->nullable();
            $table->timestamp('correo_verificado_en')->nullable()->index();
            // Con qué se verificó: el enlace del correo o el código escrito.
            $table->string('correo_verificado_por', 20)->nullable();

            // Verificación del celular, con un código por SMS.
            $table->string('telefono_codigo_hash')->nullable();
            $table->timestamp('telefono_expira_en')->nullable();
            $table->unsignedTinyInteger('telefono_intentos')->default(0);
            $table->timestamp('telefono_enviado_en')->nullable();
            $table->timestamp('telefono_verificado_en')->nullable()->index();

            // La validación de la Administración: otra cosa que las dos de arriba.
            // Verificar el correo y el celular prueba que la persona los controla;
            // validar es que la Administración revisó que el registro es cierto.
            $table->timestamp('validado_en')->nullable()->index();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('validacion_nota')->nullable();

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
