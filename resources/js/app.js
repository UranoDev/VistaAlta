//

/*
 * Renglones repetibles del formulario de /registro (lotes y contactos).
 *
 * El HTML de cada renglón lo arma el servidor; el <template> trae uno vacío con
 * `__I__` donde va el índice, y aquí solo se clona. Sin este script el formulario
 * sigue sirviendo con un lote, que es el mínimo.
 */
document.querySelectorAll('[data-repetible]').forEach((bloque) => {
    const items = bloque.querySelector('[data-items]');
    const plantilla = bloque.querySelector('template[data-plantilla]');
    const minimo = Number(bloque.dataset.minimo || 0);
    // Los títulos ordinales («Primer Lote») los trae el servidor, para no
    // escribirlos en dos lugares.
    const ordinales = bloque.dataset.ordinales ? JSON.parse(bloque.dataset.ordinales) : [];
    let siguiente = Number(bloque.dataset.siguiente || 0);

    const actualizar = () => {
        const filas = items.querySelectorAll('[data-item]');

        filas.forEach((fila, n) => {
            const numero = fila.querySelector('[data-numero]');
            if (numero) numero.textContent = String(n + 1);

            const ordinal = fila.querySelector('[data-ordinal]');
            if (ordinal) ordinal.textContent = ordinales[n] ?? `${n + 1}.º`;

            // Con el mínimo exacto no hay nada que quitar.
            fila.querySelector('[data-quitar]')?.classList.toggle('hidden', filas.length <= minimo);
        });
    };

    bloque.querySelector('[data-agregar]')?.addEventListener('click', () => {
        const html = plantilla.innerHTML.replaceAll('__I__', String(siguiente++));
        items.insertAdjacentHTML('beforeend', html);
        actualizar();

        items.lastElementChild?.querySelector('input:not([type=radio])')?.focus();
    });

    items.addEventListener('click', (evento) => {
        const quitar = evento.target.closest('[data-quitar]');
        if (!quitar) return;

        quitar.closest('[data-item]')?.remove();
        actualizar();
    });

    actualizar();
});

/*
 * Un doble toque en «Enviar» no debe mandar dos registros.
 */
document.querySelectorAll('form[data-una-vez]').forEach((formulario) => {
    formulario.addEventListener('submit', () => {
        formulario.querySelectorAll('button[type=submit]').forEach((boton) => {
            boton.disabled = true;
        });
    });
});

// Al volver con «atrás» el navegador puede devolver la página con el botón apagado.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-una-vez] button[type=submit]').forEach((boton) => {
        boton.disabled = false;
    });
});
