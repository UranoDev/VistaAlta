<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El enlace a un PDF con solo el resumen del mes —ingresos, egresos y
     * remanente—, aparte del PDF del desglose (`pdf_url`), que trae además el
     * pago de cada lote. Es para quien quiere el resumen en un archivo que
     * pueda guardar o reenviar.
     *
     * Opcional y nullable por lo mismo que `pdf_url`: los meses anteriores no
     * lo tienen.
     */
    public function up(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->string('resumen_pdf_url')->nullable()->after('pdf_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->dropColumn('resumen_pdf_url');
        });
    }
};
