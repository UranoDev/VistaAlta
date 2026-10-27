<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MES = '2026-09-01';

    private const PDF_DEL_DESGLOSE = 'https://drive.google.com/file/d/1wX2BcyK0wMjKfkVCGWKMnvwYc1FantO6/view?usp=sharing';

    /**
     * Run the migrations.
     *
     * Septiembre de 2026 vuelve a enlazar la hoja de cálculo como desglose, ya
     * con la celda del Remanente corregida (K119 `=K90-K117`, $16,257), y suma
     * el PDF del resumen («Resumen Reporte 2026 Septiembre.pdf»). El PDF del
     * desglose («Reporte 2026 Septiembre.pdf») se deja de enlazar: trae lo mismo
     * que la hoja.
     */
    public function up(): void
    {
        // Igual que la migración que publica septiembre: las pruebas no lo llevan.
        if (app()->runningUnitTests()) {
            return;
        }

        DB::table('reporte_financiero')->where('mes', self::MES)->update([
            'hoja_url' => 'https://docs.google.com/spreadsheets/d/1a2Em0njkGtBf91I9pX3-nwrgA8fAvL7JMVQKVuI8YSs/edit?usp=sharing',
            'pdf_url' => null,
            'resumen_pdf_url' => 'https://drive.google.com/file/d/1XU7-FmhKdGmyiOGF_FFtONkFPiY2nmge/view?usp=sharing',
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * Deja septiembre como lo publicó la primera migración: solo el PDF del
     * desglose.
     */
    public function down(): void
    {
        DB::table('reporte_financiero')->where('mes', self::MES)->update([
            'hoja_url' => null,
            'pdf_url' => self::PDF_DEL_DESGLOSE,
            'resumen_pdf_url' => null,
        ]);
    }
};
