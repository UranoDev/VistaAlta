<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Le da al Reporte financiero el mes que cubre, y con eso la tabla deja de
     * ser de un solo renglón: cada mes que se rinde se conserva en vez de
     * sobreescribir al anterior. Ver `docs/adr/0005`.
     *
     * El mes va como fecha normalizada al día 1 y no como texto, porque de él
     * salen tres cosas que no pueden divergir: el orden del histórico, la URL
     * de cada reporte (`/reporte-financiero/2026-06`) y el título que se lee en
     * la página. El índice único es el que impide que existan dos reportes del
     * mismo mes, que es la única forma de que la Asamblea no sepa cuál vale.
     *
     * `periodo` se va: era texto libre y decía lo mismo que el mes, con la
     * posibilidad de contradecirlo. Ahora se deriva —ver
     * `App\Models\ReporteFinanciero::periodo`— así que la etiqueta legible sigue
     * existiendo, pero ya no se captura.
     */
    public function up(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->date('mes')->nullable()->after('id');
        });

        // La tabla era de un solo renglón por construcción, así que aquí hay
        // cuando mucho uno que rellenar: el de junio de 2026, el único Periodo
        // capturado hasta ahora. Se le pone su mes antes de que la columna se
        // vuelva obligatoria.
        DB::table('reporte_financiero')->whereNull('mes')->update(['mes' => '2026-06-01']);

        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->dropColumn('periodo');
        });

        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->date('mes')->nullable(false)->change();
        });

        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->unique('mes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Volver atrás deja la tabla con la forma vieja, pero no puede deshacer el
     * histórico: si hay varios meses capturados, al regresar quedan varios
     * renglones en una tabla que el código anterior lee como de uno solo —y se
     * queda con el primero—. Bajar de versión con más de un reporte publicado
     * es perder los demás de vista, no recuperarlos.
     */
    public function down(): void
    {
        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->dropUnique(['mes']);
        });

        Schema::table('reporte_financiero', function (Blueprint $table) {
            $table->dropColumn('mes');
            $table->string('periodo')->nullable()->after('id');
        });
    }
};
