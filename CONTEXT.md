# urge

Sitio de la Mesa Directiva de Vista Alta, con dos mitades que conviene no confundir.

La **rendición de cuentas** hacia la Asamblea: publica la propuesta del periodo, las
actividades realizadas y el reporte financiero, y pide a los propietarios el
comprobante de depósito de la administración pasada. Se lee sin identificarse — ahí
el sitio se dirige al Colono, no lo registra.

El **control de cuotas**: el padrón de unidades, lo que cada una debe mes con mes, y
los recibos de lo que se cobra. Aquí sí hay cuentas, sesión y dinero, y el Colono sí
es una entidad del sistema.

## Language

**Asamblea**:
La reunión de colonos ante la que la Mesa Directiva presenta y somete a
consideración su propuesta y su rendición de cuentas. Es el evento al que apunta
cada edición del sitio.
_Avoid_: Junta, reunión de vecinos

**Mesa Directiva**:
El órgano de colonos que administra el fraccionamiento y publica en este sitio. Es
quien redacta todo lo que se publica y quien decide qué se cobra; ya no es el único
rol que se autentica —el Colono también entra a ver lo suyo, y el Cobrador a
capturar—, pero sigue siendo el único que publica.
_Avoid_: Comité (además se presta a confusión con el `Comité de Vigilancia`, que es
otro órgano y otro rol), administración, board

**Colono**:
Propietario o residente de una Unidad en Vista Alta. En las páginas de rendición de
cuentas es el lector al que el sitio **se dirige**, y ahí no se le registra ni se le
pide identificarse: esa mitad se lee entera sin cuenta. En el control de cuotas sí es
una entidad del sistema — está en el padrón, tiene cuenta, inicia sesión y consulta
lo que se le cobra. Lo que **no** hace nunca es deber: el sujeto de cobro es la
Unidad, no la persona.
_Avoid_: Vecino, socio, miembro, usuario

**Periodo**:
El tramo de tiempo que cubre una edición de la rendición de cuentas (p. ej. los
últimos tres meses). Agrupa las Actividades y el Reporte financiero que se
presentan juntos ante una Asamblea. **Es de la rendición de cuentas y de nada más**:
no manda sobre el cobro ni se cruza con él.
_Avoid_: Trimestre (el corte no siempre es trimestral), ejercicio. **Cuota** — el mes
que cubre una Cuota **no** es un Periodo: son dos relojes distintos, uno agrupa lo
que se presenta ante la Asamblea y el otro es lo que se debe. Ese mes no se llama
Periodo en ninguna parte, y el nombre `CuotaPeriodo` que trae nvavista **no se
porta** — al portar el código hay que renombrarlo

**Propuesta**:
El planteamiento que la Mesa Directiva somete a consideración de la Asamblea:
**formalizar** el fraccionamiento como figura legal. Es un único asunto, no una lista
de obras, y es el eje del sitio — las Actividades y el Reporte financiero existen para
darle respaldo, y los Comentarios se hacen sobre ella.
_Avoid_: Plan, iniciativa, proyecto, propuestas (en plural — aquí solo hay una)

**Actividad**:
Algo que la Mesa Directiva llevó a cabo durante el Periodo, con su fecha, publicado
como evidencia de gestión. Es una entrada que se lee en la propia página — no hay
documento adjunto que la respalde. Deliberadamente **no** lleva costo: el dinero se
rinde únicamente en el Reporte financiero, para no publicar dos cuentas del mismo
gasto.
_Avoid_: Publicación (en nvavista es otra cosa), evento, obra, tarea

**Actividades Pendientes**:
Algo que la Mesa Directiva todavía no ha hecho, publicado en «Lo que sigue» —la
segunda mitad de `/actividades`, después de la Bitácora. Deliberadamente **no**
lleva fecha comprometida: varios dependen de un tercero que lleva su propio paso,
y publicar un plazo que no se controla es prometer de más. Tampoco se marca como
cumplido: el que se cumple se convierte en Actividad y se retira (acción «Ya se
hizo» del panel). Su orden es contenido, no capricho — el primero es del que
cuelgan los demás.
_Avoid_: Tarea, compromiso (sugiere el plazo que justamente no se da), meta,
objetivo. **Lo que sigue** es el rótulo de la sección en la página; la entidad se
llama ActividadPendiente

**Reporte financiero**:
La rendición de cuentas económica de **un mes**: un resumen de cifras que el sitio
muestra, más el enlace a la hoja de cálculo de Google donde vive el detalle. El
resumen existe para ser mostrado; la hoja es la fuente de verdad y no se copia aquí.
Un mes, siempre — de ahí salen su título y su dirección, y de ahí que no se retire
nunca: cada mes rendido se queda publicado (ver `Histórico`).
_Avoid_: Balance. **Estado de cuenta** — sí existe, y es otra cosa: es lo que una
Unidad debe, se consulta por Colono con su sesión y no se presenta ante nadie; el
Reporte financiero es del fraccionamiento entero y es público sin barrera. **Corte de
caja** — también existe y es más chico todavía: la entrega de un Cobrador (ver `Corte
de caja`). **Trimestral** — el Reporte no sigue al Periodo aunque lo respalde: las
Actividades se agrupan por Periodo, el dinero se rinde mes por mes

**Histórico**:
Los meses ya rendidos, que siguen publicados con su propia dirección
(`/reporte-financiero/2026-06`). No es un archivo muerto ni una copia: es el mismo
Reporte financiero de ese mes, tal como se rindió, y nunca se corrige hacia atrás
para que cuadre con otro. `/reporte-financiero` publica siempre el mes más reciente
y los anteriores cascadean solos (`docs/adr/0005`).
_Avoid_: Archivo (sugiere que se retiró de circulación), versiones anteriores (no
son versiones de un mismo reporte, son meses distintos)

**Comprobante de depósito**:
La constancia de que un propietario depositó a la administración anterior a esta
Mesa Directiva. El sitio los **pide**, no los recibe: llegan por correo a
`comprobantes@vistaaltatx.com` —buzón propio, aparte del institucional que citan
las páginas legales— porque admiten adjuntos y en el sitio no hay ninguna pantalla
donde un Colono pueda cargar un archivo. Tampoco son una entidad del sistema — de
ellos el sitio guarda un solo dato, cuántos van, y se cuenta a mano.
_Avoid_: Pago, aportación. **Demanda** — es la ruta (`/demanda`) y nada más: en la
interfaz es una petición de comprobantes, no una acción legal, y el copy no afirma
delitos ni señala personas. **Es el término que más fácil se confunde de todo el
glosario**, porque al hablar hay tres papeles que se llaman «comprobante» y son cosas
distintas: éste es dinero que un propietario entregó a la administración **anterior**
y que este sitio ni cobró ni guarda; el `Recibo` es dinero que entra hoy y que el
sitio sí emite; y el comprobante de un gasto de la Mesa Directiva —que todavía no es
una entidad de este glosario— es dinero que sale. Al escribir, nombrar cuál de los
tres

## Participación

**Comentario**:
Pregunta o señalamiento que un visitante deja sobre la Propuesta. Nace ligado al
Teléfono validado que lo escribió y a la elección de su autor de hacerlo público o
privado.
_Avoid_: Feedback, mensaje, ticket

**Comentario público**:
El que su autor marcó para que lo lea la Asamblea. Es el único tipo que puede llegar
a aparecer en el sitio, y solo después de pasar por la Cola de moderación. En el
panel se lista junto a los privados, pero es el único que se puede publicar.
_Avoid_: Comentario abierto, visible

**Comentario privado**:
El que su autor dirigió únicamente a la Mesa Directiva. Nunca se publica: la elección
del autor es definitiva y la Mesa Directiva no puede volverlo público. Comparte lista
con los públicos en el panel, y ahí no lleva casilla de selección ni acción de
publicar — la garantía es por registro, no por estar en otra pantalla.
_Avoid_: Comentario oculto, confidencial (no es una clasificación que la Mesa
Directiva asigne — la pone el autor y lo obliga)

**Teléfono validado**:
Número celular que completó un OTP y con ello quedó habilitado para comentar.
Acredita que del otro lado hay una persona alcanzable — **no** acredita que sea
colono.
_Avoid_: Usuario, cuenta, teléfono verificado (sugiere una verificación de identidad
que no ocurre)

**Ventana de validación**:
Los 30 minutos que siguen a un OTP exitoso, durante los cuales ese teléfono puede
comentar sin volver a validarse. Al expirar no se pierde nada de lo publicado: solo
se exige un OTP nuevo para volver a escribir.
_Avoid_: Sesión (no hay sesión de usuario aquí), login temporal

**Recepción de comentarios**:
El interruptor que la Mesa Directiva controla desde el encabezado de la pantalla de
Comentarios para dejar de admitir Comentarios nuevos. Nace abierta y así se queda
hasta que alguien la cierre; cerrarla no oculta nada de lo ya publicado, solo retira
el formulario.
_Avoid_: Cierre, archivado, moderación (la Cola de moderación decide **qué se
publica**; esto decide **si se puede escribir** — son cosas distintas)

**Vía de recepción**:
Por dónde llegan los Comentarios cuando sí se admiten: escritos en el sitio con
el celular validado por SMS (`otp`), o recibidos por WhatsApp (`whatsapp`). La
mueve la Mesa Directiva desde la misma pantalla del panel, debajo de la Recepción
de comentarios, que manda sobre ella —cerrada, la vía no se usa—. La diferencia
que importa no es el canal sino **quién elige la visibilidad**: en `otp` la elige
el autor y queda fija; en `whatsapp` la captura la Mesa Directiva, y por eso el
mensaje del enlace se la pide al autor por escrito.
_Avoid_: Canal, modo, método. **Recepción de comentarios** — ése es el apagador
(¿se admiten, sí o no?), éste es el selector (¿por dónde?); confundirlos es el
error fácil

**Cola de moderación**:
El conjunto de Comentarios públicos que ya fueron escritos pero que todavía no
aparecen en el sitio. Ningún comentario público existe de cara a la Asamblea hasta
que la Mesa Directiva lo **publica**. En el panel es la pestaña de entrada de la
pantalla de Comentarios, la única que hay: ahí conviven en una sola lista los
públicos y los privados, y ahí mismo está el interruptor de Recepción de
comentarios.
_Avoid_: Pendientes, borradores (el autor ya terminó de escribir; lo que falta no es
suyo sino que se publique). **Liberar** — la acción se llama publicar, en la
interfaz y en el código (`Comentario::publicar()`)

## Padrón

**Unidad**:
El lote o la casa. **Es el sujeto de cobro: la Unidad debe, no la persona.** Quien
tiene tres lotes tiene tres adeudos que no se juntan y paga tres veces, y cuando la
Unidad cambia de dueño la deuda se queda donde está.
_Avoid_: Casa (se usa al hablar, no en el código ni en la interfaz), propiedad, lote

**Sección**:
La división del fraccionamiento a la que pertenece una Unidad. El nombre de la Unidad
es único **por Sección**, no en todo el padrón: puede haber «Casa 14» en dos
secciones. Nombrar una Unidad sin su Sección es ambiguo, así que en listas, búsquedas
y Recibos van siempre juntas.
_Avoid_: Etapa, manzana, privada (se usan al hablar; la entidad es Sección)

**Titularidad**:
El tramo de tiempo en que un Colono fue dueño de una Unidad. Cada Cuota guarda quién
era el titular al generarse, **sin mover la deuda de lugar**: sirve para saber a quién
se le cobró, no para cambiar quién debe — quien debe sigue siendo la Unidad. Por eso
hay historial y no un solo dueño actual.
_Avoid_: Propietario (es la persona, no el tramo que la liga a una Unidad), asignación

## Cobranza

**Cuota**:
Lo que una Unidad debe por un mes. Nace con monto, sobrecargo y vencimiento
**congelados**: los copia de la Vigencia de cuota que estaba al aire cuando se generó,
y corregir esa vigencia después no la recalcula, ni siquiera si sigue pendiente.
_Avoid_: Mensualidad. **Periodo** — el mes de una Cuota no es un Periodo (ver
`Periodo`), y el nombre `CuotaPeriodo` de nvavista no se porta

**Vigencia de cuota**:
Desde qué fecha aplican cierto monto, cierto sobrecargo y ciertos días de gracia. Es
una tabla con historial, no un valor que se sobrescribe: un adeudo de 2024 se cobra al
precio de 2024. El monto es **único** para todo el fraccionamiento — no hay
excepciones por Unidad.
_Avoid_: Tarifa, configuración (sugieren un valor actual que se edita; aquí cambiar de
precio es **agregar** una vigencia, no editar la anterior)

**Sobrecargo**:
Importe que se suma a una Cuota al vencer su gracia. **Se aplica una sola vez y no
crece.** Es deliberado: una deuda que crece sola llega a un número que nadie va a
pagar nunca, y entonces deja de cobrarse.
_Avoid_: Interés moratorio, recargo acumulado (justo lo que no es), multa

**Cobrador**:
Quien recibe el dinero, lo registra en el momento desde su teléfono y emite el Recibo.
Entre que cobra y entrega trae dinero del fraccionamiento en la mano; eso se salda en
el Corte de caja, y un faltante lo repone él.
_Avoid_: Gestor, recaudador

**Recibo**:
El comprobante de un pago: qué Unidad pagó, qué cubre y quién lo cobró. **Un Recibo,
una Unidad.** Toma el nombre que hasta hoy tenía el sistema visual, que por eso se
renombró a «Palette Receipt» (URVA-31) — el nombre quedó libre para lo que significa
en la calle.
_Avoid_: Pago (el Recibo es el documento, no el hecho). **Comprobante de depósito** —
eso es lo de `/demanda`, de la administración anterior, y este sitio ni lo emite ni lo
guarda (ver ahí: son tres papeles distintos)

**Corte de caja**:
La entrega del dinero de un Cobrador a la Mesa Directiva. Cierra lo que el Cobrador
trae en la mano desde el corte pasado; lo que falte es deuda suya y no toca ningún
Reporte financiero.
_Avoid_: Arqueo, depósito

## Roles

Se **acumulan**: sumarle un rol a alguien abre lo que ese rol abre y nunca le quita lo
que ya podía hacer. `Mesa Directiva`, `Cobrador` y `Colono` están definidos arriba,
donde se explica qué hace cada uno; aquí va el que no aparece en ninguna otra parte.

**Comité de Vigilancia**:
Rol que ve todo y **no modifica nada**. Existe para poder revisar sin poder tocar —
padrón, cuotas, recibos y cortes de caja se le abren completos, y ninguna acción de
escritura. Que el rol se acumule no lo contradice: no restringe a nadie, solo abre
lectura.
_Avoid_: Auditoría, contraloría
