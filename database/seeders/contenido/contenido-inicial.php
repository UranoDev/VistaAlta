<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Contenido inicial del sitio
|--------------------------------------------------------------------------
|
| El material con el que el sitio sale al aire. Lo manda la Mesa Directiva y
| aquí se pega: en la ventana que hay antes de la Asamblea, el panel no
| alcanza a llenarse renglón por renglón.
|
| Se siembra con:
|
|     php artisan db:seed --class=ContenidoInicialSeeder
|
| Correrlo dos veces deja el sitio igual: las Actividades se reconocen por su
| fecha y su texto, los Pendientes por su título, y el Reporte financiero por el
| mes que cubre. Lo que se haya editado desde el panel sí se pisa — mientras
| este archivo tenga contenido, este archivo es la versión buena.
|
| Todo nace vacío a propósito. Una Actividad o una cifra de relleno en un
| sitio de rendición de cuentas no es un marcador de posición: es una mentira
| ante la Asamblea. Mientras una lista siga vacía el seeder la salta y la
| página pública dice que ese contenido todavía no se publica, que es la
| verdad.
|
| El video no se siembra desde aquí: su URL vive en la variable de entorno
| ASOCIACION_CIVIL_VIDEO_URL, para que cambiarla no requiera tocar la base.
|
*/

return [

    /*
     * Las Actividades del Periodo, tal como la Mesa Directiva las redactó. El
     * orden aquí da igual: la página las acomoda por fecha, de la más reciente
     * a la más vieja.
     *
     * No lleva costo ni documento adjunto, y no es que falte capturarlo — la
     * Actividad no tiene esos campos (ver App\Models\Actividad). El dinero se
     * rinde únicamente en el Reporte financiero.
     *
     * Cada renglón:
     *
     *     ['fecha' => 'AAAA-MM-DD', 'descripcion' => 'Qué se hizo, en una o dos frases.'],
     */
    'actividades' => [

        /*
         * La entrada larga de la Bitácora, y la única con varios párrafos: es el
         * cierre del pendiente «Vigilancia las 24 horas, los siete días» y trae
         * la explicación completa de por qué la vigilancia no se contrató con
         * una empresa.
         *
         * Va fechada el 2 de agosto —el domingo en que se incorporó la cuarta
         * persona y quedó cubierta la semana entera— y no el día en que se
         * publicó. La Bitácora dice cuándo pasó lo que pasó.
         *
         * El texto vive aquí y no en la página de Vigilancia a propósito: aquella
         * dice quién está en el acceso ahora, ésta explica una decisión y tiene
         * fecha. La liga interna se escribe `[texto](/ruta)` y la resuelve
         * `App\Support\Contenido\TextoConLigas`.
         */
        ['fecha' => '2026-08-02', 'descripcion' => <<<'TEXTO'
            Se cerró el turno de vigilancia que faltaba: el acceso quedó cubierto las 24 horas, los siete días de la semana, con cuatro personas.

            Antes de decidirlo se pidieron cotizaciones a empresas de seguridad privada. La propuesta más baja fue de $16,000 más IVA por elemento al mes, con un esquema de dos elementos alternándose en turnos de 24 por 24 —uno cubre de las 07:00 del lunes a las 07:00 del martes, el siguiente de las 07:00 del martes a las 07:00 del miércoles, y así sucesivamente—. En total, $32,000 más IVA al mes: $37,120.

            Para la Administración habría sido la opción más cómoda: un solo proveedor, una sola factura y ningún trato directo con el personal. La objeción no fue el costo, sino la continuidad. En ese esquema el fraccionamiento no elige quién viene: la empresa asigna y reasigna a su personal, y la rotación es alta. Cada relevo empieza de cero —las rutinas del acceso, los horarios de recolección, qué vehículos son de aquí y cuáles no— y ese aprendizaje se pierde con cada cambio.

            Tres de las cuatro personas ya llevan tiempo trabajando con nosotros: cumplen su horario, no faltan y siguen las instrucciones que se les dan. Se prefirió continuar con ellas. La cuarta se incorporó este día y cuenta con experiencia previa en empresas de seguridad; con ella se cerró el turno de domingo.

            También se aumentó el número de rondines nocturnos: el vigilante en turno recorre el fraccionamiento varias veces durante la noche, en lugar de permanecer únicamente en el acceso.

            Quiénes son y quién está de guardia en este momento se consulta en [la página de Vigilancia](/vigilancia). Lo que se paga por este servicio aparece en el Reporte financiero, en el detalle de egresos de cada mes.
            TEXTO],

        ['fecha' => '2026-07-28', 'descripcion' => 'Poda exterior, pasto y árboles'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se aplica mata hierba en todos los jardines'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Barrido periódico de calles'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Una persona pasa semanalmente a recoger material de desecho que esté sobre la banqueta. No entra a las obras por seguridad de todos.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se revisa la cajuela en la entrada y en la salida de los autos.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Mantenimiento y conservación del mirador'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se destapó drenaje en la calle Margarita. Se está consiguiendo una tapa para la coladera.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se cambió el disco para tener más días de grabación en las cámaras del circuito cerrado de tv. Ahora tenemos un mes de garbación.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se han emitido 5 Cartas de no Adeudo'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se recuperó cuotas atrasadas de dos lotes ($32,400)'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se habilitaron las lámparas de Malva'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se reparó el alumbrado de las calles Margarita y Pensamiento.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se cambió WC en la oficina - zona de vigilancia'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Por las noches se hacen rondines de vigilancia. Por cierto, se equipó al vigilante con una lámpara más potente.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se limpia semanalmente el cuarto de basura, y los botes se desinfectan.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se paga puntualmente en el Municipio el servicio de recolección de basura.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Intermediación para limpieza de lotes con retroexcavadora'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Lona de entrada para advertir que los compradores validen la situación legal y que se esté al corriente en los pagos de cuotas.'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se habilitó una cuenta de correo oficial: admon@vistaaltatx.com'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se empezó el proceso de registro de la asociación civil. Se ha tenido varios contactos con diferentes notarios, pero han habido dos notarías que han tenido problemas y tuvimos que cambiar a otra notaría'],
        ['fecha' => '2026-07-28', 'descripcion' => 'Se habilita el sitio del fraccionamiento: vistaaltatx.com'],
    ],

    /*
     * «Lo que sigue»: lo que la Mesa Directiva todavía no ha hecho, en el orden
     * en que se publica debajo de la Bitácora. Aquí el orden sí importa —el
     * primer renglón es el pendiente del que cuelgan los demás—, y es el que se
     * respeta al sembrar.
     *
     * Ninguno lleva fecha comprometida, y no es que falte capturarla: el
     * Pendiente no tiene ese campo (ver App\Models\Pendiente). Varios dependen
     * de un tercero que lleva su propio paso, y una fecha que no se controla se
     * lee como promesa.
     *
     * Cada renglón:
     *
     *     ['titulo' => 'Qué falta, en una línea.', 'detalle' => 'Por qué sigue pendiente.'],
     */
    'pendientes' => [
        [
            'titulo' => 'Constituir la Asociación Civil',
            'detalle' => 'De ahí sale la cuenta a nombre del fraccionamiento y la posibilidad de firmar como Vista Alta.',
        ],
        /*
         * Ocupa el lugar de «Vigilancia las 24 horas, los siete días», que se
         * cumplió el 2 de agosto de 2026 y subió a la Bitácora.
         *
         * No es un pendiente de relleno para no dejar el hueco. Los cuatro
         * turnos cubren la semana entera **mientras nadie falte**: no hay quinta
         * persona ni relevo, así que una incapacidad o unas vacaciones dejan el
         * acceso solo o obligan a que alguien doble turno. Anunciar «24 horas,
         * los siete días» sin decir esto haría que el primer martes descubierto
         * convirtiera la página en mentira.
         */
        [
            'titulo' => 'Cubrir faltas y vacaciones sin dejar el acceso solo',
            'detalle' => 'Los cuatro turnos cubren la semana completa mientras nadie falte. No hay relevo: una incapacidad o unas vacaciones dejan un hueco, o obligan a que alguien doble turno. Falta resolver cómo se cubre eso.',
        ],
        [
            'titulo' => 'Cámaras en las zonas que hoy no se ven',
            'detalle' => 'Lo instalado deja puntos ciegos. Cubrirlos es lo que hace que la grabación sirva el día que haya algo que revisar.',
        ],
        [
            'titulo' => 'Cerca eléctrica reparada en todos sus tramos',
            'detalle' => 'Hay tramos sin funcionar. Mientras uno falle, el perímetro protege menos de lo que aparenta.',
        ],
        [
            'titulo' => 'Alumbrado público al 100%',
            'detalle' => 'Reponer lo que está apagado y mantenerlo así. No es una reparación de una vez: es mantenimiento que no se puede soltar.',
        ],
        [
            'titulo' => 'Coladera repuesta',
            'detalle' => 'La reposición, para la calle de Margarita le corresponde a la Fraccionadora, quien ya aceptó comprarla. Lo que nos toca es supervisar que se instale correctamente.',
        ],
    ],

    /*
     * El Reporte financiero de un mes: el resumen que la Asamblea lee de un
     * vistazo, más el enlace a la hoja de cálculo donde está el detalle.
     *
     * - `mes`      — el mes que se rinde, como 'AAAA-MM'. Un reporte cubre
     *                siempre un mes: de aquí salen el título de la página
     *                ('Junio de 2026') y su dirección
     *                (/reporte-financiero/2026-06), así que no es texto libre.
     *                Cambiarlo por otro mes **no** reemplaza al anterior: lo
     *                agrega, y el que ya estaba pasa al histórico.
     * - `hoja_url` — la hoja de Google. Tiene que estar compartida en
     *                «Cualquier persona con el enlace: puede ver»; si queda
     *                restringida a cuentas invitadas, quien la abra desde el
     *                sitio se topa con una pantalla de permiso denegado.
     * - `cifras`   — los renglones del resumen, en el orden en que se leen.
     *                `destacada` es el renglón del total, que sale resaltado
     *                como en un comprobante. Los montos van en pesos, con
     *                punto decimal y sin separador de miles: 12345.67. Un
     *                egreso se captura en negativo.
     */
    'reporte_financiero' => [

        'mes' => '2026-06',

        'hoja_url' => 'https://docs.google.com/spreadsheets/d/190VNjSqxbU2EyQMlHADjivYU7x83GWcgw0SnM6zIhso/edit?usp=sharing',

        'cifras' => [
            ['concepto' => 'Cuotas de junio', 'monto' => 57600],
            ['concepto' => 'Recuperaciones (adeudos viejos)', 'monto' => 33280],
            ['concepto' => 'Cuotas de julio', 'monto' => 2400],
            ['concepto' => 'Cuotas de agosto', 'monto' => 800],
            ['concepto' => 'Recargos', 'monto' => -80],
            ['concepto' => 'Total ingresos', 'monto' => 94000, 'destacada' => true],
            ['concepto' => 'Egresos recurrentes', 'monto' => -36512.52],
            ['concepto' => 'Egresos única vez', 'monto' => -8217],
            ['concepto' => 'Total egresos', 'monto' => -44729.52, 'destacada' => true],
            ['concepto' => 'Remanente', 'monto' => 49270.48, 'destacada' => true],
        ],

        /*
         * El mes trae un ingreso que no se repite y que pesa un tercio del
         * total. Sin decirlo, el remanente de $49,270.48 se lee como el
         * excedente normal del fraccionamiento, y no lo es.
         */
        'aclaracion' => 'Las Recuperaciones ($33,280.00) son el cobro de adeudos de meses anteriores, no cuotas de este mes. Es un ingreso extraordinario: no forma parte del ingreso regular del fraccionamiento y no hay que esperarlo cada mes. Sin él, el remanente de junio habría sido de $15,990.48.',

    ],

];
