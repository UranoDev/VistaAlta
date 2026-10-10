#!/usr/bin/env bash
# Despliegue de vistaaltatx.com. Se ejecuta en la raíz de la aplicación (la carpeta que
# contiene composer.json), no en public/.
#
# En el servidor, por SSH:      cd ~/httpdocs && git pull && bash deploy.sh
# En Plesk, Git > Acciones de despliegue adicionales:      bash deploy.sh
#
# Para probar el servidor SIN desplegar (revisa PHP, composer, extensiones, .env y npm, y
# no instala ni migra nada):      bash deploy.sh --revisar
#
# La siembra de contenido (ContenidoInicialSeeder) NO va aquí y no se corre contra
# producción: duplica lo que alguien editó en el panel (docs/deploy-plesk.md, sección 6).
set -euo pipefail

cd "$(dirname "$0")"

REVISAR=0
if [ "${1:-}" = "--revisar" ]; then
    REVISAR=1
fi

# Los binarios cambian de ruta entre instalaciones de Plesk: se resuelven, no se fijan.
# Además `composer` suele ser un shim de shell de phpenv, no un .phar, y `php` puede ser
# un shim sin versión elegida: se prueba que cada candidato realmente corra antes de
# quedarse con él.
first_working() {
    for candidate in "$@"; do
        command -v "$candidate" >/dev/null 2>&1 || continue
        "$candidate" --version >/dev/null 2>&1 || continue
        command -v "$candidate"
        return 0
    done
    echo "Ninguno de estos binarios corre: $*" >&2
    return 1
}

PHP="$(first_working php8.4 php8.3 /opt/plesk/php/8.4/bin/php /opt/plesk/php/8.3/bin/php php)"

# Un composer.phar casi nunca tiene permiso de ejecución, y aunque lo tenga, su shebang
# usaría el `php` del PATH, que en Plesk puede ser otra versión: un archivo PHP se corre
# con el $PHP de arriba. Solo un shim de shell (el de phpenv) se ejecuta directo. La ruta
# exacta cambia entre servidores Plesk; COMPOSER_BIN permite fijarla si ninguna sirve.
es_script_php() {
    case "$1" in *.phar) return 0 ;; esac
    head -n 1 "$1" 2>/dev/null | grep -q php
}

COMPOSER=""
for candidato in ${COMPOSER_BIN:-} composer composer.phar \
        /usr/lib/plesk-9.0/composer.phar \
        /usr/local/psa/var/modules/composer/composer.phar \
        /opt/psa/var/modules/composer/composer.phar \
        /usr/local/bin/composer \
        "${HOME:-}/.composer/composer.phar"; do
    ruta="$(command -v "$candidato" 2>/dev/null || true)"
    if [ -z "$ruta" ] && [ -f "$candidato" ]; then
        ruta="$candidato"
    fi
    [ -n "$ruta" ] || continue

    if es_script_php "$ruta"; then
        "$PHP" "$ruta" --version >/dev/null 2>&1 || continue
    else
        "$ruta" --version >/dev/null 2>&1 || continue
    fi

    COMPOSER="$ruta"
    break
done

if [ -z "$COMPOSER" ]; then
    echo "ERROR: no se encontró un composer que corra." >&2
    echo "Para fijarlo: COMPOSER_BIN=/ruta/a/composer.phar bash deploy.sh" >&2
    exit 1
fi

if es_script_php "$COMPOSER"; then
    composer_run() { "$PHP" "$COMPOSER" "$@"; }
else
    composer_run() { "$COMPOSER" "$@"; }
fi

echo "PHP:      $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"
echo "Composer: $COMPOSER"

# Laravel 13 pide PHP 8.3 o más. Sin esta revisión, composer falla con un error de
# plataforma que no dice cuál PHP agarró el despliegue.
if ! "$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
    echo "ERROR: este PHP es $("$PHP" -r 'echo PHP_VERSION;') y Laravel 13 pide 8.3 o más." >&2
    exit 1
fi

# Extensiones sin las cuales algo truena en producción y no aquí: pdo_mysql (la base),
# mbstring y openssl (Laravel), curl (el SMS), fileinfo y zip (el ZIP de datos:entregar).
FALTAN=""
for extension in pdo_mysql mbstring openssl curl fileinfo zip; do
    if ! "$PHP" -m | grep -qi "^${extension}$"; then
        FALTAN="$FALTAN $extension"
    fi
done
if [ -n "$FALTAN" ]; then
    echo "ERROR: al PHP del despliegue le faltan extensiones:$FALTAN" >&2
    exit 1
fi

# El .env no viaja en git. Sin él no hay APP_KEY y cada petición truena.
if [ ! -f .env ]; then
    echo "ERROR: falta el .env en la raíz de la aplicación." >&2
    exit 1
fi

# Avisos, no errores: el sitio corre, pero con algo que casi seguro no se quería.
valor_de_env() {
    grep -E "^$1=" .env | tail -n 1 | cut -d= -f2- | tr -d "\"' \r"
}

if [ "$(valor_de_env APP_ENV)" != "production" ]; then
    echo "AVISO: APP_ENV no es «production» (es «$(valor_de_env APP_ENV)»): /sistema-visual queda publicada." >&2
fi
if [ "$(valor_de_env APP_DEBUG)" = "true" ]; then
    echo "AVISO: APP_DEBUG=true: cualquier error enseña el stack trace a quien entre al sitio." >&2
fi
case "$(valor_de_env MAIL_MAILER)" in
    ""|log|array)
        echo "AVISO: MAIL_MAILER es «$(valor_de_env MAIL_MAILER)»: los correos de verificación no le llegan a nadie." >&2
        ;;
esac
case "$(valor_de_env OTP_CHANNEL)" in
    twilio) ;;
    *)
        echo "AVISO: OTP_CHANNEL es «$(valor_de_env OTP_CHANNEL)», no «twilio»: los SMS no le llegan a nadie." >&2
        ;;
esac

# Assets. Esto no se puede omitir: si no se compilan, la aplicación sigue sirviendo el
# CSS del despliegue anterior y el fallo es invisible —las pantallas cargan, solo que con
# los estilos viejos—. Por eso el despliegue falla en vez de saltarse el build.
if [ ! -f package-lock.json ]; then
    echo "ERROR: falta package-lock.json, no se puede compilar con npm ci." >&2
    exit 1
fi

# Plesk corre el despliegue con un PATH más corto que el de una sesión SSH: los shims de
# nodenv no vienen, así que `npm` no existe aunque en la terminal sí responda. Tampoco
# sirve llamar a `nodenv`, que en estos servidores es una función del perfil del shell y
# no un programa. Se agregan los shims a mano.
if ! command -v npm >/dev/null 2>&1; then
    for shims in "${HOME:-}/.nodenv/shims" "$(dirname "$PWD")/.nodenv/shims"; do
        if [ -x "$shims/npm" ]; then
            PATH="$shims:$PATH"
            export PATH
            echo "nodenv:   shims agregados al PATH desde $shims"
            break
        fi
    done
fi

if ! command -v npm >/dev/null 2>&1; then
    echo "ERROR: npm no está disponible para este despliegue." >&2
    echo "No se encontró en el PATH ni en los shims de nodenv del vhost." >&2
    echo "El .node-version del repo pide: $(cat .node-version 2>/dev/null || echo '(no está)')" >&2
    echo "Revisar con: nodenv versions" >&2
    exit 1
fi

echo "Node:     $(node --version 2>/dev/null || echo 'desconocida')"
echo "npm:      $(npm --version)"

if [ "$REVISAR" -eq 1 ]; then
    echo "Revisión terminada: este servidor puede desplegar. No se instaló ni se migró nada."
    exit 0
fi

# Git no versiona carpetas vacías: si el clon llegó sin el esqueleto de storage, artisan
# falla con "Please provide a valid cache path" antes de hacer nada.
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         storage/app/entregas \
         bootstrap/cache
chmod -R u+rwX,g+rwX storage bootstrap/cache

composer_run install --no-interaction --prefer-dist --no-dev --optimize-autoloader

npm ci
npm run build

# `npm run dev` deja este archivo y Laravel pide los assets a un Vite que en el servidor no
# existe.
rm -f public/hot

if [ ! -f public/build/manifest.json ]; then
    echo "ERROR: el build terminó sin dejar public/build/manifest.json." >&2
    exit 1
fi

"$PHP" artisan storage:link --force
"$PHP" artisan migrate --force

# Limpia y rehace los cachés de configuración, rutas, vistas y eventos. Con la
# configuración cacheada el .env deja de leerse: un cambio en él no surte efecto hasta
# volver a correr esto.
"$PHP" artisan optimize

echo "Despliegue terminado."
echo "Versión publicada: $(cat VERSION 2>/dev/null || echo 'desconocida')"
echo "Assets compilados:"
ls public/build/assets 2>/dev/null | sed 's/^/  /'
