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
