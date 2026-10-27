<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MES = '2026-09-01';

    private const RESUMEN_ANTERIOR = 'https://drive.google.com/file/d/1XU7-FmhKdGmyiOGF_FFtONkFPiY2nmge/view?usp=sharing';

    /**
     * Run the migrations.
     *
     * Cambia el PDF del resumen de septiembre de 2026 por uno con la página en
     * horizontal. Las cifras son las mismas.
     */
    public function up(): void
    {
        // Igual que la migración que publica septiembre: las pruebas no lo llevan.
        if (app()->runningUnitTests()) {
            return;
        }

        DB::table('reporte_financiero')->where('mes', self::MES)->update([
            'resumen_pdf_url' => 'https://drive.google.com/file/d/1BE78y3bTvJBfQS3RM0Ooak5JRjP8iugz/view?usp=sharing',
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('reporte_financiero')->where('mes', self::MES)->update(['resumen_pdf_url' => self::RESUMEN_ANTERIOR]);
    }
};
