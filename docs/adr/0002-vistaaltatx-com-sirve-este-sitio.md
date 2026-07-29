# vistaaltatx.com sirve este sitio, no un tenant de nvavista

Todo el contenido de este proyecto vive en el dominio pelón `vistaaltatx.com`. Esto
contradice de frente el ADR-0001 de nvavista, que usa justamente ese host como el
ejemplo canónico de "dominio personalizado" de un tenant y para el que ya existen el
middleware `InitializeTenancyByDomainOrSubdomain`, la tabla `domains`, el estado
`pendiente`/`activo` y el runbook de alta de vhost + Certbot.

Se eligió así porque este sitio es lo que existe y tiene fecha; el tenant de Vista
Alta en nvavista no existe (no hay fila de tenant ni datos), y no se le va a reservar
el dominio bueno a algo hipotético. Se descartó servirlo en un subdominio
(`asamblea.vistaaltatx.com`), que habría dejado libre el dominio pelón para el tenant,
porque parte el sitio de la Mesa Directiva en dos direcciones distintas ante una
Asamblea que solo necesita una.

## Consecuencias

El camino de dominio personalizado de nvavista queda **diferido** para este host: si
Vista Alta llegara a entrar a nvavista, o convive en un subdominio o hay que migrar
una URL que la Asamblea ya tiene guardada. El dominio interno de tenant
(`vista-alta.nvavista.test` y su equivalente en producción) sigue disponible y no se
ve afectado. Conviene anotar este bloqueo en el ADR-0001 de nvavista — todavía no se
hizo, ese repo no se ha tocado.
