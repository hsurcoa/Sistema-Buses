# Plan de implementación — Fase 6: Encomiendas

**Fecha:** 2026-09-17
**Plan anterior:** `docs/superpowers/plans/2026-09-17-migracion-laravel-fase5-plan.md` (Admin, hecho)

## Alcance

Puerto de `legacy/app/controllers/Encomiendas.php` (125 líneas) + `EncomiendaModel` + 3 vistas (`crear`, `index`, `recibo`, ~740 líneas) a `App\Http\Controllers\EncomiendasController`. Reutiliza `RutaModel::calcularPrecioDinamico()` (ya portado en Fase 5) para la cotización dinámica por tramo.

## Corrección de seguridad aplicada

El legacy sanitizaba **todo** `$_POST` con `filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS)` antes de leerlo — las vistas (`recibo.php` en particular) hacen `echo` directo de los datos del remitente/destinatario sin escapar. `$request->input()` de Laravel no aplica ese filtro. Se replicó campo por campo con `htmlspecialchars(...)` antes de guardar, para no introducir un XSS almacenado que el legacy no tenía.

## Rutas

```
GET  /encomiendas
GET  /encomiendas/crear
GET  /encomiendas/obtener_viajes/{rutaId}
POST /encomiendas/guardar
GET  /encomiendas/recibo/{id}
```

## Verificación

`php artisan route:list`, `view:cache`, `php artisan test` (9/9), curl contra Apache real (302 a login sin sesión).

## Siguiente paso

Fase 7 (Reportes): financiero, pasajeros, manifiesto de viaje — con exportación a PDF/Excel (dompdf/PhpSpreadsheet, ya en el `vendor/` fusionado, usados en Fase 4).
