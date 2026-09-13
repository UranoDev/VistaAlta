<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Los posts de Convivencia: lo que la Mesa Directiva publica sobre cómo se
     * vive el fraccionamiento. Cuatro columnas y ninguna más.
     *
     * El `slug` es único porque es la dirección pública del post
     * (`/convivencia/manejo-de-la-basura`), y dos posts con la misma dirección
     * dejarían que la ruta sirviera cualquiera de los dos. Lo que **no** es es
     * inmutable: se edita libremente después de publicado, con el costo asumido
     * de que un enlace ya compartido quede en 404 (URVA-96). Es la única
     * columna con restricción, y es de integridad, no de política editorial.
     *
     * No hay `estado` ni `borrador`: lo que existe en esta tabla está
     * publicado, igual que en `actividades`. Un borrador que solo puede ver
     * quien ya entró al panel es un texto sin guardar con pasos de más.
     *
     * Las imágenes de un post **no** viven aquí: van dentro del propio
     * Markdown, subidas por el editor del panel al disco `public`
     * (`![](/storage/convivencia/...)`). Una columna de imagen aparte obligaría
     * a decidir dónde va la foto respecto del texto, que es justo lo que el
     * Markdown ya resuelve.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 160);
            $table->string('slug', 160)->unique();
            $table->text('contenido');
            $table->date('publicado_en');
            $table->timestamps();

            $table->index('publicado_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
