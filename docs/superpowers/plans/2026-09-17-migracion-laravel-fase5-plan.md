# Plan de implementación — Fase 5: Admin

**Fecha:** 2026-09-17
**Plan anterior:** `docs/superpowers/plans/2026-09-17-migracion-laravel-fase4-plan.md` (Caja, hecho)

## Alcance (más grande de lo previsto en el spec)

El spec original agrupaba esto bajo un solo "Admin", pero en el código vive repartido en **4 controladores legacy**: `Admin.php` (1711 líneas: personal, usuarios, roles/permisos, choferes/asignaciones, sucursales, rutas/paradas/tarifas, tipos de bus, mapa de asientos), `Vehiculos.php` (CRUD real de buses — la vista `registrar_buses` llama a `/vehiculos/*`, no a `/admin/*`), `Series.php` (series de boletos — la vista llama a `/series/*`), `Configuracion.php` (datos de empresa y QR de cobro). Se portaron los 4 a `AdminController`, `VehiculosController`, `SeriesController`, `ConfiguracionController` respectivamente, mismo patrón de Fase 3/4.

## Código muerto detectado (no portado)

`Admin::registrar_serie_boletos()`/`guardar_serie_boletos()`/`cambiar_estado_serie()` duplican lo que ya hace `Series.php`, pero ninguna vista enlaza a esas rutas `/admin/*` (la vista real usa `/series/*`). Verificado con grep sobre las 15 vistas de `admin/`. No se portaron para no mantener dos implementaciones inalcanzables.

## Correcciones aplicadas (no solo transcripción)

- **Rutas de subida de archivos**: el código legacy usaba rutas relativas (`'../public/img/personal/'`, `'uploads/pagos/'`) que dependían del directorio de trabajo del front-controller legacy (`legacy/public/`). Bajo el front-controller de Laravel (`public/`) esas rutas relativas ya no son fiables. Se reemplazaron por `public_path(...)`, explícito e independiente de cómo se invoque PHP.
- **`Admin::esAdministrador()`** leía `$_SESSION['rol']` (poblado solo por el refresco de `legacy/public/index.php`, ver hallazgo de Fase 4). Se reemplazó por `auth()->user()?->rol?->nombre === 'Administrador'`, más simple porque Eloquent ya es fresco en cada petición.

## Rutas

53 rutas nuevas: 42 bajo `/admin/*`, 5 bajo `/vehiculos/*`, 3 bajo `/series*`, 3 bajo `/configuracion*`. Ver `routes/web.php`.

## Verificación

`php artisan route:list`, `view:cache` (compila las 15 vistas de admin + 1 de configuración sin errores), `php artisan test` (9/9), curl contra Apache real: las 11 pantallas principales devuelven 302 a login sin sesión, igual que el resto de rutas migradas.

## Siguiente paso

Fase 6 (Encomiendas).
