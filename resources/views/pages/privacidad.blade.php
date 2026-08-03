{{--
    Aviso de Privacidad. Portado de nvavista (docs/adr/0003) y recortado a lo
    que este sitio de verdad hace: aquí no hay cuentas de usuario, ni correo
    electrónico, ni pagos, ni carga de archivos.

    Tres cuidados que lo gobiernan:

    1. No lleva la franja de "borrador pendiente de revisión legal" que traen
       las páginas de nvavista. La Mesa Directiva asume el texto como vigente,
       y de ahí salen dos consecuencias: no puede quedar ningún corchete sin
       resolver, y la fecha de última actualización es una afirmación.

    2. Tampoco lleva Rojo Sello. Ese color está reservado a alertas y lo usa
       /demanda; dos franjas rojas más lo volverían decoración.

    3. Solo describe datos que el sitio sí recaba —teléfono, nombre y el texto
       del comentario, más la IP y la cookie de la Ventana de validación—. Un
       aviso que enumera datos que nadie pide es tan falso como uno que calla
       los que sí.

    La fecha y el correo salen de `config/contenido.php`, que los comparte con
    los Términos de Servicio y con la página de Comprobantes.
--}}
@php
    $correo = config('contenido.correo_contacto');
    $actualizado = config('contenido.legal.actualizado_en');
@endphp

<x-layout.app title="Aviso de Privacidad">
    <x-palette-receipt.seccion rotulo="Datos personales" titulo="Aviso de Privacidad">
        <p class="cifra text-xs text-grafito/70">Última actualización: {{ $actualizado }}</p>

        <div class="mt-8 space-y-8">

            <x-legal.seccion numero="1" titulo="Identidad y domicilio del responsable">
                <p>
                    Fraccionamiento Vista Alta Residencial, con domicilio en Paseo del Girasol 81, Barrio de San Juan,
                    Tequisquiapan, Querétaro, CP 76755, México (en adelante, el &ldquo;Responsable&rdquo;), es
                    responsable del tratamiento de sus datos personales conforme al presente Aviso de Privacidad, en
                    cumplimiento de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares
                    (&ldquo;LFPDPPP&rdquo;) y su Reglamento.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="2" titulo="Datos personales que recabamos">
                <p>
                    Este sitio recaba datos personales en un solo lugar: cuando usted deja un comentario sobre la
                    Propuesta. Los datos que se recaban son:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Su número de teléfono celular.</li>
                    <li>El nombre con el que decide firmar su comentario.</li>
                    <li>El texto del comentario que escribe.</li>
                </ul>
                <p>
                    No recabamos datos personales sensibles (por ejemplo, origen étnico o racial, estado de salud,
                    información genética, creencias religiosas, filosóficas o morales, afiliación sindical, opiniones
                    políticas o preferencia sexual).
                </p>
                <p>
                    Además, para validar su teléfono y evitar el uso abusivo del envío de mensajes, el sitio utiliza su
                    dirección IP y guarda temporalmente su número en una cookie cifrada de su navegador.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="3" titulo="Finalidades del tratamiento">
                <p>
                    Sus datos personales serán utilizados única y exclusivamente para las siguientes finalidades,
                    necesarias para que usted pueda comentar la Propuesta:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        Validar, mediante un código enviado por SMS, que del otro lado hay una persona a la que se le
                        puede responder.
                    </li>
                    <li>
                        Publicar su comentario en este sitio junto con el nombre que usted escribió, únicamente si
                        usted eligió que fuera público y después de que la Mesa Directiva lo publique.
                    </li>
                    <li>
                        Permitir que la Mesa Directiva lea su comentario y le conteste por el mismo número que validó.
                    </li>
                    <li>Cumplir con obligaciones legales aplicables.</li>
                </ul>
                <p>
                    No utilizaremos sus datos personales para finalidades distintas a las aquí descritas, como
                    mercadotecnia, publicidad o prospección comercial. Este sitio no crea cuentas de usuario, no recaba
                    correo electrónico, no procesa pagos y no recibe archivos.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="4" titulo="Transferencia de datos personales">
                <p>
                    Sus datos personales podrán ser compartidos con las siguientes personas, en el entendido de que
                    dicha transferencia no requiere de su consentimiento por encontrarse en alguno de los supuestos de
                    excepción previstos en el artículo 37 de la LFPDPPP, o bien contando con su consentimiento cuando
                    así lo requiera la ley:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        Twilio, el proveedor a través del cual se envía el mensaje SMS con su código de validación, que
                        para ese fin recibe su número de teléfono y lo trata únicamente por cuenta y siguiendo las
                        instrucciones del Responsable.
                    </li>
                    <li>
                        Proveedores de servicios tecnológicos que nos apoyan en el alojamiento (hosting) o
                        mantenimiento del sitio, quienes tratan los datos únicamente por cuenta y siguiendo las
                        instrucciones del Responsable.
                    </li>
                    <li>Autoridades competentes, cuando exista un requerimiento legal fundado y motivado.</li>
                </ul>
                <p>
                    No vendemos, rentamos ni compartimos sus datos personales con terceros para fines de mercadotecnia
                    ajenos a este Aviso.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="5" titulo="Mecanismos para el ejercicio de derechos ARCO">
                <p>
                    Usted tiene derecho a Acceder a sus datos personales que poseemos, a Rectificarlos en caso de ser
                    inexactos o incompletos, a Cancelarlos cuando considere que no se requieren para alguna de las
                    finalidades señaladas, así como a Oponerse al tratamiento de los mismos para fines específicos
                    (derechos ARCO).
                </p>
                <p>
                    Para ejercer cualquiera de estos derechos, así como para revocar su consentimiento al tratamiento
                    de sus datos, puede enviar una solicitud al correo electrónico
                    <a href="mailto:{{ $correo }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">{{ $correo }}</a>,
                    indicando:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Nombre completo y datos de contacto para comunicarle la respuesta a su solicitud.</li>
                    <li>Documento que acredite su identidad o, en su caso, la representación legal del titular.</li>
                    <li>
                        Descripción clara y precisa de los datos personales respecto de los cuales se busca ejercer el
                        derecho correspondiente.
                    </li>
                    <li>Cualquier elemento que facilite la localización de los datos personales.</li>
                </ul>
                <p>
                    Le daremos respuesta a su solicitud dentro de los plazos establecidos en la LFPDPPP (20 días
                    hábiles para dar respuesta, con posibilidad de una prórroga).
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="6" titulo="Limitación de uso y divulgación">
                <p>
                    Su número de teléfono no se publica en ninguna parte del sitio: se utiliza únicamente para validar
                    que del otro lado hay una persona a la que se puede responder.
                </p>
                <p>
                    Si usted elige que su comentario sea privado, lo lee únicamente la Mesa Directiva y no puede
                    hacerse público después, por ningún medio.
                </p>
                <p>
                    Si desea dejar de recibir comunicaciones de nuestra parte o solicitar que sus datos no sean
                    tratados para finalidades específicas distintas a las estrictamente necesarias para la prestación
                    del servicio, puede enviar su solicitud al correo señalado en la sección anterior.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="7" titulo="Uso de cookies y tecnologías similares">
                <p>Este sitio utiliza dos cookies, y ninguna de ellas sirve para rastrearlo ni para medir su conducta:</p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        La cookie de sesión que la plataforma necesita para el funcionamiento básico del sitio y para
                        proteger los formularios.
                    </li>
                    <li>
                        Una cookie cifrada y firmada que guarda su número de teléfono durante
                        {{ \App\Support\VentanaDeValidacion::MINUTOS }} minutos después de que valida su código, para
                        que pueda comentar en ese lapso sin volver a pedirle un SMS.
                    </li>
                </ul>
                <p>
                    El sitio no utiliza herramientas de analítica, publicidad, perfilamiento ni rastreo de terceros, ni
                    web beacons. Si usted borra las cookies de su navegador, lo único que ocurre es que se le pedirá un
                    código nuevo para volver a comentar.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="8" titulo="Cambios al Aviso de Privacidad">
                <p>
                    El presente Aviso de Privacidad puede sufrir modificaciones o actualizaciones derivadas de nuevos
                    requerimientos legales, de nuestras propias necesidades por los servicios que ofrecemos, de
                    nuestras prácticas de privacidad, o por otras causas. Nos comprometemos a mantenerlo informado
                    sobre los cambios que pueda sufrir el presente aviso a través de
                    <a href="{{ route('privacidad') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">{{ route('privacidad') }}</a>,
                    indicando la fecha de su última actualización al inicio del documento.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="9" titulo="Consentimiento">
                <p>
                    Al validar su teléfono y dejar un comentario en este sitio, usted manifiesta su consentimiento para
                    el tratamiento de sus datos personales conforme a los términos establecidos en el presente Aviso de
                    Privacidad.
                </p>
            </x-legal.seccion>

            <x-legal.seccion titulo="Contacto">
                <p>
                    Si tiene dudas respecto al tratamiento de sus datos personales, puede contactarnos en:
                    <a href="mailto:{{ $correo }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">{{ $correo }}</a>.
                </p>
            </x-legal.seccion>

        </div>
    </x-palette-receipt.seccion>
</x-layout.app>
