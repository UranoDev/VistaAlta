# Changelog

## [Unreleased]
### Fix / Bugs
- **URVA-88**: ContenidoInicialSeeder deja la Bitácora entera marcada «Se agregó» _(cerrado 2026-08-12 18:21)_
- **URVA-85**: La fecha del saldo inicial se captura, se muestra y nunca se consulta _(cerrado 2026-08-12 17:55)_
- **URVA-84**: Los importes de Egresos y Otros ingresos salen en formato de España _(cerrado 2026-08-12 17:46)_
- **URVA-83**: Los mensajes de validación salen como claves crudas: «validation.required» _(cerrado 2026-08-12 17:39)_
- **URVA-81**: La cuenta desactivada recibe «Estas credenciales no coinciden», que es mentira _(cerrado 2026-08-12 17:23)_
- **URVA-78**: Un día límite de pago fuera de 1–31 devuelve una fecha creíble en vez de reventar _(cerrado 2026-08-04 15:24)_
- **URVA-76**: «Día límite de pago» reemplaza a «Días de gracia»: con 10, el 11 ya lleva sobrecargo _(cerrado 2026-08-04 14:48)_
- **URVA-75**: Cortes de caja: las acciones que un rol no puede usar salen deshabilitadas en vez de no salir _(cerrado 2026-08-04 11:52)_
- **URVA-67**: El clic en «Ver a qué se aplica» se pierde: el blur del importe re-renderiza el formulario antes del submit _(cerrado 2026-08-04 01:10)_
- **URVA-66**: El primer clic en «Ver a qué se aplica» se pierde: el blur del importe borra la revisión recién hecha _(cerrado 2026-08-03 23:42)_
- **URVA-47**: La siembra de contenido no debe pisar nada que ya exista en la base _(cerrado 2026-08-02 22:20)_
### Features
- **URVA-92**: Doble punto en el motivo de cancelación del comprobante público _(cerrado 2026-08-12 18:49)_
- **URVA-91**: El panel habla de usted y el sitio de tú _(cerrado 2026-08-12 18:44)_
- **URVA-90**: En el modal de cancelación, «Cancelar» descarta y «Cancelar el recibo» ejecuta _(cerrado 2026-08-12 18:32)_
- **URVA-89**: El interruptor inhabilitado del Comité de Vigilancia no se ve inhabilitado _(cerrado 2026-08-12 18:27)_
- **URVA-87**: El modal de «Ya se hizo» precarga el título y publica frases en futuro _(cerrado 2026-08-12 18:12)_
- **URVA-86**: El 403 es la página de Laravel sin vestir: en inglés y sin salida _(cerrado 2026-08-12 18:07)_
- **URVA-82**: Reclamar la unidad no abre la sesión, y la pantalla no lo dice _(cerrado 2026-08-12 17:30)_
- **URVA-80**: Pedir a los cuatro vigilantes su consentimiento, nombre y foto antes de publicarlos _(cerrado 2026-08-12 17:04)_
- **URVA-77**: Estado de resultados en vivo: el mes que todavía no se rinde, para Mesa Directiva y Vigilancia _(cerrado 2026-08-04 15:01)_
- **URVA-74**: El Cobrador puede cancelar sus recibos: la acción entra en «De qué cobros es ese dinero» _(cerrado 2026-08-04 10:15)_
- **URVA-73**: Cortes de caja: ver más allá de los 20 cortes del histórico _(cerrado 2026-08-04 09:48)_
- **URVA-72**: Cortes de caja: los cancelados se muestran desde el último corte, y la lista deja de recortarse _(cerrado 2026-08-04 09:41)_
- **URVA-71**: Cortes de caja: afinar el renglón del Recibo — icono alineado al folio, sin la palabra «Cancelado», y Método que cede en celular _(cerrado 2026-08-04 09:28)_
- **URVA-64**: Pagos adelantados: el colono al corriente puede pagar meses por venir _(cerrado 2026-08-04 09:21)_
- **URVA-70**: Cortes de caja: los Recibos cancelados también se listan, marcados y sumados aparte _(cerrado 2026-08-04 01:54)_
- **URVA-69**: Cancelar un Recibo desde el panel: hoy solo se puede por consola _(cerrado 2026-08-04 01:38)_
- **URVA-68**: Cortes de caja: la lista de cobros que justifican el dinero en tránsito _(cerrado 2026-08-04 01:18)_
- **URVA-65**: Registro de pago: el detalle dice en qué va la Unidad y hasta cuándo se paga sin sobrecargo _(cerrado 2026-08-03 23:54)_
- **URVA-63**: Registro de pago: la lista de Unidades dice el estado de cobro antes de entrar _(cerrado 2026-08-03 23:14)_
- **URVA-56**: Reemitir un mes publicado, dejando constancia de qué cambió _(cerrado 2026-08-03 12:23)_
- **URVA-55**: Página de detalle pública: movimientos del mes y estado de cobranza _(cerrado 2026-08-03 11:59)_
- **URVA-54**: Resumen derivado: retirar la captura de cifras y calcularlas _(cerrado 2026-08-03 11:40)_
- **URVA-53**: Otros ingresos: entidad en espejo del Egreso _(cerrado 2026-08-03 11:17)_
- **URVA-52**: Comprobante del Egreso: el primer archivo que este sitio carga _(cerrado 2026-08-03 11:07)_
- **URVA-51**: Egresos: captura del gasto con categoría y proveedor _(cerrado 2026-08-03 10:44)_
- **URVA-50**: Categorías y Rubros: catálogo administrable que se archiva, no se borra _(cerrado 2026-08-03 10:32)_
- **URVA-49**: Glosario: Egreso, Otro ingreso, Categoría, Rubro y Detalle _(cerrado 2026-08-03 10:19)_
- **URVA-43**: Panel de cobranza: Mesa Directiva ve todo, Comité de Vigilancia solo lee _(cerrado 2026-08-03 02:15)_
- **URVA-42**: Portal del Colono: consultar su Unidad y sus recibos _(cerrado 2026-08-03 02:02)_
- **URVA-48**: ADR: el sitio carga archivos, el reporte se deriva y un mes se puede reemitir _(cerrado 2026-08-03 01:42)_
- **URVA-41**: Corte de caja: entrega del dinero del Cobrador a la Mesa Directiva _(cerrado 2026-08-03 01:37)_
- **URVA-40**: Entrega del Recibo: WhatsApp, QR y correo si está confirmado _(cerrado 2026-08-03 01:16)_
- **URVA-39**: Registro de pago del Cobrador, desde el celular y en el momento _(cerrado 2026-08-03 00:56)_
- **URVA-38**: Recibo: folio, URL propia, QR y cancelación con motivo _(cerrado 2026-08-03 00:30)_
- **URVA-62**: Terminar el renombre: las clases CSS siguen llamándose recibo-* _(cerrado 2026-08-03 00:08)_
- **URVA-37**: Carga de adeudos hacia atrás por rango de meses _(cerrado 2026-08-02 23:59)_
- **URVA-36**: Cuota: generación mensual, periodo de gracia y sobrecargo congelado _(cerrado 2026-08-02 22:14)_
- **URVA-35**: Vigencias de cuota: historial de monto y sobrecargo por fecha _(cerrado 2026-08-02 22:02)_
- **URVA-34**: Identidad del Colono: tres caminos de entrada, teléfono confirmado obligatorio _(cerrado 2026-08-02 21:49)_
- **URVA-33**: Padrón: Unidad y Titularidad con vigencias _(cerrado 2026-08-02 21:23)_
- **URVA-32**: Roles: spatie/laravel-permission con cuatro roles acumulables _(cerrado 2026-08-02 21:01)_
- **URVA-30**: ADR: cuotas en urge sin tenancy, y el sistema manda sobre el ingreso por cuotas _(cerrado 2026-08-02 20:38)_
- **URVA-28**: Legal: declarar en el Aviso y los Términos el tratamiento de datos para control de pagos _(cerrado 2026-08-02 20:29)_

## [2026.09.13.1] - 2026 sep 13
### Features
- **URVA-99**: La página de Administración presenta a los dos órganos y el trámite de la asociación civil

## [2026.09.13] - 2026 sep 13
### Features
- **URVA-98**: Sembrar el primer post de Convivencia: manejo de la basura
- **URVA-97**: Construir Convivencia: modelo, recurso de Filament, índice y página de post
- **URVA-95**: La portada cambia a Reporte financiero y el menú se reordena

## [2026.08.06.1] - 2026 ago 06
_Sin issues cerrados en esta ventana_

## [2026.08.06] - 2026 ago 06
### Features
- **URVA-79**: Página pública de Vigilancia: quién está de guardia ahora, sin publicar los horarios

## [2026.08.05] - 2026 ago 05
### Fix / Bugs
- **URVA-58**: El OTP por SMS no llega a celulares de México: Twilio lo rechaza con error 30008
### Features
- **URVA-60**: Trámite ante los carriers para registrar el Alphanumeric Sender ID en México
- **URVA-59**: El SMS del código no dice de quién viene: identificar a Vista Alta en el cuerpo

## [2026.08.02] - 2026 ago 02
### Features
- **URVA-29**: Glosario: reescribir CONTEXT.md y README para un sitio con padrón, cuentas y cuotas
- **URVA-31**: Renombrar el sistema visual «Recibo» a «Palette Receipt»

## [2026.07.30.5] - 2026 jul 30
### Features
- **URVA-26**: Interruptor en el panel para recibir Comentarios por OTP o por WhatsApp

## [2026.07.30.4] - 2026 jul 30
_Sin issues cerrados en esta ventana_

## [2026.07.30.3] - 2026 jul 30
_Sin issues cerrados en esta ventana_

## [2026.07.30.2] - 2026 jul 30
### Features
- **URVA-25**: «Lo que sigue»: mover los pendientes de la vista a la base y darles pantalla en el panel
- **URVA-24**: Histórico de Reportes financieros: un reporte por mes, con URL propia y archivo consultable

## [2026.07.30.1] - 2026 jul 30
### Features
- **URVA-23**: Panel del Reporte financiero: botón arriba que abra la página pública en una pestaña nueva
- **URVA-22**: Runbook de despliegue: pasar de SQLite a MariaDB 10.5 en producción
- **URVA-21**: Página Propuesta: sección «Lo que necesitamos de ti» con el Comité de Supervisión, enlazada desde el encabezado
- **URVA-20**: Reporte financiero: aclaración del periodo, para que un ingreso extraordinario no se lea como el excedente normal

## [2026.07.30] - 2026 jul 30
_Sin issues cerrados en esta ventana_

## [2026.07.29.1] - 2026 jul 29
_Sin issues cerrados en esta ventana_

## [2026.07.29] - 2026 jul 29
### Features
- **URVA-19**: Separar el buzón de comprobantes del institucional: dos llaves en config y comprobantes@ en /demanda
- **URVA-18**: Página Demanda: renombrarla, encabezarla con el motivo, resaltar el comprobante y quitarle los enlaces internos
- **URVA-17**: Páginas legales: Aviso de Privacidad y Términos de Servicio, portados de nvavista y recortados a lo que este sitio sí hace
- **URVA-16**: Página /demanda: pedir a los propietarios el comprobante de depósito de la administración pasada
- **URVA-15**: Bitácora de Actividades: no repetir la fecha en actividades del mismo día
- **URVA-14**: Una sola pantalla de Comentarios en el panel: fundir Cola de moderación, Comentarios privados y el interruptor de Recepción
- **URVA-13**: Usar otro número: salir de la pantalla de código sin esperar a que expire la sesión
- **URVA-9**: Página Reporte financiero: resumen de cifras y enlace a Google Sheets
- **URVA-8**: Página Actividades: lista con fechas y su CRUD en el panel
- **URVA-7**: Panel de la Mesa Directiva: Cola de moderación e interruptor de Recepción de comentarios
- **URVA-6**: Página Propuesta: video, preguntas frecuentes y formulario de comentarios con OTP
- **URVA-5**: Límite de envío de OTP: 3 intentos por ventana de 10 minutos
- **URVA-4**: Modelo Comentario: visibilidad del autor y estado de moderación
- **URVA-3**: Portar OtpService y TwilioOtpSender desde nvavista, sin tenancy
- **URVA-2**: Bootstrap del proyecto Laravel + sistema visual "Recibo"

