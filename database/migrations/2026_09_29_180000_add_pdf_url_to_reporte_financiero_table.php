<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El enlace a un PDF con lo mismo que trae la hoja de cálculo, para quien
     * revisa el reporte desde el celular y no quiere abrir una hoja de 130
     * renglones. Es opcional y no reemplaza a `hoja_url`: el PDF es una copia
     * que alguien generó de la hoja en una fecha, y la hoja sigue siendo la
     * fuente de verdad.
     *
     * Es un enlace y no un archivo a propósito, por lo mismo que la hoja: este
     * sitio no carga documentos de nadie, y el PDF vive donde ya vive la hoja
     * (Drive), compartido por enlace. Nullable porque los meses anteriores no
     * lo tienen y no hay con qué generárselo.
     */
    public function up(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->string('pdf_url')->nullable()->after('hoja_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->dropColumn('pdf_url');
        });
    }
};
