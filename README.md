# Sistema de Venta de Pasajes (Sistema-Buses)

Sistema de venta de pasajes y encomiendas para transporte terrestre (buses):
venta con mapa de asientos, caja, administración de flota/rutas/personal,
encomiendas y reportes (PDF/Excel/Word).

## Arquitectura: migración incremental a Laravel (patrón *strangler fig*)

El proyecto está en pleno proceso de migración de un MVC casero en PHP puro
hacia **Laravel 11**, módulo por módulo, sin cortar el servicio. Conviven dos
capas en el mismo repositorio:

- **`/` (raíz) — App Laravel 11.** Framework, rutas, modelos y controladores
  ya migrados. Autenticación (`routes/auth.php`), RBAC con
  `spatie/laravel-permission`, generación de reportes con `dompdf`,
  `phpoffice/phpspreadsheet` y `phpoffice/phpword`.
- **`legacy/`** — Sistema original: MVC propio (`app/core/App.php` como
  router, `app/core/Database.php` como wrapper PDO), sin ORM ni rutas
  declaradas (convención `controlador/metodo/parametros`). Sigue activo:
  ventas, caja, buses, rutas, encomiendas y reportes que aún no se migraron.

### Cómo se conectan

`routes/web.php` registra primero las rutas ya migradas a Laravel. Todo lo
que **no** coincida cae a un catch-all (`routes/legacy.php` →
`LegacyBridgeController`) que reenvía la petición al sistema legacy — sin
sesión/CSRF/cifrado de cookies de Laravel, porque el legacy gestiona los
suyos. Así cada módulo se puede migrar de forma independiente sin romper el
resto.

## Stack

- **Laravel 11** (PHP ^8.2), `spatie/laravel-permission`, `dompdf`,
  `phpoffice/phpspreadsheet`, `phpoffice/phpword`.
- **Legacy:** PHP puro (MVC propio), MariaDB vía PDO, Bootstrap 5 +
  AdminLTE, jQuery, SweetAlert2.
- **Base de datos:** MariaDB (`sistema_transportes`).

## Estado de la migración

Ver `INFORME_TECNICO_ARQUITECTURA_Y_MIGRACION_LARAVEL.md` para el análisis
completo de arquitectura, hallazgos de seguridad y plan de migración.
