# Plan de implementación — Fase 8: Backup

**Fecha:** 2026-09-17
**Plan anterior:** `docs/superpowers/plans/2026-09-17-migracion-laravel-fase7-plan.md` (Reportes, hecho)

## Alcance

Puerto de `legacy/app/controllers/Backup.php` (322 líneas, sin modelo propio — trabaja directo con el filesystem y `mysqldump`/`ZipArchive`) a `App\Http\Controllers\BackupController`.

## Bugs corregidos (no solo transcripción)

1. **Directorio de backups apuntaba a una carpeta que no existe.** El legacy calculaba `dirname(dirname(dirname(__FILE__)))` desde su propia ubicación; antes de la Fase 1 eso resolvía a la raíz del proyecto, pero tras moverse a `legacy/app/controllers/Backup.php` en la reestructuración resuelve a `legacy/` — carpeta sin `backups/` dentro. El backup real ya existente (`backup_db_2025-12-31_20-16.sql`, verificado en disco) vive en `<raíz>/backups/`. Se corrigió a `base_path('backups')` explícito.
2. **El ZIP ya no zipeaba el código vivo.** Por el mismo bug de ruta, el ZIP comprimía `legacy/` en vez de la raíz del proyecto — con esta migración, el código que realmente hay que resguardar (rutas, controladores y vistas Laravel) queda fuera de `legacy/`. Se corrigió a zipear `base_path()`, excluyendo `backups/`, `.git/`, `vendor/`, `node_modules/` y `storage/framework|logs` (reproducibles vía `composer install`/`npm install`, no datos de usuario — incluirlos infla el ZIP sin necesidad).
3. **Path traversal en la descarga.** `download($filename)` concatenaba `$filename` directo a la ruta del archivo sin `basename()` (a diferencia de `delete()`, que sí lo hacía) — un `../../` en la URL permitía descargar cualquier archivo del servidor (p. ej. `.env` con las credenciales de BD) como binario. Se aplicó `basename()` también en `download()`.
4. **Sin `requireAuth()`** (hallazgo ya señalado en el spec, sección 6): cerrado automáticamente por quedar esta ruta dentro del grupo `auth`+`active`, igual que el resto de módulos migrados.

## Rutas

```
GET  /backup
GET  /backup/download/{filename}
GET  /backup/delete/{filename}   (el legacy también usa GET para esto, se preservó)
GET  /backup/generate
```

## Verificación

`php artisan route:list` (107 rutas Laravel en total, sumando las 9 fases), `view:cache`, `php artisan test` (9/9 — `LegacyBridgeTest` ya no tenía ningún módulo entero sin migrar para usar de ejemplo; se cambió a rutas inexistentes a propósito, que es lo único que ese test realmente necesita). Curl contra Apache real: 302 a login sin sesión. No se ejecutó `generate()` (crea archivos `.sql`/`.zip` reales): queda para que el usuario lo pruebe manualmente con sesión real.

## Con esto se cierran las 8 fases del roadmap original del spec

Sección 5 del spec (`docs/superpowers/specs/2026-09-13-migracion-laravel-design.md`): Núcleo+Auth+RBAC (1), Layout (2), Ventas (3), Caja (4), Admin (5), Encomiendas (6), Reportes (7), Backup (8) — todas migradas. Queda el paso 9 (Cierre: borrar `legacy/`) y, por pedido explícito del usuario en esta misma sesión, ir más allá de lo que el spec original definía como alcance: reescribir a Eloquent la lógica de negocio que hoy sigue reutilizándose tal cual desde `legacy/app/models/*.php`, y ejecutar las migraciones correctivas de BD — ver el informe de estado de fin de sesión para el detalle de qué queda pendiente y por qué no se hizo en la misma pasada.
