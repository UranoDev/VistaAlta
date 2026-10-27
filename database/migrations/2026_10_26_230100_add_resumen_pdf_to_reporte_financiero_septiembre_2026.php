<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MES = '2026-09-01';

    /**
     * Run the migrations.
     *
     * Le agrega a septiembre de 2026 el PDF del resumen («Resumen Reporte 2026
     * Septiembre.pdf»). El desglose ya estaba en `pdf_url` («Reporte 2026
     * Septiembre.pdf»).
     */
    public function up(): void
    {
        // Igual que la migración que publica septiembre: las pruebas no lo llevan.
        if (app()->runningUnitTests()) {
            return;
        }

        DB::table('reporte_financiero')->where('mes', self::MES)->update([
            'resumen_pdf_url' => 'https://drive.google.com/file/d/1XU7-FmhKdGmyiOGF_FFtONkFPiY2nmge/view?usp=sharing',
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('reporte_financiero')->where('mes', self::MES)->update(['resumen_pdf_url' => null]);
    }
};
