# Plan de implementación — Fase 4: Caja

**Proyecto:** `venta-pasajes` (BD: `sistema_transportes`)
**Fecha:** 2026-09-17
**Spec de referencia:** `docs/superpowers/specs/2026-09-13-migracion-laravel-design.md` (sección 5, paso 4)
**Plan anterior:** `docs/superpowers/plans/2026-09-17-migracion-laravel-fase3-plan.md` (Ventas, hecho)

## Alcance

Puerto de `legacy/app/controllers/Caja.php` (582 líneas) + `CajaModel` + 4 vistas (`apertura`, `index`, `reporte`, `reporte_z`, ~1250 líneas) a `App\Http\Controllers\CajaController` + `resources/views/caja/*.blade.php`. Mismo patrón que Fase 3: reutiliza `CajaModel` tal cual (transacciones de apertura/cierre ya probadas), vistas portadas 1:1 preservando HTML/CSS/JS y las URLs de los endpoints AJAX.

## Hallazgo nuevo (aplicado retroactivamente a Fase 3 también)

`CajaModel::obtenerSesionesCerradas()`/`obtenerEstadisticasPeriodo()` usan `Sucursal::condicion()`, que internamente depende de `$_SESSION['sucursal_id']`/`$_SESSION['rol']` — datos que **solo** `legacy/public/index.php` refresca en cada petición (ver `legacy/app/core/Sucursal.php`, comentario de cabecera). Un controlador Laravel-nativo que reutiliza estas clases sin pasar por ese archivo tenía esos valores vacíos, lo que silenciosamente filtraba los reportes de caja a "sucursal 0" (ningún resultado) para cualquier rol no-global.

Se corrigió centralizando el fix en `App\Http\Controllers\Concerns\InteractsWithLegacy::legacySucursalHelper()`: repite el mismo refresco (rol + sucursal_id + sucursal_nombre) leyendo `auth()->user()` en vez de repetir la consulta SQL. Se refactorizaron `VentasController`, `ControladorTransaccionesController` y `BoletosController` para usar este trait compartido en vez de duplicar `legacyModel()`/`legacySession()`.

## Rutas

```
GET  /caja
POST /caja/abrir
POST /caja/cerrar
POST /caja/registrar_gasto_ajax
POST /caja/obtener_ingresos_ajax
GET  /caja/reporte/{id}
GET  /caja/reporte_z/{id}
POST /caja/obtener_reportes_cierre_ajax
POST /caja/exportar_excel   (PhpSpreadsheet, ya en vendor/ fusionado)
POST /caja/exportar_word    (PhpWord)
POST /caja/exportar_pdf/{tamano?}  (Dompdf)
```

## Siguiente paso

Fase 5 (Admin): personal, usuarios, roles/permisos, buses, tipos de bus, rutas/paradas, terminales, series de boletos — la fase más grande, y donde tocan las migraciones correctivas de BD (fusión `buses`+`vehiculos`, `personal`+`usuarios`) documentadas en `docs/superpowers/plans/2026-09-17-auditoria-normalizacion-bd.md`.
