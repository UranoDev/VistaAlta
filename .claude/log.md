# Log de cambios

## 2026-06-02
- **Paleta de Colores**: Se definió y configuró una paleta inspirada en Tequisquiapan (Terracota, Cantera Rosa, Cantera Clara y Azul Colonial) en `tailwind.config.js`.
- **Mejora del Dashboard**:
    - Se transformó el dashboard estático en un centro de control con tarjetas de estadísticas.
    - Se agregaron accesos directos para crear y listar fraccionamientos y propietarios directamente desde el inicio.
    - Se aplicó la paleta de colores institucional (Terracota y Contraste Azul) en todo el dashboard.
- **Optimización de Interfaces CRUD**:
    - Se mejoraron las tablas de listado con iconos de acción (Editar/Eliminar) y efectos hover.
    - Se estandarizaron los botones de "Nuevo" para que sean más prominentes y coherentes con la marca.
- **Tarea T-003 (CRUD de Fraccionamientos)**:
    - Se agregaron los campos `slug`, `address` y `contact` a la tabla `fraccionamientos` mediante una migración.
    - Se implementó el controlador `FraccionamientoController` con todas las operaciones CRUD.
    - Se crearon las vistas `index`, `create` y `edit` para fraccionamientos, aplicando la nueva paleta de colores y validaciones.
    - Se verificó la funcionalidad con tests automatizados (7 pruebas, 15 aserciones).

## 2026-06-02 (Mejoras de Estilos y UX)
- **Rediseño de Estados Vacíos**:
    - Se eliminaron las tablas vacías (sin encabezados) para mostrar una interfaz más limpia cuando no hay registros.
    - Se implementaron tarjetas de bienvenida con iconos mejorados, mensajes claros y botones de acción destacados.
- **Identidad Institucional**:
    - Se personalizaron los componentes globales `nav-link`, `responsive-nav-link` y `primary-button` con la paleta de colores institucional.
    - Se reemplazó el color indigo por defecto de Laravel por **Terracota** y **Contraste Azul**.
    - Se ajustaron los estados activos y hover para una mejor experiencia de navegación.
- **Consistencia Visual**: Se estandarizaron sombras, bordes y espaciados en los listados CRUD.

## 2026-06-08 (T-006)
- **T-006 — Cuota mensual por fraccionamiento**:
    - Migración `2026_06_08_000001_create_monthly_fees_table`: tabla `monthly_fees` con `fraccionamiento_id` (FK cascadeOnDelete), `amount` (decimal), `start_date` (date), `surcharge_type` (nullable: `percentage` | `fixed`), `surcharge_value` (decimal nullable). Índice compuesto en `(fraccionamiento_id, start_date)`.
    - Modelo `MonthlyFee` con métodos helpers: `isActive()`, `isFuture()`, `amountWithSurcharge()`. El recargo se aplica una sola vez (no acumulativo).
    - Modelo `Fraccionamiento` extendido con `monthlyFees()`, `currentFee()` (la de `start_date` más reciente ≤ hoy) y `scheduledFee()` (la de `start_date` > hoy).
    - `MonthlyFeeController` con rutas anidadas `fraccionamientos.fees.*`: `index`, `create`, `store`, `destroy`. Al registrar una nueva cuota futura se elimina automáticamente cualquier cuota futura previa (solo puede haber una programada); las cuotas históricas se conservan siempre.
    - Solo se puede eliminar una cuota con `start_date` futuro; las activas/históricas son de solo lectura para preservar auditoría.
    - Autorización: superadmin accede a todos los fraccionamientos; admin solo a los asignados. Usa `FraccionamientoPolicy::view` e `update` existente.
    - Vista `monthly-fees/index.blade.php`: muestra cuota vigente (con desglose de recargo), cuota programada (con opción de cancelar) e historial completo paginado con estado (Vigente / Programada / Histórica).
    - Vista `monthly-fees/create.blade.php`: formulario con importe, fecha de inicio (admite fecha futura), selección de recargo (sin / porcentaje / importe fijo) con JS dinámico para mostrar/ocultar el campo de valor.
    - Índice de fraccionamientos actualizado: nueva columna "Cuota vigente" y botón de acceso directo a cuotas (ícono $).
    - `MonthlyFeeFactory` con estados: `future()`, `withPercentageSurcharge()`, `withFixedSurcharge()`.
    - `tests/Feature/MonthlyFeeControllerTest.php`: 19 tests — acceso por rol, creación con y sin recargo, cuota futura, reemplazo de cuota programada, conservación de historial, validaciones, eliminación permitida/bloqueada, lógica de cuota vigente por fecha, cálculo de recargos.
    - Suite completa: 82/82 tests pasando.

## 2026-06-08
- **T-005 — CRUD de Propiedades**:
    - Migración `2026_06_05_000002_create_properties_table`: tabla `properties` con `fraccionamiento_id` (FK cascadeOnDelete), `owner_id` (FK nullable nullOnDelete), `section` (nullable) y `unit` (requerido).
    - Modelo `Property` con relaciones `belongsTo` a `Fraccionamiento` y `Owner`. Relaciones inversas `hasMany(Property::class)` agregadas en `Fraccionamiento` y `Owner`.
    - `PropertyController` con CRUD completo: el index filtra por fraccionamientos asignados para usuarios no-superadmin; el superadmin ve todo.
    - `StorePropertyRequest` / `UpdatePropertyRequest` con validaciones: `fraccionamiento_id` requerido, `owner_id` nullable, `unit` requerido.
    - Vistas `index`, `create` y `edit` con estilo consistente. El selector de propietario se filtra dinámicamente por fraccionamiento vía JavaScript sin llamadas adicionales al servidor.
    - Ruta resource `properties` añadida en `web.php`. Dashboard actualizado con tarjeta de Propiedades y contador. Navegación con enlace "Propiedades" en desktop y móvil.
    - `PropertyFactory` con sección aleatoria, unidad única y owner del mismo fraccionamiento.
    - `tests/Feature/PropertyControllerTest.php`: 12 tests, todos pasando — CRUD completo, validaciones, restricción de propietario único por FK, scoping por rol.
    - Corrección de `DashboardTest::test_fraccionamientos_index_has_crud_buttons`: el test usaba usuario sin rol, pero T-004 ya aplica scoping. Se actualizó para usar superadmin.
    - Suite completa: 63/63 tests pasando.

## 2026-06-05
- **Administrador del Fraccionamiento (desde Propietarios)**:
    - Se agregó columna `admin_owner_id` (FK nullable a `owners`) en la tabla `fraccionamientos` mediante nueva migración (`nullOnDelete` para evitar FK circular problemática).
    - Se añadió relación `administrator()` (`belongsTo Owner`) al modelo `Fraccionamiento` y el campo al `$fillable`.
    - El `FraccionamientoController::edit()` ahora pasa `$propietarios` (owners del fraccionamiento ordenados por nombre) en lugar de la lista de Users con `role='admin'`.
    - La vista `edit.blade.php` muestra un `<select>` con los propietarios del fraccionamiento; si no hay propietarios, muestra un aviso informativo.
    - La vista `create.blade.php` reemplaza los checkboxes de usuarios por una nota explicando que el admin se asigna desde la edición.
    - El listado `index.blade.php` muestra una columna "Administrador" con el nombre del propietario designado.
    - Se agregó eager loading de `administrator` en el index para evitar N+1.
    - `UpdateFraccionamientoRequest` valida `admin_owner_id` como `nullable|exists:owners,id`.
- **Creación de CLAUDE.md**: Se creó el archivo `CLAUDE.md` en la raíz del proyecto con instrucciones persistentes para Claude Code: regla de actualizar el log, stack del proyecto, tabla de roles y notas de arquitectura.
- **Corrección de Bug — Rol faltante en Seeder**:
    - `test@example.com` se creaba sin `role`, causando que el CRUD de fraccionamientos mostrara lista vacía (el query filtra por usuarios asignados cuando no es superadmin) y que el formulario de creación devolviera **403** (la `FraccionamientoPolicy` requiere `superadmin`).
    - Se agregó `'role' => 'superadmin'` al seeder en `database/seeders/DatabaseSeeder.php`.
    - Para actualizar el usuario ya existente en BD, ejecutar: `php artisan tinker --execute="App\Models\User::where('email', 'test@example.com')->update(['role' => 'superadmin']);"`

## 2026-06-03
- **Tarea T-004 (Asignación de Administradores)**:
    - Se implementó la relación muchos a muchos entre usuarios (administradores) y fraccionamientos.
    - Se creó la `FraccionamientoPolicy` para restringir el acceso:
        - Los **SuperAdmin** mantienen acceso total y global.
        - Los **Admin** de fraccionamiento solo pueden ver y editar los fraccionamientos que tienen asignados.
    - Se actualizó el `FraccionamientoController` para filtrar los listados automáticamente según el rol del usuario.
    - Se mejoraron las vistas de creación y edición para permitir a los SuperAdmin asignar administradores mediante una lista de selección.
    - Se verificó la seguridad con un nuevo set de tests de acceso (`FraccionamientoAccessTest`).
