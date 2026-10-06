<!DOCTYPE html>
<html lang="es-MX">
<body style="margin:0;padding:24px;background:#f4f1ea;font-family:Arial,Helvetica,sans-serif;color:#1f2a24;">
    <div style="max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #d8d3c4;padding:24px;">
        <p style="margin:0 0 16px;font-size:16px;">Hola, {{ $nombre }}.</p>

        <p style="margin:0 0 16px;font-size:16px;line-height:1.5;">
            Para confirmar tu registro de propietario, toca el botón o escribe este código en la página.
        </p>

        <p style="margin:24px 0;text-align:center;">
            <a href="{{ $enlace }}"
               style="display:inline-block;background:#1e4d3b;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;padding:14px 24px;">
                Confirmar mi registro
            </a>
        </p>

        <p style="margin:0 0 8px;font-size:14px;color:#55605a;text-align:center;">Tu código</p>
        <p style="margin:0 0 24px;font-size:32px;font-weight:bold;letter-spacing:6px;text-align:center;font-family:Menlo,Consolas,monospace;">{{ $codigo }}</p>

        <p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#55605a;">
            El código y el enlace sirven por {{ $horas }} horas. Si tú no te registraste, ignora este correo.
        </p>
        <p style="margin:0;font-size:13px;line-height:1.5;color:#55605a;">
            Si el botón no abre, copia esta dirección en tu navegador:<br>
            <span style="word-break:break-all;">{{ $enlace }}</span>
        </p>
    </div>
</body>
</html>
