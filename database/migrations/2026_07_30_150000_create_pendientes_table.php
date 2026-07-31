<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tres columnas y ninguna fecha. La ausencia de `fecha_compromiso` es
     * deliberada: varios pendientes dependen de un tercero —la notaría, la
     * Fraccionadora, los proveedores— y comprometer ante la Asamblea una fecha
     * que no se controla es prometer de más. Un campo así, una vez que existe
     * en el panel, se llena.
     *
     * El `orden` es explícito porque el primer pendiente es «Constituir la
     * Asociación Civil», del que cuelgan los demás: con un `ORDER BY id` ese
     * orden se perdería en cuanto se agregue uno nuevo.
     */
    public function up(): void
    {
        Schema::create('pendientes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 160);
            $table->text('detalle');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendientes');
    }
};
