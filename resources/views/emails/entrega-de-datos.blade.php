<!DOCTYPE html>
<html lang="es-MX">
<body style="margin:0;padding:24px;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;color:#1f2a24;">
    <div style="max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #d8d3c4;padding:24px;">
        <p style="margin:0 0 16px;font-size:16px;">Hola.</p>

        <p style="margin:0 0 16px;font-size:16px;line-height:1.5;">
            Atendemos tu solicitud de acceso a tus datos personales. Adjuntamos un archivo con todo lo que
            tenemos de ti en Vista Alta.
        </p>

        <p style="margin:0 0 16px;font-size:16px;line-height:1.5;">
            Dentro del ZIP hay dos copias de lo mismo: <strong>datos.txt</strong>, para leerlo, y
            <strong>datos.json</strong>, por si quieres llevártelo a otro sistema.
        </p>

        @if ($conClave)
            <p style="margin:0 0 16px;font-size:16px;line-height:1.5;">
                El archivo está protegido con una clave. No va en este correo: te la damos por otro medio.
            </p>
        @endif

        <p style="margin:0;font-size:13px;line-height:1.5;color:#55605a;">
            Si tú no pediste esto, avísanos en
            <a href="mailto:{{ config('contenido.correo_contacto') }}" style="color:#1e4d3b;">{{ config('contenido.correo_contacto') }}</a>.
        </p>
    </div>
</body>
</html>
