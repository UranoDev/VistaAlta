# Changelog

## [2026.10.06] - 2026 oct 06
_Sin issues cerrados en esta ventana_

## [2026.09.29.1] - 2026 sep 29
_Sin issues cerrados en esta ventana_

## [2026.09.29] - 2026 sep 29
### Fix / Bugs
- **URVA-108**: En producción las fechas salen en inglés: «Sunday 20 de September de 2026»
### Features
- **URVA-107**: Alberto H. entra al rol: el domingo se parte en dos y la página deja de contar vigilantes
- **URVA-11**: Publicar en vistaaltatx.com: vhost, SSL y Twilio en producción
- **URVA-94**: Decidir si la recepción de comentarios vuelve a OTP por SMS, ahora que el SMS entrega

## [2026.09.13.1] - 2026 sep 13
### Features
- **URVA-99**: La página de Administración presenta a los dos órganos y el trámite de la asociación civil

## [2026.09.13] - 2026 sep 13
### Fix / Bugs
- **URVA-88**: ContenidoInicialSeeder deja la Bitácora entera marcada «Se agregó»
- **URVA-85**: La fecha del saldo inicial se captura, se muestra y nunca se consulta
- **URVA-84**: Los importes de Egresos y Otros ingresos salen en formato de España
- **URVA-83**: Los mensajes de validación salen como claves crudas: «validation.required»
- **URVA-81**: La cuenta desactivada recibe «Estas credenciales no coinciden», que es mentira
### Features
- **URVA-98**: Sembrar el primer post de Convivencia: manejo de la basura
- **URVA-97**: Construir Convivencia: modelo, recurso de Filament, índice y página de post
- **URVA-95**: La portada cambia a Reporte financiero y el menú se reordena
- **URVA-92**: Doble punto en el motivo de cancelación del comprobante público
- **URVA-91**: El panel habla de usted y el sitio de tú
- **URVA-90**: En el modal de cancelación, «Cancelar» descarta y «Cancelar el recibo» ejecuta
- **URVA-89**: El interruptor inhabilitado del Comité de Vigilancia no se ve inhabilitado
- **URVA-87**: El modal de «Ya se hizo» precarga el título y publica frases en futuro
- **URVA-86**: El 403 es la página de Laravel sin vestir: en inglés y sin salida
- **URVA-82**: Reclamar la unidad no abre la sesión, y la pantalla no lo dice
- **URVA-80**: Pedir a los cuatro vigilantes su consentimiento, nombre y foto antes de publicarlos

## [2026.08.06.1] - 2026 ago 06
_Sin issues cerrados en esta ventana_

## [2026.08.06] - 2026 ago 06
### Features
- **URVA-79**: Página pública de Vigilancia: quién está de guardia ahora, sin publicar los horarios

## [2026.08.05] - 2026 ago 05
### Fix / Bugs
- **URVA-78**: Un día límite de pago fuera de 1–31 devuelve una fecha creíble en vez de reventar
- **URVA-76**: «Día límite de pago» reemplaza a «Días de gracia»: con 10, el 11 ya lleva sobrecargo
- **URVA-75**: Cortes de caja: las acciones que un rol no puede usar salen deshabilitadas en vez de no salir
- **URVA-67**: El clic en «Ver a qué se aplica» se pierde: el blur del importe re-renderiza el formulario antes del submit
- **URVA-66**: El primer clic en «Ver a qué se aplica» se pierde: el blur del importe borra la revisión recién hecha
- **URVA-47**: La siembra de contenido no debe pisar nada que ya exista en la base
- **URVA-58**: El OTP por SMS no llega a celulares de México: Twilio lo rechaza con error 30008
### Features
- **URVA-60**: Trámite ante los carriers para registrar el Alphanumeric Sender ID en México
- **URVA-77**: Estado de resultados en vivo: el mes que todavía no se rinde, para Mesa Directiva y Vigilancia
- **URVA-74**: El Cobrador puede cancelar sus recibos: la acción entra en «De qué cobros es ese dinero»
- **URVA-73**: Cortes de caja: ver más allá de los 20 cortes del histórico
- **URVA-72**: Cortes de caja: los cancelados se muestran desde el último corte, y la lista deja de recortarse
- **URVA-71**: Cortes de caja: afinar el renglón del Recibo — icono alineado al folio, sin la palabra «Cancelado», y Método que cede en celular
- **URVA-64**: Pagos adelantados: el colono al corriente puede pagar meses por venir
- **URVA-70**: Cortes de caja: los Recibos cancelados también se listan, marcados y sumados aparte
- **URVA-69**: Cancelar un Recibo desde el panel: hoy solo se puede por consola
- **URVA-68**: Cortes de caja: la lista de cobros que justifican el dinero en tránsito
- **URVA-65**: Registro de pago: el detalle dice en qué va la Unidad y hasta cuándo se paga sin sobrecargo
- **URVA-63**: Registro de pago: la lista de Unidades dice el estado de cobro antes de entrar
- **URVA-56**: Reemitir un mes publicado, dejando constancia de qué cambió
- **URVA-55**: Página de detalle pública: movimientos del mes y estado de cobranza
- **URVA-54**: Resumen derivado: retirar la captura de cifras y calcularlas
- **URVA-53**: Otros ingresos: entidad en espejo del Egreso
- **URVA-52**: Comprobante del Egreso: el primer archivo que este sitio carga
- **URVA-51**: Egresos: captura del gasto con categoría y proveedor
- **URVA-50**: Categorías y Rubros: catálogo administrable que se archiva, no se borra
- **URVA-49**: Glosario: Egreso, Otro ingreso, Categoría, Rubro y Detalle
- **URVA-43**: Panel de cobranza: Mesa Directiva ve todo, Comité de Vigilancia solo lee
- **URVA-42**: Portal del Colono: consultar su Unidad y sus recibos
- **URVA-48**: ADR: el sitio carga archivos, el reporte se deriva y un mes se puede reemitir
- **URVA-41**: Corte de caja: entrega del dinero del Cobrador a la Mesa Directiva
- **URVA-40**: Entrega del Recibo: WhatsApp, QR y correo si está confirmado
- **URVA-39**: Registro de pago del Cobrador, desde el celular y en el momento
- **URVA-38**: Recibo: folio, URL propia, QR y cancelación con motivo
- **URVA-62**: Terminar el renombre: las clases CSS siguen llamándose recibo-*
- **URVA-59**: El SMS del código no dice de quién viene: identificar a Vista Alta en el cuerpo
- **URVA-37**: Carga de adeudos hacia atrás por rango de meses
- **URVA-36**: Cuota: generación mensual, periodo de gracia y sobrecargo congelado
- **URVA-35**: Vigencias de cuota: historial de monto y sobrecargo por fecha
- **URVA-34**: Identidad del Colono: tres caminos de entrada, teléfono confirmado obligatorio
- **URVA-33**: Padrón: Unidad y Titularidad con vigencias
- **URVA-32**: Roles: spatie/laravel-permission con cuatro roles acumulables
- **URVA-30**: ADR: cuotas en urge sin tenancy, y el sistema manda sobre el ingreso por cuotas
- **URVA-28**: Legal: declarar en el Aviso y los Términos el tratamiento de datos para control de pagos

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

