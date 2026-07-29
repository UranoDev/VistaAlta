# Puesta en producción — vistaaltatx.com sobre Plesk

Runbook del primer despliegue y de los subsecuentes. El dominio y el porqué de
servirlo pelón están en `docs/adr/0002`.

La aplicación es más simple de operar de lo que suele ser un Laravel: **no tiene
tareas programadas ni jobs en cola** (`routes/console.php` está en su estado por
omisión y no hay una sola clase `ShouldQueue`), así que **no hace falta cron ni
un worker corriendo**. Tampoco manda correo — los `Notification::make()` del
panel son avisos de interfaz, no mensajes salientes. Lo único que sale del
servidor hacia afuera es la llamada HTTP a Twilio cuando alguien pide su código
para comentar.

---

## 0. Antes de empezar

| Requisito | Valor | Cómo se verifica |
| --- | --- | --- |
| DNS | `vistaaltatx.com` y `www` con registro A a la IP del VPS | `nslookup vistaaltatx.com` |
| PHP | 8.3 o superior (`composer.json` pide `^8.3`; en local corre 8.4) | Plesk → Domains → PHP Settings |
| Extensiones PHP | `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `curl`, `fileinfo`, `zip` | `php -m` por SSH |
| Acceso SSH | Habilitado para el usuario de la suscripción | Plesk → Web Hosting Access → *Access to the server over SSH* → `/bin/bash` |
| Twilio | SID, Auth Token y número emisor | Consola de Twilio |

`curl` no es opcional: sin él no sale el SMS del código de verificación y nadie
puede comentar.

---

## 1. Línea base en git *(en tu máquina, una sola vez)*

El repo no tiene historial todavía. Antes del primer `git add`, confirma que la
base local está ignorada — guarda Comentarios con el teléfono de quien los
escribió, y el historial de git no se depura después:

```bash
git check-ignore -v database/database.sqlite   # debe imprimir la regla del .gitignore
```

Si no imprime nada, **detente** y arregla `.gitignore` primero.

```bash
git add -A
git status                      # revisa que no aparezca ningún .sqlite ni .env
git commit -m "Línea base del sitio"
```

Después, el repo remoto. Dos caminos:

- **Repositorio remoto (GitHub/GitLab privado)** — recomendado. Plesk lo jala y
  puede redesplegar con un webhook en cada push.
- **Repositorio local de Plesk** — la extensión Git te da una URL a la que
  empujas directo. Sirve si prefieres no meter un tercero.

```bash
git remote add origin git@github.com:<cuenta>/urge.git
git push -u origin master
```

Para cortar versión usa `scripts/release.ps1`, que regenera el CHANGELOG, commitea
y etiqueta en un solo paso.

---

## 2. El dominio en Plesk

1. **Domains → Add Domain →** `vistaaltatx.com`.
2. **Hosting Settings → Document root:** `/httpdocs/public`.

   Este es *el* paso que se equivoca. La aplicación se copia en `httpdocs/` y el
   docroot apunta a `httpdocs/public`, no a `httpdocs`. Si queda en `httpdocs`,
   quedan expuestos `.env`, `database/database.sqlite` y todo `storage/` a
   cualquiera que adivine la URL.
3. **PHP Settings:** versión 8.3+, modo **FPM application served by nginx**.

---

## 3. El código en el servidor

Con la extensión **Git** de Plesk: *Add Repository* → tu remoto → carpeta de
destino `/httpdocs` → rama `master`.

O por SSH:

```bash
cd ~/httpdocs
git clone git@github.com:<cuenta>/urge.git .
```

### Dependencias PHP

```bash
composer install --no-dev --optimize-autoloader
```

`--no-dev` no es cosmético: sin él viaja PHPUnit y el resto del andamiaje de
desarrollo. El `post-autoload-dump` corre `filament:upgrade`, que republica los
assets del panel — por eso `public/css/filament` y compañía no se versionan.

### Assets del front (Vite)

`public/build` está en `.gitignore`, así que **no llega por git**. Dos opciones:

- **Compilar en el servidor** — extensión Node.js de Plesk, y luego:
  ```bash
  npm ci && npm run build
  ```
- **Compilar en tu máquina y subir** — `npm run build` en local y copiar
  `public/build/` por SFTP. Sirve si no quieres Node en el VPS.

Las tipografías IBM Plex se auto-hospedan desde el build (`vite.config.js`), así
que este paso también produce los archivos de fuente. Si se salta, el sitio sale
sin estilos.

---

## 4. `.env` de producción

`.env` no se versiona. Cópialo de `.env.example` y ajusta:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vistaaltatx.com

LOG_LEVEL=error

SESSION_DOMAIN=vistaaltatx.com
SESSION_SECURE_COOKIE=true

OTP_CHANNEL=twilio
TWILIO_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM=+1...

ASOCIACION_CIVIL_VIDEO_URL=https://www.youtube.com/embed/...
```

Los tres que más caro cuestan si se olvidan:

- **`APP_DEBUG=false`** — en `true` cualquier error enseña el stack trace con
  fragmentos de configuración a quien entre al sitio.
- **`APP_ENV=production`** — además de lo obvio, es lo que apaga
  `/sistema-visual` (`routes/web.php` la envuelve en `! app()->isProduction()`).
- **`OTP_CHANNEL=twilio`** — en `log` el código de verificación se escribe al
  archivo de log en vez de mandarse por SMS, y ningún Colono puede comentar.

Luego la llave de la aplicación:

```bash
php artisan key:generate
```

---

## 5. Base de datos

SQLite, un archivo. Hay que crearlo — no viaja por git:

```bash
touch database/database.sqlite
php artisan migrate --force
```

`--force` es obligatorio: en producción Laravel pide confirmación interactiva y
la va a rechazar en un script.

### Contenido inicial

Las 21 Actividades del Periodo y el Reporte financiero viven en
`database/seeders/contenido/contenido-inicial.php` y se siembran con:

```bash
php artisan db:seed --class=ContenidoInicialSeeder --force
```

Es idempotente: correrlo dos veces deja el sitio igual. Las Actividades se
reconocen por fecha + texto exacto, así que **si editas una descripción en el
seeder después de sembrar, la próxima siembra crea un duplicado en vez de
actualizar**. Cuando el sitio ya esté al aire, las Actividades nuevas se capturan
desde `/admin`, no aquí.

### La cuenta del panel

```bash
php artisan make:filament-user
```

Una por integrante de la Mesa Directiva. No hay registro abierto ni recuperación
de contraseña por correo (el sitio no manda correo): si alguien la olvida, se
reasigna por SSH con `php artisan tinker`.

---

## 6. Permisos

El usuario de PHP-FPM tiene que poder escribir en tres lugares:

```bash
chmod -R 775 storage bootstrap/cache database
```

`database/` como **directorio**, no solo el archivo: SQLite crea ahí sus archivos
`-wal` y `-shm` durante la escritura, y sin permiso en la carpeta las escrituras
fallan aunque el `.sqlite` sea escribible.

---

## 7. Cachés de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Con `config:cache` activo, **`.env` deja de leerse en cada petición**. Cualquier
cambio posterior a `.env` exige volver a correr `config:cache` o no surte efecto
— es la causa número uno de "cambié la variable y no pasó nada".

---

## 8. TLS

Plesk → **SSL/TLS Certificates → Install a free basic certificate** (Let's
Encrypt), incluyendo `www`. Después, en *Hosting Settings*, activa la redirección
permanente de HTTP a HTTPS.

Con `SESSION_SECURE_COOKIE=true` en el `.env`, el sitio **solo** funciona sobre
HTTPS: hazlo después de que el certificado esté emitido, no antes.

---

## 9. Verificación

| Qué | Esperado |
| --- | --- |
| `https://vistaaltatx.com/` | 200, con estilos y la tipografía Plex |
| `https://vistaaltatx.com/sistema-visual` | **404** — confirma que `APP_ENV=production` tomó |
| `https://vistaaltatx.com/actividades` | Bitácora con las 21 Actividades, y «Lo que sigue» debajo |
| `https://vistaaltatx.com/.env` | **404 o 403** — si descarga algo, el docroot está mal |
| `https://vistaaltatx.com/admin` | Pantalla de acceso; entra con la cuenta creada |
| Comentario de prueba | Llega el SMS con el código |
| Pie de página | «La tecnología para rendir cuentas en línea la provee Urano.dev» |

La prueba del `.env` es la que no hay que saltarse: es la diferencia entre un
docroot bien puesto y regalar las credenciales de Twilio.

---

## 10. Despliegues posteriores

```bash
cd ~/httpdocs
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # solo si cambió el front
php artisan migrate --force
php artisan optimize             # limpia y rehace config/route/view cache
```

**Nunca** corras `git clean -fdx` en el servidor: se lleva `.env` y
`database/database.sqlite`, que son justamente lo que no está en git.

---

## 11. Respaldos

La base es un solo archivo, así que respaldar es copiarlo. Programa en Plesk
(**Backup Manager → Scheduled Backups**) un respaldo diario de la suscripción, que
se lleva `httpdocs/` completo e incluye `database/database.sqlite` y `.env`.

Vale la pena antes de cada despliegue:

```bash
cp database/database.sqlite ~/respaldos/database-$(date +%F-%H%M).sqlite
```

Los Comentarios y sus teléfonos solo existen ahí. No hay segunda copia.
