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

];
