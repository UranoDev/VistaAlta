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
solo en el `post-autoload-dump` de composer.

## Sistema visual «Recibo»

La dirección visual es la Propuesta 2 de `docs/design/paletas-condominios.html`: el
comprobante de pago —tinta de folio, números tabulares, papel térmico—, porque el
argumento del sitio es la transparencia.

- Los tokens viven en `resources/css/app.css`, dentro de `@theme`. Los colores se
  usan por su nombre (`bg-tinta`, `text-grafito`, `border-linea`), no por su hex.
  **Rojo Sello está reservado a alertas y urgencia**: no es un color decorativo.
- Las piezas reutilizables son componentes anónimos de Blade en
  `resources/views/components/recibo/`: `tarjeta`, `renglon`, `rotulo`, `sello`,
  `boton`, `nota`, `campo`, `seccion`.
- `/sistema-visual` muestra las piezas juntas. Es una herramienta de construcción y
  **no se sirve en producción**.
- Las tipografías son IBM Plex Sans e IBM Plex Mono, auto-hospedadas desde
  `@fontsource` vía el plugin de fuentes de Vite (`vite.config.js`). No hay petición
  a un CDN de fuentes en tiempo de ejecución.

## Pruebas

```bash
php artisan test
./vendor/bin/pint
```
