{{--
    El rótulo que señala lo que cambió hace poco en `/actividades`: «Se agregó»
    sobre una entrada nueva de la Bitácora o un Pendiente recién capturado, «Se
    cumplió» sobre uno que se acaba de cerrar.

    Va en menta y no en Rojo Sello, que está reservado a alertas y urgencia
    (`resources/css/app.css`). Que algo haya cambiado no es un aviso.

    Con la palabra escrita y no con un punto de color: un punto hay que saber
    interpretarlo, y esta página la lee gente que entra dos veces al año. Sin
    `aria-hidden` por lo mismo — quien usa lector de pantalla también quiere
    saber cuál es la novedad.
--}}
<p {{ $attributes->class([
        'cifra mb-1.5 text-[0.625rem] font-semibold uppercase tracking-[0.12em] text-tinta',
    ]) }}>
    {{ $slot }}
</p>
