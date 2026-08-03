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
        [
            'titulo' => 'Vigilancia las 24 horas, los siete días',
            'detalle' => 'Hoy quedan horas del día (y todo el domingo) sin vigilante en el acceso. Falta cerrar el turno completo para que no queden huecos.',
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
