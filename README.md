# urge

Sitio de rendición de cuentas de la Mesa Directiva de Vista Alta hacia su Asamblea.
Se sirve en `vistaaltatx.com` (ADR-0002).

Antes de tocar código: `CONTEXT.md` (glosario del dominio — la terminología es
vinculante) y `docs/adr/` (las decisiones tomadas, varias contraintuitivas a
propósito).

## Stack

Laravel 13 · Blade · Tailwind CSS v4 · Vite · SQLite · Filament 4 (solo el panel).

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run dev        # y, en otra terminal, php artisan serve
```

## Páginas públicas

Seis, todas de lectura y sin autenticación (`routes/web.php`). Cuatro están en la
navegación de arriba:

- **`/`** — la Propuesta. El único asunto que se somete a la Asamblea.
- **`/actividades`** y **`/reporte-financiero`** — lo que la respalda. El Reporte
  financiero se rinde **un mes a la vez** y los meses se acumulan: la raíz publica
  siempre el más reciente y cada mes anterior conserva su propia dirección
  (`/reporte-financiero/2026-06`), listada al pie de la página. El vigente vive en las
  dos direcciones, así que la página declara la raíz como canónica en vez de redirigir
  — la URL con fecha tiene que seguir sirviendo junio el día que junio deje de ser lo
  vigente (`docs/adr/0005`).
- **`/demanda`** — Demanda. Pide los comprobantes de depósito a la administración
  pasada para documentar cuánto se entregó. La única que **pide** algo en vez de
  rendir cuentas, y por eso va al final de la navegación y **no lleva enlaces de
  navegación interna** — el `mailto:` es su única salida. Es estática; el número de
  comprobantes recibidos y el correo de contacto salen de `config/contenido.php`, no
  del blade. Es también **el único lugar donde se usa el Rojo Sello**.

Las otras dos se enlazan desde el pie, no desde el menú —arriba va lo que se le pide
a la Asamblea que lea—, y el Aviso además se enlaza desde el formulario de
Comentarios, donde se recaban los datos:

- **`/aviso-de-privacidad`** y **`/terminos-de-servicio`** — portadas de nvavista y
  recortadas a lo que este sitio sí hace. No llevan franja de borrador: la Mesa
  Directiva asume el texto como vigente, así que no puede quedar ningún corchete sin
  resolver y la fecha de `contenido.legal.actualizado_en` es una afirmación, no un
  dato de bitácora — se cambia a mano cuando el texto cambie.

## Panel de la Mesa Directiva

Vive en `/admin` y es **el único lugar del sitio que pide autenticación**: las
páginas públicas no piden nada nunca. Está construido con Filament 4.

```bash
php artisan make:filament-user   # una cuenta por integrante de la Mesa Directiva
```

No hay registro abierto ni roles: el Colono no es una entidad del sistema y no tiene
cuenta (`CONTEXT.md`), así que estar en la tabla `users` *es* el permiso. Dar de baja
a alguien es borrar su cuenta.

> **Las dos frases anteriores están decididas en contra** y se caen con el control de
> cuotas (ver «Decidido y todavía no construido»). Van a ser cuatro roles acumulables
> con `spatie/laravel-permission`, y las cuentas van a **desactivarse, nunca
> borrarse** — en un sistema de dinero, quién hizo qué no se puede borrar. Antes de
> portar código de nvavista que apunte a `users`, revisar que ninguna llave foránea
> venga con `cascadeOnDelete`.

Tres pantallas:

- **Comentarios** — **una sola** pantalla para todo lo relativo a Comentarios: la
  lista trae públicos y privados juntos, y el interruptor de Recepción de comentarios
  va en su encabezado. Las pestañas son «En cola» (la de entrada: la Cola de
  moderación, en el orden en que llegaron), «Privados», «Publicados», «Descartados» y
  «Todos»; las dos del medio existen para poder deshacer. La acción se llama
  **Publicar** —no «Liberar»—, igual que en el código (`Comentario::publicar()`). La
  búsqueda encuentra por nombre y por teléfono, normalizando el término a dígitos,
  así que `55-3126-9267` y `5531269267` traen lo mismo.

  **Un Comentario privado no se publica por ninguna vía**, y desde que comparte lista
  con los públicos eso ya no lo garantiza la consulta del recurso: son tres capas y
  las tres están implementadas —no se ofrecen sus acciones, no lleva casilla de
  selección utilizable, y la acción en lote filtra a públicos del lado del servidor
  antes de iterar—. `ComentarioPrivadoNoSeModera` en el modelo es la última red. La
  elección del autor es definitiva y la Mesa Directiva no puede revertirla
  (`CONTEXT.md` → `Comentario privado`).

  El interruptor decide *si se puede escribir*; cerrarlo retira el formulario de la
  página de la Propuesta pero **no despublica nada**. No confundirlo con la
  moderación, que decide *qué se publica*: son cosas distintas y las dos advertencias
  siguen escritas en la propia pantalla.

  Debajo va el segundo interruptor, la **Vía de recepción**, que decide *por dónde
  llegan* cuando sí se admiten: `otp` —el colono escribe en el sitio validando su
  celular por SMS— o `whatsapp` —la página no pide teléfono y muestra un enlace a la
  conversación con la Mesa Directiva, con el número que se captura ahí mismo—. El
  primero manda: con la recepción cerrada la vía no se usa. **Hoy sale al aire en
  `whatsapp`** porque el SMS de Twilio no se entrega en producción (error 30008: las
  operadoras filtran el tráfico A2P de un long code sin registrar); cuando eso se
  destrabe, volver a `otp` es mover el selector, no desplegar.

  La diferencia de fondo entre las dos no es el canal: **es quién elige la
  visibilidad**. En `otp` la elige el autor y queda fija; en `whatsapp` el comentario
  llega a un chat y lo captura la Mesa Directiva, así que el mensaje del enlace ya
  viene prellenado pidiéndole al autor que la diga por escrito
  (`config/contenido.php` → `whatsapp_mensaje`). Esa línea del texto no es
  decorativa: es lo que mantiene la elección siendo del autor.

  > **Pendiente conocido de la vía `whatsapp`:** el panel todavía **no tiene dónde
  > capturar** un Comentario que llegó por chat —`canCreate` sigue en falso desde
  > URVA-7, y el modelo exige `crearPublico()`/`crearPrivado()`—, así que en esta vía
  > nada nuevo puede llegar a publicarse en la página. Por eso el texto de la página
  > promete lo que sí se hace hoy (leer y contestar en el chat) y no promete
  > publicación. Esa pantalla es lo que falta para que la vía sirva completa, y trae
  > una decisión con ella: si un Comentario transcrito debe quedar marcado como tal,
  > porque de él el sitio ya no puede afirmar que su autor eligió la visibilidad.
- **Actividades** — alta, edición y borrado de lo que la Mesa Directiva llevó a cabo
  durante el Periodo. A diferencia de los Comentarios, esto sí lo redacta la Mesa
  Directiva, y lo que se da de alta sale de inmediato en la página pública: no hay
  borradores ni cola de por medio. El formulario tiene **dos campos y solo dos**,
  fecha y descripción: sin costo, porque el dinero se rinde completo en el Reporte
  financiero, y sin adjunto, porque en el sitio no se cargan documentos.
- **Reporte financiero** — un listado, con un reporte por mes: el resumen de cifras y
  el enlace a la hoja de cálculo de Google de cada uno. **Capturar un mes nuevo no
  borra al anterior**, lo empuja al histórico; corregir un mes es editarlo, no volver
  a capturarlo, y la base impide que existan dos del mismo mes. El mes se elige de una
  lista y no se escribe: de él salen el título («Junio de 2026») y la dirección
  pública, que así no pueden contradecirse. Las cifras **se capturan a mano**: la hoja
  es la fuente de verdad y el sitio no la lee ni la importa, así que cuando la hoja
  cambia hay que volver a esta pantalla. Y lo que se guarde aquí queda **público y sin
  contraseña**, y para siempre — son decisiones tomadas (`docs/adr/0004` y
  `docs/adr/0005`), no un pendiente. Las dos advertencias están también dentro de la
  propia pantalla.

> **Al entregar el panel, decirlo explícitamente:** la Recepción de comentarios nace
> abierta y nadie la cierra sola, así que los Comentarios públicos van a seguir
> llegando **indefinidamente**. Si nadie revisa la Cola de moderación, se apilan sin
> publicar y los colonos concluyen que se les está ignorando. Alguien de la Mesa
> Directiva tiene que quedar a cargo de revisarla. La advertencia también está dentro
> del propio panel, en el encabezado de la pantalla de Comentarios.

Los assets del panel (`public/css/filament`, `public/js/filament`,
`public/fonts/filament`) no se versionan: los republica `filament:upgrade`, que corre
solo en el `post-autoload-dump` de composer. Su **tema** es otra cosa y sí se
versiona: es una entrada más de Vite, así que sale de `npm run build` junto con el
resto del sitio (ver «Sistema visual»).

## Sistema visual «Recibo» → «Palette Receipt»

> **Este sistema se renombra a «Palette Receipt»** (URVA-31). El nombre «Recibo» queda
> libre para lo que significa en la calle: el comprobante de un pago, que el sitio va a
> empezar a emitir. Mientras no se haga el renombre, «Recibo» sigue siendo esto.

La dirección visual es la Propuesta 2 de `docs/design/paletas-condominios.html`: el
comprobante de pago —tinta de folio, números tabulares, papel térmico—, porque el
argumento del sitio es la transparencia.

- Los tokens viven en `resources/css/palette-receipt.css`, dentro de `@theme`. Los
  colores se usan por su nombre (`bg-tinta`, `text-grafito`, `border-linea`), no por
  su hex. **Rojo Sello está reservado a alertas y urgencia**: no es un color
  decorativo. Tienen archivo propio, y no `app.css`, porque los importan dos hojas:
  la del sitio y la del tema del panel. Ahí se escriben una sola vez.
- Las piezas reutilizables son componentes anónimos de Blade en
  `resources/views/components/recibo/`: `tarjeta`, `renglon`, `rotulo`, `sello`,
  `boton`, `nota`, `campo`, `seccion`.
- `/sistema-visual` muestra las piezas juntas. Es una herramienta de construcción y
  **no se sirve en producción**.
- Las tipografías son IBM Plex Sans e IBM Plex Mono, auto-hospedadas desde
  `@fontsource` vía el plugin de fuentes de Vite (`vite.config.js`). No hay petición
  a un CDN de fuentes en tiempo de ejecución.
- **El panel también viste Palette Receipt**, con tema propio en
  `resources/css/filament/admin/theme.css`. Ahí va lo que la API de colores de
  Filament no alcanza —los neutros cálidos, el blanco redefinido a papel-alto y la
  corrección de saturación de `primary` y `success`—, cada cosa con la razón por la
  que no cabía en `AdminPanelProvider`. **El panel no tiene modo oscuro**: Palette
  Receipt es papel y tinta, y darle versión nocturna sería ampliar el sistema visual,
  no aplicarlo.

## Pruebas

```bash
php artisan test
./vendor/bin/pint
```

## Decidido y todavía no construido

**Todo lo de arriba describe el sitio tal como es hoy.** Esta sección es lo contrario:
decisiones ya tomadas que **nada del código refleja todavía**. Está aquí para que nadie
"arregle" de buena fe algo que está a punto de cambiar, ni construya sobre una premisa
que ya se revirtió.

El detalle vive en YouTrack (proyecto URVA); los ADR que las sostienen se escriben como
primera tarea de cada Epic.

### Control de cuotas — URVA-27

El sitio deja de ser solo rendición de cuentas y se vuelve **portal operativo**. Esto
revierte de frente lo que `CONTEXT.md` dice hoy: el Colono pasa a tener cuenta, iniciar
sesión y existir en un padrón, y aquí sí se van a pagar cuotas y consultar estados de
cuenta.

Se **porta el código de nvavista** (padrón, cuotas, recibos, identidad, roles) **sin
tenancy** — cuarta copia divergente después de la que ya advirtió `docs/adr/0003`.

Lo que conviene saber antes de tocar nada de esto:

- **La Unidad debe, no la persona.** Hay historial de titularidad con vigencias, así que
  una Cuota sabe quién era el dueño cuando se generó — pero la deuda se queda con el lote.
- **Monto único**, sin excepciones por Unidad. Vive en una tabla de vigencias con
  `monto`, `sobrecargo` y `dias_gracia`: un adeudo de 2024 se cobra al precio de 2024.
- **La Cuota congela sus tres condiciones al generarse.** Corregir una vigencia **no**
  recalcula nada ya generado, ni siquiera lo pendiente.
- **El sobrecargo se aplica una sola vez** al vencer la gracia y **no crece**. Es
  deliberado: una deuda que crece sola llega a un número que nadie va a pagar nunca.
- **Un Recibo, una Unidad.** Quien tiene tres lotes hace tres pagos. Repartir un abono
  entre propiedades exigiría un criterio, y ése es del colono.
- **El teléfono confirmado es obligatorio; el correo no.** Por eso la confirmación puede
  asentarse a mano (`otp`, `con_llamada`, `en_persona`, `con_documento`) y nunca es un
  booleano: el Comité de Vigilancia tiene que poder auditar cuántas no fueron por OTP.
  Existe porque **el SMS no se entrega en producción** (ver abajo).
- **Cobradores**: reciben efectivo, capturan desde el celular y entregan en **Cortes de
  caja**. Un faltante **lo repone el Cobrador** — es deuda suya, no un egreso, y no toca
  ningún Reporte financiero.

### Reporte financiero derivado — URVA-46

El Reporte deja de capturarse y pasa a **calcularse**. Aparecen dos entidades nuevas
—**Egresos** y **Otros ingresos**— porque el sistema solo puede derivar lo que sabe, y
de los gastos no sabe nada.

- Pasa a ser **dos páginas**: el resumen (como hoy) y un **detalle público** donde el
  botón «Ver el detalle» ocupa el lugar que hoy tiene el enlace a la hoja de Google.
- **La hoja se retira de los meses nuevos**; los ya publicados conservan su enlace. Eso
  revierte `docs/adr/0004` en cuanto a la fuente de verdad — no en cuanto a que se lea
  sin barrera, que se refuerza.
- **El comprobante de un Egreso es un archivo cargado**, y es **lo único que este sitio
  va a cargar**: revierte, solo para eso, la regla de que aquí no se suben archivos. Lo
  carga únicamente la Mesa Directiva, lo abre cualquier rol con sesión, y **nunca es
  público** — trae datos fiscales de un tercero.
- **`docs/adr/0005` se matiza, no se revierte**: un mes publicado se puede reemitir,
  pero **dejando constancia** de cuándo se corrigió y qué cambió. El principio que
  protege es que el histórico no cambie *en silencio*.

### Avisos al Colono — URVA-57

Nadie le avisa al colono que se le venció la cuota, y es **a propósito**: hoy no hay
canal que sirva. El SMS no se entrega, WhatsApp es un enlace manual y el correo no lo
tiene todo el mundo. Se retoma cuando alguno se destrabe.

### Pendiente de operación

**El SMS de Twilio no se entrega en producción** (error 30008: las operadoras filtran el
tráfico A2P de un long code sin registrar). Es lo que mantiene los Comentarios en la vía
`whatsapp`, lo que obliga a la confirmación manual de teléfonos, y lo que tiene detenidos
los avisos. Destrabarlo desbloquea tres cosas de un golpe.
