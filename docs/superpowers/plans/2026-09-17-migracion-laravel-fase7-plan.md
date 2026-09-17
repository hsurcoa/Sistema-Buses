# Plan de implementación — Fase 7: Reportes

**Fecha:** 2026-09-17
**Plan anterior:** `docs/superpowers/plans/2026-09-17-migracion-laravel-fase6-plan.md` (Encomiendas, hecho)

## Alcance

Puerto de `legacy/app/controllers/Reportes.php` (175 líneas) + `ReporteModel` + 3 vistas (`pasajeros`, `financiero`, `imprimir_manifiesto`, ~1500 líneas) a `App\Http\Controllers\ReportesController`. Todo de solo lectura (el legacy tampoco valida CSRF aquí, no se agregó). `imprimir_manifiesto` es un documento HTML autónomo para impresión (no usa el layout, como su equivalente en Ventas).

El spec preveía migrar aquí a `barryvdh/laravel-dompdf`/`maatwebsite/excel`; no se hizo: los reportes de Pasajeros/Financiero no generan PDF/Excel en el código actual (solo `Ventas::imprimir_manifiesto` y `Caja::exportar_*`, ya portados en Fases 3 y 4 reusando FPDF/PhpSpreadsheet/Dompdf tal cual). No hay nada más que migrar en ese frente por ahora.

## Rutas

```
GET  /reportes                              (alias de pasajeros)
GET  /reportes/pasajeros
POST /reportes/buscar_viajes_ajax
GET  /reportes/obtener_manifiesto_ajax/{viaje_id}
POST /reportes/buscar_historico_ajax
POST /reportes/buscar_persona_ajax
GET  /reportes/imprimir_manifiesto/{viaje_id}
GET  /reportes/financiero
POST /reportes/financiero_ajax
```

## Verificación

`php artisan route:list`, `view:cache`, `php artisan test` (9/9 — se re-adaptó `LegacyBridgeTest` para usar `/backup/*`, el único módulo que sigue 100% legacy, como ejemplo de ruta no migrada), curl contra Apache real (302 a login sin sesión).

## Siguiente paso

Fase 8 (Backup) — último paso del roadmap original antes del cierre (retirar `legacy/`).
