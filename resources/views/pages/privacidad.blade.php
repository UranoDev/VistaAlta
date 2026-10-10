{{--
    Aviso de Privacidad. Portado de nvavista (docs/adr/0003) y recortado a lo
    que este sitio de verdad hace: aquí no hay cuentas de usuario, ni pagos, ni
    carga de archivos.

    Tres cuidados que lo gobiernan:

    1. No lleva la franja de "borrador pendiente de revisión legal" que traen
       las páginas de nvavista. La Mesa Directiva asume el texto como vigente,
       y de ahí salen dos consecuencias: no puede quedar ningún corchete sin
       resolver, y la fecha de última actualización es una afirmación.

    2. Tampoco lleva Rojo Sello. Ese color está reservado a alertas y lo usa
       /demanda; dos franjas rojas más lo volverían decoración.

    3. Dice qué datos se piden y para qué, **sin nombrar las pantallas** ni los
       proveedores: una lista de datos y una lista de finalidades, genéricas. Solo
       describe lo que el sitio sí recaba —y un aviso que enumera datos que nadie
       pide es tan falso como uno que calla los que sí—, así que si algún
       formulario pide o guarda algo nuevo, este documento cambia en el mismo
       despliegue.

    4. Lo que promete se cumple en código. El plazo de conservación de la
       sección 6 sale de `contenido.legal.conservacion_anos` y lo hace valer el
       comando `datos:depurar` (`app/Console/Commands`), que corre a diario.

    La fecha y el correo salen de `config/contenido.php`, que los comparte con
    los Términos de Servicio y con la página de Comprobantes.
--}}
@php
    $correo = config('contenido.correo_contacto');
    $actualizado = config('contenido.legal.actualizado_en');
    $conservacion = config('contenido.legal.conservacion_anos');
@endphp

<x-layout.app title="Aviso de Privacidad">
    <x-palette-receipt.seccion rotulo="Datos personales" titulo="Aviso de Privacidad">
        <p class="cifra text-xs text-grafito/70">Última actualización: {{ $actualizado }}</p>

        <div class="mt-8 space-y-8">

            <x-legal.seccion numero="1" titulo="Identidad y domicilio del responsable">
                <p>
                    Fraccionamiento Vista Alta, con domicilio en Paseo del Girasol 81, Barrio de San Juan,
                    Tequisquiapan, Querétaro, CP 76755, México (en adelante, el &ldquo;Responsable&rdquo;), es
                    responsable del tratamiento de sus datos personales conforme al presente Aviso de Privacidad, en
                    cumplimiento de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares
                    (&ldquo;LFPDPPP&rdquo;) y su Reglamento.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="2" titulo="Datos personales que recabamos">
                <p>
                    Según lo que usted haga en este sitio, le pedimos algunos de los siguientes datos personales. Cada
                    formulario pide solo los que necesita:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Su nombre completo, o el nombre con el que decide firmar un comentario.</li>
                    <li>Su número de teléfono celular.</li>
                    <li>Su correo electrónico.</li>
                    <li>El domicilio de una propiedad: la calle y el número oficial, o bien la manzana y el lote.</li>
                    <li>El tipo de propiedad: terreno, casa terminada o casa en construcción.</li>
                    <li>
                        Si usted decide darlos: el nombre y el teléfono o el correo de otras personas a las que se puede
                        avisar por la propiedad, y el nombre y el teléfono de un contacto de emergencia.
                    </li>
                    <li>El texto del comentario que escribe.</li>
                    <li>
                        La fecha en que aceptó este Aviso y la fecha en que verificó su correo y su celular. De los
                        códigos de verificación no se guarda el código, sino una huella que no permite recuperarlo.
                    </li>
                    <li>
                        Cuando la Administración revisa un registro: quién lo revisó, cuándo y, si la hay, una nota de
                        cómo se revisó.
                    </li>
                </ul>
                <p>
                    Si usted nos da datos de otras personas, como contactos adicionales o de emergencia, manifiesta que
                    cuenta con su autorización para hacerlo y que les dio a conocer este Aviso.
                </p>
                <p>
                    No recabamos datos personales sensibles (por ejemplo, origen étnico o racial, estado de salud,
                    información genética, creencias religiosas, filosóficas o morales, afiliación sindical, opiniones
                    políticas o preferencia sexual).
                </p>
                <p>
                    Además, la dirección IP se usa para limitar el abuso de los formularios.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="3" titulo="Finalidades del tratamiento">
                <p>
                    Sus datos personales se usan única y exclusivamente para las siguientes finalidades, necesarias para
                    el servicio que usted pidió:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Saber quién es usted y cómo localizarlo, con su nombre, su celular y su correo.</li>
                    <li>
                        Verificar que usted controla el celular y el correo que dio, con un código enviado por SMS o por
                        correo.
                    </li>
                    <li>Identificar la propiedad de que se trata, con su domicilio y su tipo, y evitar que se registre dos veces.</li>
                    <li>
                        Que la Administración revise y valide los registros, es decir, que dé por cierta la información
                        que usted declaró.
                    </li>
                    <li>
                        Integrar la información de los propietarios y de sus propiedades para el sistema automatizado de
                        pagos del fraccionamiento.
                    </li>
                    <li>
                        Dar seguimiento a los servicios que usted solicitó y responder a sus comentarios. Un comentario
                        se publica con el nombre que usted escribió únicamente si usted eligió que fuera público y
                        después de que la Administración lo publique.
                    </li>
                    <li>
                        Avisar a las personas de contacto que usted indicó, y localizar a su contacto de emergencia
                        cuando haga falta.
                    </li>
                    <li>Cumplir con obligaciones legales aplicables.</li>
                </ul>
                <p>
                    No utilizaremos sus datos personales para finalidades distintas a las aquí descritas, como
                    mercadotecnia, publicidad o prospección comercial. Este sitio no crea cuentas de usuario, no procesa
                    pagos y no recibe archivos.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="4" titulo="Transferencia de datos personales">
                <p>
                    No transferimos sus datos personales a terceros, salvo a las autoridades competentes cuando exista un
                    requerimiento legal fundado y motivado.
                </p>
                <p>
                    Para operar el sitio nos apoyamos en proveedores de alojamiento, de mensajería SMS y de correo
                    electrónico. Reciben únicamente los datos que necesitan para prestar ese servicio —por ejemplo, el
                    número o la dirección de destino y el contenido del mensaje con el código— y los tratan por cuenta
                    nuestra y siguiendo nuestras instrucciones, sin poder usarlos para otros fines.
                </p>
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

            <x-legal.seccion numero="6" titulo="Limitación de uso, divulgación y conservación">
                <p>
                    Su número de teléfono, su correo, su domicilio y los datos de sus contactos no se publican en
                    ninguna parte del sitio: solo los ve la Administración, en su panel. Lo único que se publica es el
                    nombre que usted escribe al firmar un comentario público, junto con el comentario, y solo después
                    de que la Administración lo publique.
                </p>
                <p>
                    Si usted elige que su comentario sea privado, lo lee únicamente la Administración y no puede
                    hacerse público después, por ningún medio.
                </p>
                <p>
                    Conservamos sus datos personales mientras se usan y hasta {{ $conservacion }} años después de la
                    última vez que se actualizaron o usaron. Pasado ese plazo se eliminan automáticamente.
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
                        proteger los formularios. En la sesión se recuerda lo que usted está haciendo, como el celular
                        que acaba de escribir o el registro que está verificando, para no pedírselo otra vez.
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
                    código nuevo para volver a comentar, que los formularios ya no traigan escritos sus datos y
                    que, si estaba verificando un registro, lo retome con el enlace del correo que se le mandó.
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
                    Al validar su teléfono, enviar un formulario o aceptar este Aviso en este sitio, usted manifiesta
                    su consentimiento para el tratamiento de sus datos personales conforme a los términos establecidos
                    en el presente Aviso de Privacidad.
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
