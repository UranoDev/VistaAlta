{{--
    Términos de Servicio. Portados de nvavista (docs/adr/0003) y recortados: de
    las doce secciones de allá se caen tres —registro y cuenta de usuario,
    cuotas de mantenimiento y pagos, suspensión y cancelación de cuentas—
    porque aquí no existe ninguna de esas cosas. Quedan diez.

    Como en el Aviso de Privacidad, no hay franja de "borrador pendiente de
    revisión legal" ni corchetes sin resolver, y el Rojo Sello sigue reservado
    a /demanda.

    Dos cambios de fondo que no vienen de nvavista y que se agregan porque el
    sitio los hace y nadie los había dicho con carácter de término: qué es la
    validación por SMS (sección 3) y que un comentario público pasa por
    revisión de la Mesa Directiva antes de aparecer (sección 5).

    Al visitante se le llama «Visitante» y no «Residente» ni «Usuario»: aquí no
    hay cuentas, y comentar no exige ser colono.
--}}
@php
    $correo = config('contenido.correo_contacto');
    $actualizado = config('contenido.legal.actualizado_en');
@endphp

<x-layout.app title="Términos de Servicio">
    <x-recibo.seccion rotulo="Uso del sitio" titulo="Términos de Servicio">
        <p class="cifra text-xs text-grafito/70">Última actualización: {{ $actualizado }}</p>

        <div class="mt-8 space-y-8">

            <x-legal.seccion numero="1" titulo="Aceptación de los términos">
                <p>
                    El presente documento (&ldquo;Términos de Servicio&rdquo;) regula el acceso y uso del sitio
                    vistaaltatx.com (en adelante, el &ldquo;sitio&rdquo;), operado por la Mesa Directiva del
                    Fraccionamiento Vista Alta Residencial (en adelante, la &ldquo;Mesa Directiva&rdquo;), con
                    domicilio en Paseo del Girasol 81, Barrio de San Juan, Tequisquiapan, Querétaro, CP 76755, México.
                </p>
                <p>
                    Al utilizar el sitio, usted (en adelante, el &ldquo;Visitante&rdquo;) acepta quedar sujeto a estos
                    Términos de Servicio, así como al Aviso de Privacidad correspondiente. Si no está de acuerdo,
                    deberá abstenerse de utilizar el sitio.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="2" titulo="Objeto del sitio">
                <p>
                    El sitio tiene como finalidad la rendición de cuentas de la Mesa Directiva ante los Colonos.
                    Específicamente, sirve para:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        Publicar la Propuesta que la Mesa Directiva somete a consideración de la Asamblea, así como
                        las actividades realizadas durante el periodo y el reporte financiero que las respalda.
                    </li>
                    <li>Recibir comentarios y preguntas de quien quiera dejarlos sobre esa Propuesta.</li>
                </ul>
                <p>
                    El sitio no administra el fraccionamiento: no cobra ni procesa pagos, no lleva estados de cuenta,
                    no tiene padrón de colonos, no recibe archivos y no crea cuentas de usuario. La información
                    publicada es de carácter informativo y se basa en los registros de la Mesa Directiva.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="3" titulo="Validación por SMS">
                <p>
                    Para dejar un comentario, el sitio le pide su número de teléfono celular y le envía por SMS un
                    código de un solo uso. Escribir ese código valida el teléfono, y con ello acredita únicamente que
                    del otro lado hay una persona a la que se le puede responder: no acredita que usted sea propietario
                    ni residente del fraccionamiento, ni le otorga cargo o membresía alguna.
                </p>
                <p>
                    Una vez validado, ese teléfono puede comentar durante
                    {{ \App\Support\VentanaDeValidacion::MINUTOS }} minutos sin volver a validarse. Transcurrido ese
                    lapso no se pierde nada de lo ya publicado: simplemente se le pedirá un código nuevo para volver a
                    escribir. Su número no se publica en ninguna parte del sitio.
                </p>
                <p>
                    El Visitante es responsable de utilizar un número de teléfono del que pueda disponer legítimamente.
                    El envío de códigos está sujeto a un límite de intentos para evitar el uso abusivo del servicio de
                    mensajes.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="4" titulo="Comunicados y avisos">
                <p>
                    Los comunicados publicados en el sitio tienen carácter informativo y buscan facilitar la difusión
                    de acuerdos, avisos, convocatorias a asambleas y demás información relevante para el
                    fraccionamiento. Dichos comunicados no sustituyen, en su caso, las notificaciones formales que
                    deban realizarse conforme a la Ley de Propiedad en Condominio aplicable o al reglamento interno del
                    fraccionamiento.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="5" titulo="Obligaciones del Visitante">
                <p>El Visitante se compromete a:</p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>Utilizar el sitio únicamente para los fines para los que fue diseñado.</li>
                    <li>No proporcionar información falsa, incompleta o de terceros sin autorización.</li>
                    <li>No intentar vulnerar la seguridad del sitio ni acceder al panel de la Mesa Directiva.</li>
                    <li>
                        Respetar los derechos de los demás colonos y de la Mesa Directiva al redactar sus comentarios,
                        absteniéndose de publicar contenido ofensivo, difamatorio o ajeno al asunto que se somete a la
                        Asamblea.
                    </li>
                </ul>
                <p>
                    Los comentarios que su autor marca como públicos pasan por la revisión de la Mesa Directiva antes
                    de aparecer en el sitio, y pueden no publicarse. Los comentarios que su autor marca como privados
                    no se publican en ningún caso, y esa elección no puede revertirse después.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="6" titulo="Propiedad intelectual">
                <p>
                    El diseño, estructura, logotipos y demás elementos del sitio son propiedad de la Mesa Directiva o
                    de sus proveedores tecnológicos y se encuentran protegidos por la legislación aplicable en materia
                    de propiedad intelectual. Queda prohibida su reproducción, distribución o modificación sin
                    autorización previa y por escrito.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="7" titulo="Limitación de responsabilidad">
                <p>
                    El sitio se proporciona &ldquo;tal cual&rdquo; y &ldquo;según disponibilidad&rdquo;. La Mesa
                    Directiva no garantiza que el acceso sea ininterrumpido o esté libre de errores, y no será
                    responsable por:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                    <li>
                        Fallas, interrupciones o indisponibilidad del servicio derivadas de causas de fuerza mayor,
                        caso fortuito o de terceros proveedores tecnológicos.
                    </li>
                    <li>
                        La falta de entrega, el retraso o el costo de los mensajes SMS de validación, que dependen del
                        proveedor de mensajería y de la compañía telefónica del Visitante.
                    </li>
                    <li>
                        Errores u omisiones en la información publicada, salvo dolo o negligencia grave de la Mesa
                        Directiva.
                    </li>
                </ul>
            </x-legal.seccion>

            <x-legal.seccion numero="8" titulo="Protección de datos personales">
                <p>
                    El tratamiento de los datos personales de los Visitantes se rige por el
                    <a href="{{ route('privacidad') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Aviso de Privacidad</a>
                    disponible en el sitio, el cual forma parte integral de estos Términos de Servicio.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="9" titulo="Modificaciones a los Términos">
                <p>
                    La Mesa Directiva podrá modificar estos Términos de Servicio en cualquier momento, publicando la
                    versión actualizada en el sitio junto con la fecha de su última actualización. El uso continuado
                    del sitio después de dichos cambios implica la aceptación de los mismos.
                </p>
            </x-legal.seccion>

            <x-legal.seccion numero="10" titulo="Legislación aplicable y jurisdicción">
                <p>
                    Estos Términos de Servicio se regirán e interpretarán conforme a las leyes federales de los Estados
                    Unidos Mexicanos y, en lo conducente, por la Ley de Propiedad en Condominio del estado de Querétaro.
                    Para cualquier controversia derivada del uso del sitio, las partes se
                    someten a los tribunales competentes del estado de Querétaro, renunciando a cualquier otro fuero
                    que pudiera corresponderles por razón de su domicilio presente o futuro.
                </p>
            </x-legal.seccion>

            <x-legal.seccion titulo="Contacto">
                <p>
                    Para dudas sobre estos Términos de Servicio, puede contactar a la Mesa Directiva en:
                    <a href="mailto:{{ $correo }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">{{ $correo }}</a>.
                </p>
            </x-legal.seccion>

        </div>
    </x-recibo.seccion>
</x-layout.app>
