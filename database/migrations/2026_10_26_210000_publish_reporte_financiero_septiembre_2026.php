<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MES = '2026-09-01';

    /**
     * Run the migrations.
     *
     * Publica el Reporte financiero de septiembre de 2026. Va en una migración
     * y no en `contenido-inicial.php` porque contra producción el seeder ya no
     * se corre (ver `docs/deploy-plesk.md`), y el despliegue sí corre
     * `migrate --force`.
     *
     * Se reconoce por su mes, como en el seeder: si alguien ya capturó
     * septiembre en el panel, esto lo corrige en vez de duplicarlo.
     *
     * Este mes se comparte solo en PDF, sin la hoja de cálculo: `hoja_url` va
     * vacío y `pdf_url` apunta al reporte completo (los pagos lote por lote y
     * el resumen), así que la página dice que la que vale es el PDF.
     *
     * De dónde sale cada renglón de ingresos (columna Septiembre, $68,040):
     * 81 lotes pagaron la cuota de septiembre ($65,000: 80 de $800 y el
     * renglón 27, que pagó $1,000 y se cuenta completo como septiembre; la nota
     * «pago en agosto» del renglón 12 es que en agosto adelantó octubre, así
     * que su cuota de septiembre sí entró este mes); ocho
     * pagaron $880, y los $80 de cada uno van en Recargos ($640); el renglón
     * 26 pagó $3,200, de los que $2,400 cubren octubre a diciembre y se suman a las columnas
     * Octubre–Diciembre ($8,800) como Cuotas adelantadas.
     */
    public function up(): void
    {
        $cifras = [
            ['concepto' => 'Cuotas de septiembre', 'monto' => 65000, 'destacada' => false],
            ['concepto' => 'Recargos', 'monto' => 640, 'destacada' => false],
            ['concepto' => 'Subtotal cuotas de septiembre', 'monto' => 65640, 'destacada' => false],
            ['concepto' => 'Cuotas adelantadas', 'monto' => 11200, 'destacada' => false],
            ['concepto' => 'Controles y llaves', 'monto' => 9090, 'destacada' => false],
            ['concepto' => 'Total ingresos', 'monto' => 85930, 'destacada' => true],
            ['concepto' => 'Egresos recurrentes', 'monto' => -58261, 'destacada' => false],
            ['concepto' => 'Egresos única vez', 'monto' => -11412, 'destacada' => false],
            ['concepto' => 'Total egresos', 'monto' => -69673, 'destacada' => true],
            ['concepto' => 'Remanente', 'monto' => 16257, 'destacada' => true],
        ];

        $ahora = now();

        DB::table('reporte_financiero')->updateOrInsert(
            ['mes' => self::MES],
            [
                'cifras' => json_encode($cifras, JSON_UNESCAPED_UNICODE),
                'aclaracion' => 'Las Cuotas adelantadas ($11,200.00) son pagos de octubre a diciembre que se cobraron en septiembre. No son ingreso regular de este mes: sin ellas, el remanente de septiembre habría sido de $5,057.00. Entre los egresos, la Basura ($2,988.00) es un pago que cubre de septiembre a diciembre, y los egresos de única vez son la reparación de la cerca eléctrica ($3,050.00), cinco luminarias ($8,162.00) y el celular de vigilancia ($200.00).',
                'hoja_url' => null,
                'pdf_url' => 'https://drive.google.com/file/d/1wX2BcyK0wMjKfkVCGWKMnvwYc1FantO6/view?usp=sharing',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        );
    }

    /**
     * Reverse the migrations.
     *
     * Quita septiembre y deja vigente al mes anterior. Si septiembre se había
     * capturado en el panel antes de esta migración, también se va: lo que
     * tenía antes no se guardó.
     */
    public function down(): void
    {
        DB::table('reporte_financiero')->where('mes', self::MES)->delete();
    }
};
