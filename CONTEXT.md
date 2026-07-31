# urge

Sitio de rendición de cuentas de la Mesa Directiva de Vista Alta hacia su Asamblea:
publica la propuesta del periodo, las actividades realizadas y el reporte financiero,
y pide a los propietarios el comprobante de depósito de la administración pasada.
No es el portal operativo de colonos — aquí nadie paga cuotas ni consulta su estado
de cuenta.

## Language

**Asamblea**:
La reunión de colonos ante la que la Mesa Directiva presenta y somete a
consideración su propuesta y su rendición de cuentas. Es el evento al que apunta
cada edición del sitio.
_Avoid_: Junta, reunión de vecinos

**Mesa Directiva**:
El órgano de colonos que administra el fraccionamiento y publica en este sitio.
Son los únicos que se autentican: todo el resto del sitio es de lectura.
_Avoid_: Comité, administración, board

**Colono**:
Propietario o residente de una unidad en Vista Alta; el lector al que se dirige el
sitio. Deliberadamente **no** es una entidad del sistema: no tiene cuenta, no
inicia sesión y no existe padrón aquí.
_Avoid_: Vecino, socio, miembro, usuario

**Periodo**:
El tramo de tiempo que cubre una edición de la rendición de cuentas (p. ej. los
últimos tres meses). Agrupa las Actividades y el Reporte financiero que se
presentan juntos ante una Asamblea.
_Avoid_: Trimestre (el corte no siempre es trimestral), ejercicio

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

**Pendiente**:
Algo que la Mesa Directiva todavía no ha hecho, publicado en «Lo que sigue» —la
segunda mitad de `/actividades`, después de la Bitácora. Deliberadamente **no**
lleva fecha comprometida: varios dependen de un tercero que lleva su propio paso,
y publicar un plazo que no se controla es prometer de más. Tampoco se marca como
cumplido: el que se cumple se convierte en Actividad y se retira (acción «Ya se
hizo» del panel). Su orden es contenido, no capricho — el primero es del que
cuelgan los demás.
_Avoid_: Tarea, compromiso (sugiere el plazo que justamente no se da), meta,
objetivo. **Lo que sigue** es el rótulo de la sección en la página; la entidad se
llama Pendiente

**Reporte financiero**:
La rendición de cuentas económica de **un mes**: un resumen de cifras que el sitio
muestra, más el enlace a la hoja de cálculo de Google donde vive el detalle. El
resumen existe para ser mostrado; la hoja es la fuente de verdad y no se copia aquí.
Un mes, siempre — de ahí salen su título y su dirección, y de ahí que no se retire
nunca: cada mes rendido se queda publicado (ver `Histórico`).
_Avoid_: Estado de cuenta (eso es por colono, y no existe aquí), balance, corte de
caja. **Trimestral** — el Reporte no sigue al Periodo aunque lo respalde: las
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
las páginas legales— porque admiten adjuntos y aquí no se cargan archivos en
ninguna parte. Tampoco son una entidad del sistema — de ellos el sitio guarda un
solo dato, cuántos van, y se cuenta a mano.
_Avoid_: Recibo (aquí «Recibo» es el sistema visual), pago, aportación. **Demanda**
— es la ruta (`/demanda`) y nada más: en la interfaz es una petición de
comprobantes, no una acción legal, y el copy no afirma delitos ni señala personas

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
