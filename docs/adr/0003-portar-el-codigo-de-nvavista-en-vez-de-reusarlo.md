# Portar el código de nvavista en vez de reusarlo como tenant

La página de la Propuesta ya existe en nvavista: `AsociacionCivilController`
(`/asociacion-civil`) trae el video, las preguntas frecuentes ya redactadas sobre
constituir la Asociación Civil, el OTP por teléfono y la captura de Comentarios
detrás de una cookie de verificación. Se evaluó volver Vista Alta un tenant de
nvavista para heredar todo eso junto con el camino de dominio personalizado del
ADR-0001, y se decidió **no** hacerlo: se copian a este repo el controlador, el
`OtpService`, el `TwilioOtpSender` y la vista, quitando `BelongsToTenant`.

Se eligió portar para conservar un ciclo de release propio y no arrastrar tenancy a
un sitio de una sola comunidad. El ADR-0002 se sostiene: `vistaaltatx.com` sirve este
sitio, no un tenant.

## Consecuencias

Quedan **dos copias** del OTP y de la página de asociación civil, que van a divergir:
un arreglo en nvavista no llega aquí solo, ni al revés. Lo que falta —
público/privado en el Comentario, la Cola de moderación, la página de Actividades, el
Reporte financiero y la Clave de acceso — hay que construirlo igual en cualquiera de
los dos caminos; no fue eso lo que inclinó la decisión.
