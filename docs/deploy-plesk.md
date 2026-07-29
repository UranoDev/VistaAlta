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

Revisa qué entraría al commit **antes** de hacerlo:

```powershell
git add -An | Select-String -Pattern '\.env|\.sqlite|\.key|/vendor/|node_modules'
```

Debe salir únicamente `.env.example` (la plantilla, sin valores) y los
`storage/framework/*/.gitignore` de Laravel, que son los marcadores que recrean
esas carpetas en un clon nuevo. Cualquier otra cosa en esa lista es un problema.

No commitees a mano: el primer commit lo hace `release.ps1` junto con el
CHANGELOG y la etiqueta — es la sección siguiente.

Después, el repo remoto. Dos caminos:

- **Repositorio remoto (GitHub/GitLab privado)** — recomendado. Plesk lo jala y
  puede redesplegar con un webhook en cada push.
- **Repositorio local de Plesk** — la extensión Git te da una URL a la que
  empujas directo. Sirve si prefieres no meter un tercero.

```bash
git remote add origin https://github.com/uranodev/VistaAlta.git
```

HTTPS y no SSH a propósito: Git Credential Manager viene con Git para Windows y
ya es el `credential.helper` de esta máquina, así que el primer `push` se
autentica solo —o abre el navegador una vez— sin que haya que generar ni
registrar una llave. En un clon nuevo en Windows, esto funciona sin preparativos.

El primer push necesita `-u` porque la rama local todavía no tiene upstream:

```powershell
git push -u origin master
```

---

## 2. Cortar la versión: CHANGELOG.md y etiqueta

El changelog **no se escribe a mano**: se genera desde los issues resueltos de
YouTrack (proyecto `URVA` en `https://uranodev.youtrack.cloud`, declarado en
`.youtrack-project.ps1`). Todo desde PowerShell, en la raíz del repo.

### El camino corto: un solo comando

```powershell
./scripts/release.ps1
```

Eso hace cuatro cosas en orden, y el orden es el punto: refresca el caché de
issues desde YouTrack → regenera `CHANGELOG.md` → `git add -A` → `git commit` →
`git tag`. El changelog se genera **antes** del commit, así que viaja *dentro*
del commit de release en vez de quedar como un commit «docs» aparte.

La versión es CalVer y sale de la fecha de hoy (`2026.07.29`). Si ya existe una
etiqueta con ese nombre —segundo corte el mismo día— la sube a `.1`, `.2`, y usa
ese mismo valor para el encabezado del CHANGELOG y para la etiqueta.

Banderas útiles:

```powershell
./scripts/release.ps1 -DryRun                              # regenera el CHANGELOG y NO toca git
./scripts/release.ps1 -NoRefresh                           # reusa el caché actual, sin pegarle a YouTrack
./scripts/release.ps1 -Version 2026.08.01 -Message "..."   # versión y mensaje explícitos
```

Corre `-DryRun` primero. Deja el `CHANGELOG.md` regenerado para que lo revises
sin haber commiteado ni etiquetado nada.

Un detalle que engaña: **`-DryRun` también se salta el refresh** —lo anuncia con
`(dry-run) & scripts/refresh-issues-resolved.ps1`— así que lo que ves sale del
caché, no de YouTrack. Si cerraste issues después de la última generación, no
aparecen en esa vista previa. Para revisar lo que de verdad va a salir:

```powershell
./scripts/refresh-issues-resolved.ps1
./scripts/release.ps1 -DryRun -NoRefresh
```

### Los dos pasos por separado

Si quieres control fino, o si `release.ps1` falla a medias:

```powershell
./scripts/refresh-issues-resolved.ps1       # 1. YouTrack -> issues-resolved.json
./scripts/changelog.ps1 -Mode TagWindow     # 2. issues-resolved.json -> CHANGELOG.md
```

Para revisar antes de sobreescribir el caché o el changelog reales:

```powershell
./scripts/refresh-issues-resolved.ps1 -OutputFile issues-resolved.preview.json
./scripts/changelog.ps1 -Mode TagWindow -OutputFile CHANGELOG.preview.md
```

### Qué hace `-Mode TagWindow` y por qué es el que usamos

`changelog.ps1` tiene tres modos. `TagWindow` **ignora por completo el texto de
los mensajes de commit**: agrupa los issues por la etiqueta de versión en cuyo
rango cayó su fecha de resolución. Cada encabezado es el nombre de una etiqueta,
y lo resuelto después de la última etiqueta va bajo `## [Unreleased]`.

De ahí sale el truco de `-AsVersion`, que `release.ps1` usa: renderiza ese bucket
final con el número de versión que estás a punto de crear en vez de
`[Unreleased]`, y así el CHANGELOG puede quedar completo *antes* de que la
etiqueta exista.

Consecuencia práctica: **los mensajes de commit no alimentan el changelog**. Lo
que aparece publicado es el `Summary` de cada issue en YouTrack. Si un renglón se
lee mal, se corrige en YouTrack y se vuelve a generar — no editando el markdown,
que se sobreescribe en el siguiente corte.

Los tipos `Bug`, `Exception` y `Performance Problem` caen bajo *Fix / Bugs*;
todo lo demás —`Task`, `Feature`, `Usability Problem`, `Cosmetics`— cae bajo
*Features*. Los `Epic` se excluyen: el detalle lo cargan sus subtareas.

### Credenciales

La cascada la resuelve `scripts/youtrack-credentials.ps1`, de lo más específico a
lo más general:

1. `.ralph/youtrack.config.ps1` — override local, gitignoreado
2. `.youtrack-project.ps1` — **commiteado**, solo URL y proyecto, nunca el token
3. `$HOME\.youtrack.config.ps1` — el token, una vez por máquina
4. `YOUTRACK_BASE_URL` / `YOUTRACK_TOKEN` / `YOUTRACK_PROJECT` — entorno, para CI

En esta máquina el token está en `C:\Users\Urano\.youtrack.config.ps1`, así que
los scripts corren sin argumentos. En un clon nuevo, ese archivo es lo único que
hay que crear.

`issues-resolved.json` **está gitignoreado**: es caché local, no parte del repo.
No es por secretos —el archivo solo trae `Id`, `ResolvedAt`, `Type` y `Summary`,
sin autores ni teléfonos— sino para no versionar un archivo derivado que se
regenera con un comando.

La consecuencia es concreta: **YouTrack es la única fuente del changelog**. En un
clon recién hecho el caché no existe, así que `changelog.ps1` no tiene de dónde
leer y hay que refrescar primero:

```powershell
./scripts/refresh-issues-resolved.ps1     # obligatorio en un clon nuevo
./scripts/changelog.ps1 -Mode TagWindow
```

Por lo mismo, `-NoRefresh` sirve solo en una máquina que ya generó el caché al
menos una vez, y un CI que corte versiones necesita el token de YouTrack en el
entorno — no le basta con el repo.

### Publicar

```powershell
git push -u origin master     # -u solo la primera vez, para amarrar el upstream
git push origin 2026.07.29
```

La etiqueta se empuja aparte: `git push` solo no se lleva las etiquetas.

Nada de `&&` entre los dos: esta máquina corre **Windows PowerShell 5.1**, donde
`&&` no es un separador válido y truena con `InvalidEndOfLine` antes de ejecutar
nada. Si los quieres condicionados en un solo renglón:

```powershell
git push; if ($?) { git push origin 2026.07.29 }
```

---

## 3. El dominio en Plesk

1. **Domains → Add Domain →** `vistaaltatx.com`.
2. **Hosting Settings → Document root:** `/httpdocs/public`.

   Este es *el* paso que se equivoca. La aplicación se copia en `httpdocs/` y el
   docroot apunta a `httpdocs/public`, no a `httpdocs`. Si queda en `httpdocs`,
   quedan expuestos `.env`, `database/database.sqlite` y todo `storage/` a
   cualquiera que adivine la URL.
3. **PHP Settings:** versión 8.3+, modo **FPM application served by nginx**.

---

## 4. El código en el servidor

Con la extensión **Git** de Plesk: *Add Repository* →
`https://github.com/uranodev/VistaAlta.git` → carpeta de destino `/httpdocs` →
rama `master`. Plesk guarda las credenciales del repo en la suscripción, así que
es el camino menos áspero para un repo privado.

O a mano, desde una sesión SSH en el servidor:

```bash
cd ~/httpdocs
git clone https://github.com/uranodev/VistaAlta.git .
```

Ojo con esta variante si el repo es privado: en el VPS **no hay** Git Credential
Manager, así que ese `clone` se queda esperando usuario y contraseña, y GitHub ya
no acepta contraseñas de cuenta. Hay que darle un token:

```bash
git clone https://<usuario>:<token>@github.com/uranodev/VistaAlta.git .
```

Un *fine-grained token* con permiso de solo lectura sobre este repo alcanza. Queda
escrito en `.git/config` en claro, que es la razón de preferir la extensión de
Plesk o —si el repo se queda privado y hay muchos despliegues— una **deploy key**
SSH de solo lectura generada en el propio servidor.

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

## 5. `.env` de producción

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

## 6. Base de datos

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

## 7. Permisos

El usuario de PHP-FPM tiene que poder escribir en tres lugares:

```bash
chmod -R 775 storage bootstrap/cache database
```

`database/` como **directorio**, no solo el archivo: SQLite crea ahí sus archivos
`-wal` y `-shm` durante la escritura, y sin permiso en la carpeta las escrituras
fallan aunque el `.sqlite` sea escribible.

---

## 8. Cachés de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Con `config:cache` activo, **`.env` deja de leerse en cada petición**. Cualquier
cambio posterior a `.env` exige volver a correr `config:cache` o no surte efecto
— es la causa número uno de "cambié la variable y no pasó nada".

---

## 9. TLS

Plesk → **SSL/TLS Certificates → Install a free basic certificate** (Let's
Encrypt), incluyendo `www`. Después, en *Hosting Settings*, activa la redirección
permanente de HTTP a HTTPS.

Con `SESSION_SECURE_COOKIE=true` en el `.env`, el sitio **solo** funciona sobre
HTTPS: hazlo después de que el certificado esté emitido, no antes.

---

## 10. Verificación

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

## 11. Despliegues posteriores

En tu máquina, corta la versión (sección 2) y publícala:

```powershell
./scripts/release.ps1
git push
git push origin <version>
```

En el servidor:

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

## 12. Respaldos

La base es un solo archivo, así que respaldar es copiarlo. Programa en Plesk
(**Backup Manager → Scheduled Backups**) un respaldo diario de la suscripción, que
se lleva `httpdocs/` completo e incluye `database/database.sqlite` y `.env`.

Vale la pena antes de cada despliegue:

```bash
cp database/database.sqlite ~/respaldos/database-$(date +%F-%H%M).sqlite
```

Los Comentarios y sus teléfonos solo existen ahí. No hay segunda copia.
