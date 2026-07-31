# El Reporte financiero se conserva mes por mes, y el histórico queda público

Un Reporte financiero cubre **un mes**, y cada mes rendido se queda publicado con su
propia dirección (`/reporte-financiero/2026-06`). `/reporte-financiero` muestra siempre
el más reciente; los anteriores cascadean solos al archivo conforme se captura uno
nuevo. Ninguno se retira ni se corrige hacia atrás.

Antes era una tabla de un solo renglón: capturar julio borraba junio. En un sitio cuyo
propósito entero es la rendición de cuentas, eso dejaba a la Asamblea sin nada que
consultar hacia atrás y sin forma de comparar un mes con otro — justo lo que se le
pide que juzgue.

Que un reporte cubra siempre un mes es lo que destraba el diseño. Mientras `periodo`
fue texto libre (`'Abril – Junio 2026'`) no había de dónde sacar la URL ni cómo
ordenar el archivo. Con el mes estructurado, la etiqueta legible se **deriva** de él en
vez de capturarse aparte: el título y la dirección ya no pueden contradecirse.

## Consecuencias

`docs/adr/0004` dejó público, sin ninguna barrera, **un** reporte. Con histórico
quedan públicos para siempre **todos**: las finanzas de cada mes del fraccionamiento
son consultables e indexables desde su propia URL, y borrar un mes es un acto
deliberado desde el panel, no un efecto de capturar el siguiente. Es coherente con la
postura del sitio —la transparencia es el argumento, no una concesión—, pero se
escribe aquí para que sea una decisión tomada y no una consecuencia lateral de una
mejora de almacenamiento.

El mes vigente se sirve en dos direcciones —la raíz y la suya con fecha—. Se resuelve
con `<link rel="canonical">` apuntando a la raíz, no con una redirección: la URL con
fecha tiene que seguir funcionando igual el día que ese mes deje de ser el vigente, y
una redirección la haría comportarse distinto según el calendario. Quien guarde el
enlace de junio quiere junio, hoy y en diciembre.

El archivo invita a comparar meses, y el sitio **no** compara: no hay comparativos ni
gráficas, solo conservación. La hoja de cálculo de cada mes sigue siendo su fuente de
verdad y el resumen se sigue capturando a mano.
