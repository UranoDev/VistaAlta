<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Contenido del sitio
|--------------------------------------------------------------------------
|
| Texto que se cambia sin tocar una vista. Vive en config y no en la base
| porque no tiene pantalla en el panel: son cinco respuestas que se escriben
| una vez para la Asamblea del periodo, no un catálogo que crezca.
|
| Lo que sí se captura desde el panel —Actividades y Reporte financiero— vive
| en la base y no aquí. El contenido con el que el sitio sale al aire se pega
| en `database/seeders/contenido/contenido-inicial.php`.
|
*/

return [

    /*
     * El buzón institucional: el que citan las páginas legales como contacto
     * para el ejercicio de derechos ARCO. Estable a propósito — una dirección
     * publicada en un aviso de privacidad es un compromiso, no un dato que se
     * rota.
     */
    'correo_contacto' => 'admon@vistaaltatx.com',

    /*
     * El buzón al que llegan los comprobantes de depósito (/demanda). Va
     * aparte del institucional porque es el que queda expuesto al scraping en
     * la página que más se difunde: si se llena de spam se apaga el alias, se
     * crea otro y el sitio cambia en esta línea sola, sin tocar lo que
     * prometen las páginas legales.
     */
    'correo_comprobantes' => 'comprobantes@vistaaltatx.com',

    /*
     * Cuántos propietarios han mandado su comprobante de depósito a la
     * administración pasada. Es el dato de la página /demanda y el que le da
     * su peso: va aquí y no dentro del blade porque va a cambiar conforme
     * lleguen más, y una página que se quedó en el número viejo miente.
     *
     * Se cuenta a mano. Que salga solo pediría un modelo para los
     * comprobantes, y el sitio no recibe archivos: los comprobantes llegan por
     * correo.
     */
    'comprobantes_recibidos' => 4,

    /*
     * El mensaje con el que sale prellenado el enlace de WhatsApp de la página
     * de la Propuesta, cuando la Vía de recepción está en `whatsapp`.
     *
     * No es cortesía ni plantilla decorativa. En esa vía el Comentario llega a
     * un chat y lo captura la Mesa Directiva, así que la visibilidad —que es
     * definitiva y que en el sitio elige el propio autor— pasaría a decidirla
     * quien lo captura. Pedirla por escrito aquí es lo que la mantiene siendo
     * del autor. Si se reescribe este texto, esa línea se queda.
     *
     * El número al que apunta el enlace no vive aquí: vive en la base
     * (`App\Models\ViaDeRecepcion`), para que cambiarlo no pida un despliegue.
     * El texto sí, porque es copy y no tiene pantalla en el panel — el mismo
     * criterio que las preguntas frecuentes.
     */
    'whatsapp_mensaje' => <<<'TEXTO'
        Hola, quiero dejar un comentario sobre la Propuesta de Vista Alta.

        Mi nombre:

        Mi comentario:

        Quiero que mi comentario sea (deja solo una de las dos líneas):
        - PÚBLICO: que aparezca en la página con mi nombre.
        - PRIVADO: solo para la Mesa Directiva, que no se publique.
        TEXTO,

    /*
     * Las dos páginas legales. Solo la fecha: el correo que citan es
     * `correo_contacto`, el institucional, y escribirlo otra vez aquí sería
     * repetir el problema que esa llave resuelve.
     *
     * `actualizado_en` se pone a mano y se cambia cuando el texto cambie de
     * verdad, no en cada deploy. Las páginas no llevan franja de borrador —la
     * Mesa Directiva asume el texto como vigente— así que esta fecha es una
     * afirmación, no un dato de bitácora.
     */
    'legal' => [
        'actualizado_en' => '28 de julio de 2026',
    ],

    /*
     * Cuántos días conserva la marca de novedad lo que se publica en
     * `/actividades` — una entrada de la Bitácora, un Pendiente recién
     * capturado o uno que se acaba de cumplir.
     *
     * La página se lee de corrido y crece por arriba, así que quien vuelve cada
     * tantas semanas no tiene forma de saber qué apareció desde la última vez.
     * La marca es para eso, y por eso caduca sola: una marca que hay que ir a
     * quitar a mano se queda puesta para siempre, y una página donde todo está
     * marcado como nuevo no marca nada.
     *
     * Se mide contra `created_at`, que es cuándo se **capturó**, no contra la
     * fecha de la Actividad. Son cosas distintas a propósito: algo que pasó en
     * junio y se captura hoy es novedad para el lector aunque su fecha sea
     * vieja.
     *
     * El mismo número decide cuánto tiempo sigue publicado —tachado— un
     * pendiente ya cumplido, y por eso es uno solo: son la misma pregunta
     * —cuánto dura la novedad— y con dos valores la lista y su marca se
     * apagarían en momentos distintos.
     *
     * En `0` se apaga entera, sin tocar ninguna vista.
     */
    'novedades' => [
        'dias' => 7,
    ],

    /*
     * Las cuatro personas que hacen la vigilancia, y el rol con el que la
     * página calcula quién está de guardia (URVA-79).
     *
     * ## Lo que la página publica y lo que no
     *
     * Los horarios están aquí porque sin ellos no se puede saber quién está en
     * el acceso, pero **la página no los imprime en ningún lado**. Lo que se lee
     * en cada tarjeta es `etiqueta`, un rótulo escrito a mano. Publicar el
     * horario —o la hora del relevo— equivale a publicar el rol completo: quien
     * consulte cuatro veces lo reconstruye, y son cuatro personas cubriendo un
     * acceso. Hay una prueba que revisa que las horas no se cuelen al HTML.
     *
     * Por lo mismo `nombre` lleva nombre de pila e inicial y nunca el apellido
     * completo: lo que le sirve al colono para reconocer a quien está en el
     * acceso es la cara, y el apellido solo vuelve buscable a alguien que no
     * pidió serlo.
     *
     * ## Cómo se llena
     *
     * - `nombre`   — nombre de pila e inicial: «Ernesto S.».
     * - `etiqueta` — el rótulo del turno tal como se lee en la tarjeta.
     * - `foto`     — archivo dentro de `public/img/vigilantes/`. Con `null` la
     *                tarjeta se dibuja con las iniciales, y es una opción
     *                legítima y definitiva: quien no quiera que se publique su
     *                cara no tiene por qué.
     * - `desde`    — `AAAA-MM-DD`, opcional. Solo la fecha de incorporación: la
     *                situación laboral de una persona identificada no se publica
     *                en el sitio de su lugar de trabajo.
     * - `turnos`   — `dias` son días de **entrada** en ISO-8601 (1 lunes … 7
     *                domingo), no de salida. `sale` menor o igual que `entra`
     *                cruza la medianoche; iguales son 24 horas corridas.
     *
     * Los cuatro turnos de abajo cubren la semana completa sin hueco y sin
     * traslape. Las costuras que hay que respetar al editarlos son sábado 22:00
     * → domingo 06:00 y domingo 06:00 → lunes 06:00.
     */
    'vigilancia' => [

        /*
         * La aplicación corre en UTC (`config/app.php`), así que la hora del
         * acceso hay que decirla. No es el reloj del visitante: el turno es un
         * hecho del fraccionamiento, no del aparato de quien mira la página.
         */
        'zona_horaria' => 'America/Mexico_City',

        'vigilantes' => [

            [
                'nombre' => 'Maribel',
                'etiqueta' => 'Turno de mañana',
                'foto' => 'maribel.jpeg',
                'desde' => null,
                'turnos' => [
                    ['dias' => [1, 2, 3, 4, 5, 6], 'entra' => '06:00', 'sale' => '14:00'],
                ],
            ],

            [
                'nombre' => 'Gabo',
                'etiqueta' => 'Turno de tarde',
                'foto' => 'gabo.jpeg',
                'desde' => null,
                'turnos' => [
                    ['dias' => [1, 2, 3, 4, 5, 6], 'entra' => '14:00', 'sale' => '22:00'],
                ],
            ],

            [
                'nombre' => 'Hugo',
                'etiqueta' => 'Turno de noche',
                'foto' => 'hugo.jpeg',
                'desde' => null,
                // Entra de lunes a sábado y sale al día siguiente. El domingo a
                // la 01:00 quien está es el que entró el sábado.
                'turnos' => [
                    ['dias' => [1, 2, 3, 4, 5, 6], 'entra' => '22:00', 'sale' => '06:00'],
                ],
            ],

            [
                'nombre' => 'Eduardo',
                'etiqueta' => 'Turno de domingo',
                'foto' => 'eduardo.jpeg',
                'desde' => '2026-08-02',
                // 24 horas corridas: entra el domingo a las 06:00 y entrega el
                // lunes a la misma hora. `sale` igual que `entra` es eso.
                'turnos' => [
                    ['dias' => [7], 'entra' => '06:00', 'sale' => '06:00'],
                ],
            ],

        ],
    ],

    /*
     * Las preguntas frecuentes de la página de la Propuesta, en el orden en
     * que se leen: primero qué es y por qué, y al final las dos que la gente
     * pregunta de verdad —a quién le toca y cuánto cuesta—.
     *
     * Portadas de nvavista (docs/adr/0003) y reescritas para Vista Alta: allá
     * las contestaba un proveedor de software, aquí las contesta la Mesa
     * Directiva hablando con sus vecinos.
     *
     * La tercera cambió de redacción a propósito. En nvavista preguntaba
     * «¿Quiénes pueden ser socios?»; aquí un colono no es un socio de nada
     * —no hay membresía que contratar— así que la pregunta es quiénes
     * formarían parte, y la respuesta aclara que «asociado» es el término de
     * la ley, no un cargo nuevo.
     */
    'preguntas_frecuentes' => [

        '¿Qué es la Asociación Civil que se propone?' => 'Una figura legal sin fines de lucro que le da al fraccionamiento existencia jurídica propia. Hoy Vista Alta no existe ante la ley: existen las casas y existimos los colonos, pero el fraccionamiento como tal no puede abrir una cuenta ni firmar nada. Constituir la Asociación Civil es darle ese nombre propio, para que lo que es de todos —el acceso, las áreas comunes, la vigilancia— tenga a nombre de quién estar.',

        '¿Por qué hacerlo ahora?' => 'Porque las tres cosas que hoy sostiene la palabra de quien esté en la Mesa Directiva pasarían a sostenerse solas: la cuenta del fraccionamiento a nombre del fraccionamiento y no de un colono; los acuerdos con proveedores firmados por la Asociación; y la cuota exigible por la vía legal a quien decide no pagarla. Mientras la figura no exista, cada una de las tres depende de un favor personal y de que la persona que lo hace siga ahí.',

        '¿Quiénes formarían parte?' => 'Los propietarios de un lote o terreno dentro del Fraccionamiento Vista Alta. La ley los llama «asociados»; en los hechos son los mismos colonos que hoy asisten a las Asambleas. Nadie queda fuera por no haber ido a una reunión, y formar parte no obliga a ocupar ningún cargo.',

        '¿Cómo se constituye?' => 'Se redactan los estatutos —las reglas con las que la Asociación se gobierna—, se protocolizan ante notario público y se inscriben en el Registro Público de la Propiedad; después se tramita el RFC ante el SAT. Los estatutos se presentan a la Asamblea antes de ir con el notario: la propuesta es que no se firme nada que la Asamblea no haya leído.',

        '¿Tiene algún costo para los colonos?' => 'No hay una cuota nueva ni una aportación extra. Los gastos de notario y de registro salen del fondo del fraccionamiento, el mismo que se rinde en el Reporte financiero, y ahí van a aparecer en cuanto se ejerzan.',

    ],

    /*
     * La página de Administración (`/administracion`): el trámite con el que se
     * está registrando la asociación civil, y las siete personas que hoy
     * ocupan un cargo en los dos órganos.
     *
     * ## Por qué vive aquí y no en la base
     *
     * Por lo mismo que Vigilancia: son siete personas y cuatro pasos, no un
     * catálogo que crezca, y no hay pantalla en el panel que los mantenga. El
     * día que la Asamblea renueve cargos se edita este arreglo. Si algún día
     * hay que llevar historial de quién ocupó qué y cuándo, esto se muda a la
     * base y las clases de `App\Support\Administracion` se quedan igual.
     *
     * ## Los nombres van completos, y eso es distinto de Vigilancia
     *
     * En `vigilancia` el nombre lleva nombre de pila e inicial a propósito: son
     * trabajadores y el apellido solo los vuelve buscables. Aquí van completos
     * porque son cargos electos ante la Asamblea y el acta constitutiva los va
     * a asentar así. **Las siete personas tienen que saber que su nombre y su
     * foto se publican**, y quien no quiera su foto no la pone: la tarjeta se
     * dibuja con el monograma y se ve igual de completa.
     *
     * ## Cómo se llena
     *
     * - `razon_social` — el nombre autorizado, tal como lo dio el trámite.
     * - `tramite`      — los pasos en orden. `estado` es `listo`, `sigue` o
     *                    `falta`; `sigue` marca el que está en curso y no
     *                    debería haber más de uno.
     * - `comite`       — los integrantes del Comité de Vigilancia. Son pares:
     *                    no hay coordinador ni nadie al frente, y por eso el
     *                    arreglo no tiene `cabeza`.
     * - `cabeza`       — quien encabeza la Administración. Va aparte porque en
     *                    el organigrama cuelga un nivel arriba de los demás.
     * - `integrantes`  — el resto de la Administración, en el orden en que se
     *                    dibujan de izquierda a derecha.
     * - `foto`         — archivo dentro de `public/img/administracion/`, o
     *                    `null` para el monograma. Las fotos **no se versionan**
     *                    (ver `.gitignore`): este repositorio es público y el
     *                    historial de git no se depura, así que si alguien
     *                    retira su consentimiento borrar el archivo del
     *                    servidor tiene que bastar.
     * - `hace`         — una línea de qué hace ese cargo, que se lee abajo del
     *                    organigrama. El Comité no la lleva: los tres hacen lo
     *                    mismo y el párrafo de la sección ya lo dice.
     */
    'administracion' => [

        'razon_social' => 'Fraccionamiento Vista Alta',

        /*
         * El trámite de constitución, en orden. La página lo publica tal cual,
         * así que mientras el paso 2 diga `sigue` el sitio está afirmando que
         * la firma todavía no ocurre: al firmarse hay que mover el estado aquí,
         * o la página empieza a mentir.
         *
         * El detalle del paso 2 nombra un mes. Es deliberado —es lo que se le
         * dijo a la Asamblea— y es lo primero que se echa a perder: cuando pase
         * septiembre, este renglón se cambia.
         */
        'tramite' => [

            [
                'titulo' => 'Autorización del nombre',
                'detalle' => 'Nos lo dieron como Fraccionamiento Vista Alta.',
                'estado' => 'listo',
            ],

            [
                'titulo' => 'Firma del acta constitutiva',
                'detalle' => 'Ante notario. Esperamos que sea este mes de septiembre.',
                'estado' => 'sigue',
            ],

            [
                'titulo' => 'RFC de la asociación',
                'detalle' => 'Se saca una vez firmada el acta.',
                'estado' => 'falta',
            ],

            [
                'titulo' => 'Cuenta bancaria del fraccionamiento',
                'detalle' => 'Lo último, ya con el RFC.',
                'estado' => 'falta',
            ],

        ],

        /*
         * Los tres del Comité todavía no mandan foto, así que salen con su
         * monograma. No es un pendiente de maquetado: la tarjeta está hecha
         * para verse igual de completa con foto o sin ella, y quien decida no
         * publicar la suya se queda así.
         */
        'comite' => [

            ['nombre' => 'Claudia Arriola', 'cargo' => 'Integrante', 'hace' => null, 'foto' => null],
            ['nombre' => 'Fernando López', 'cargo' => 'Integrante', 'hace' => null, 'foto' => null],
            ['nombre' => 'Rafael Rojas', 'cargo' => 'Integrante', 'hace' => null, 'foto' => null],

        ],

        'cabeza' => [
            'nombre' => 'Lourdes Ríos',
            'cargo' => 'Administradora',
            'hace' => 'Coordina la operación del fraccionamiento y encabeza a la Administración.',
            'foto' => 'lourdes.jpeg',
        ],

        'integrantes' => [

            [
                'nombre' => 'Hilda Contreras',
                'cargo' => 'Secretaria',
                'hace' => 'Levanta las actas de la Asamblea y resguarda los papeles.',
                'foto' => 'hilda.jpeg',
            ],

            [
                'nombre' => 'Gaby Viorney',
                'cargo' => 'Vocal',
                'hace' => 'Echa la mano en lo que haga falta y suple a quien no pueda.',
                'foto' => 'gaby.jpeg',
            ],

            [
                'nombre' => 'Urano González',
                'cargo' => 'Tesorero',
                'hace' => 'Lleva las cuentas y publica el reporte de cada mes.',
                'foto' => 'urano.jpeg',
            ],

        ],

    ],

];
