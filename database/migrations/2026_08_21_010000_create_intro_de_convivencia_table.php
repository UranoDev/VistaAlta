<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de un solo renglón, como `recepcion_de_comentarios` y
     * `via_de_recepcion`: el texto que encabeza el índice de `/convivencia` y
     * le dice al Colono para qué es la sección.
     *
     * Va en la base y no en `config/contenido.php` —donde vive el resto del
     * copy del sitio— por la razón que ese archivo mismo declara: ahí está lo
     * que **no tiene pantalla en el panel**. Éste sí la tiene, en el encabezado
     * de la pantalla de Convivencia, porque la Mesa Directiva lo entrega
     * después de que la sección ya esté en el aire y no debería necesitar un
     * despliegue para escribirlo.
     *
     * Nace nulo a propósito: mientras nadie lo capture, el índice se dibuja sin
     * introducción en vez de con un texto de relleno. Publicarle a la Asamblea
     * un párrafo que nadie escribió es peor que no publicar ninguno.
     */
    public function up(): void
    {
        Schema::create('intro_de_convivencia', function (Blueprint $table) {
            $table->id();
            $table->text('texto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intro_de_convivencia');
    }
};
