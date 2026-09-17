# Plan de implementación — Fase 3: Ventas (venta de pasajes, crear rutas, boletos)

**Proyecto:** `venta-pasajes` (BD: `sistema_transportes`)
**Fecha:** 2026-09-17
**Spec de referencia:** `docs/superpowers/specs/2026-09-13-migracion-laravel-design.md` (sección 5, paso 3)
**Plan anterior:** `docs/superpowers/plans/2026-09-16-migracion-laravel-fase2-plan.md` (Layout + Dashboard, hecho)

## Contexto

El usuario pidió migrar todo lo que sigue 100% legacy (Ventas, Caja, Admin, Encomiendas, Reportes, Backup) manteniendo las vistas tal cual visualmente — restricción que ya era no negociable desde el spec original (sección 2: "sin rediseño visual"). Dado el tamaño (7 fases restantes del roadmap), se sigue el orden ya aprobado: esta fase cubre solo **Ventas** (paso 3 del roadmap).

## Alcance real del módulo (auditado antes de escribir código)

No es un solo controlador: la venta de pasajes hoy vive repartida en 3 controladores legacy + 6 vistas:

- `legacy/app/controllers/Ventas.php` (1027 líneas, 20 endpoints): venta de pasajes con mapa de asientos, creación/edición de rutas y viajes programados, cobro QR con polling, manifiesto de pasajeros (JSON + PDF vía FPDF + HTML), gestión de boletos (crear/editar/confirmar/cancelar).
- `legacy/app/controllers/Boletos.php`: `imprimir_ticket($id)` (vista de impresión de un boleto ya vendido) + `procesar_nuevo`/`gestion_reserva` (duplican lógica de `Ventas::procesar_venta`/`gestion_boleto`, mismo `RutaModel`; se portan por compatibilidad de URLs aunque no sean el flujo principal de la UI actual).
- `legacy/app/controllers/ControladorTransacciones.php`: `ticket_termico($id)` (vista de impresión térmica alternativa).
- Vistas: `ventas/venta_pasajes.php` (2648 líneas), `ventas/crear_ruta.php` (3053 líneas), `ventas/ticket_impresion.php`, `ventas/ticket_termico.php`, `ventas/pantalla_qr.php`, `ventas/imprimir_manifiesto.php` (HTML) — más `imprimir_manifiesto` que genera PDF directo con FPDF (sin vista).

Modelos de negocio detrás: `RutaModel` (1398 líneas — rutas, viajes, venta transaccional de asientos con prevención de doble venta, boletos, cobro QR, choferes, paradas), `TipoBusModel`, `TerminalModel`, `CajaModel` (verificación de caja abierta), `ConfiguracionModel`, `TramoModel` (tarifas por tramo).

## Decisión de arquitectura: reutilizar los modelos legacy tal cual (mismo patrón validado en Fase 2)

`DashboardController` (Fase 2) ya validó el patrón: cargar una clase legacy con `require_once base_path('legacy/...')` **dentro del proceso de Laravel** funciona sin problemas para modelos aislados (sin pasar por el bootstrap completo del framework legacy, que sí falla en este entorno — ver el comentario de `LegacyBridgeController`, que por eso usa loopback HTTP en vez de `require`).

`RutaModel::registrarVentaTransaccion()` contiene la lógica transaccional de venta (previene doble venta de asiento) ya probada en producción con dinero real. **No se reescribe a Eloquent en esta fase** — reescribirla ahora sería exactamente el tipo de big-bang riesgoso que el patrón *strangler fig* del spec busca evitar (sección 7, tabla de riesgos). La migración a Eloquent de este modelo queda para cuando le toque una migración correctiva de esquema (fuera de alcance aquí).

Consecuencia: `VentasController` (Laravel) es una capa de *routing + vista*, no una reescritura de negocio. Reutiliza `RutaModel`, `TipoBusModel`, `TerminalModel`, `CajaModel`, `ConfiguracionModel`, `TramoModel` vía `require_once`, igual que hace `DashboardController` con `DashboardModel`.

## Puente de sesión y CSRF

Ya resuelto en Fase 1 (`AuthController::legacySession()`): `SessionManager::getInstance()` abre la sesión nativa de PHP (cookie `SISTEMA_TRANSPORTES_SESSION`), independiente de la sesión propia de Laravel (cookie `venta_pasajes_laravel_session`, driver `file`). Ambas conviven sin conflicto porque Laravel nunca toca `$_SESSION` directamente. `VentasController` reutiliza `SessionManager` exactamente como lo hace `Ventas.php` hoy (CSRF vía `$_SESSION['csrf_token']`, `getUserId()`), sin tocar una sola línea de las vistas que ya leen `$_SESSION['csrf_token']` directamente.

## Estrategia de portado de vistas (para no reescribir 8200 líneas a mano)

Las vistas legacy ya reciben sus datos como `$data['clave']` (el `Controller::view()` legacy nunca hizo `extract()`; el array `$data` queda en scope porque el `require` ocurre dentro del método que lo define — típico de PHP). Portar significa entonces:

1. Pasar el mismo array `$data` a Blade como variable `$data` (`return view('ventas.venta_pasajes', ['data' => $data]);`), igual que ya hace `DashboardController`. **Cero cambios** a las referencias `$data['x']` dentro del HTML/JS de la vista.
2. Vistas de contenido (`venta_pasajes`, `crear_ruta`): ya eran fragmentos aislados en legacy (el controlador legacy las envolvía con `layouts/header`+`sidebar`+`footer` en llamadas `$this->view()` separadas) → se envuelven en `@extends('layouts.app')` / `@section('content')...@endsection`, sin tocar el resto.
3. Vistas standalone (`ticket_impresion`, `ticket_termico`, `pantalla_qr`, `imprimir_manifiesto` HTML): ya son documentos HTML completos para impresión — se copian tal cual, sin layout.
4. Las URLs de los ~20 endpoints AJAX dentro del JS de las vistas (`URLROOT + '/ventas/...'`) se preservan **exactas**: las rutas Laravel nuevas usan las mismas rutas que el router legacy (`/ventas/obtener_ruta_viaje/{id}`, `/ventas/procesar_venta`, etc.), así el JS no necesita ni un solo cambio.

## Rutas (bajo `auth` + `active`, mismo grupo que `/dashboard`)

```
GET  /ventas/venta_pasajes
GET  /ventas/crear_ruta
POST /ventas/guardar_ruta_viaje
GET  /ventas/obtener_ruta_viaje/{id}
POST /ventas/obtener_buses_tipo
POST /ventas/obtener_tripulacion_bus
POST /ventas/despachar_ruta/{id}
POST /ventas/eliminar_ruta_viaje/{id}
POST /ventas/procesar_venta
GET  /ventas/obtener_datos_reserva/{id}
GET  /ventas/obtener_manifiesto/{id}
GET  /ventas/listar_manifiesto/{id}
POST /ventas/cancelar_boleto/{id}
GET  /ventas/imprimir_manifiesto/{id}        (PDF, FPDF)
GET  /ventas/imprimir_manifiesto_html/{id}
GET  /ventas/estado_cobro/{id}
GET  /ventas/pantalla_qr/{id}
POST /ventas/gestion_boleto
GET  /ventas/obtener_conteo_asientos/{id}
GET  /boletos/imprimir_ticket/{id}
GET  /controladortransacciones/ticket_termico/{id}
```

Al cerrar la fase, estas rutas se quitan de la lista de fallback (o simplemente dejan de matchear en `LegacyBridgeController` porque Laravel las resuelve primero — el bridge solo ve lo que no matchea antes).

## Fuera de alcance de esta fase

- Reescribir `RutaModel`/`TramoModel` a Eloquent (queda para cuando se ejecuten las migraciones correctivas de BD, spec 4.2).
- Los 3 pendientes de diseño anotados en el plan de Fase 2 (rutas+encomiendas compartiendo flujo, timeline gráfico de paradas, registro de choferes separado de buses) — siguen para Fase 5 (Admin) o su propia sesión de diseño.
- Caja, Admin, Encomiendas, Reportes, Backup — fases 4-8, siguientes en el roadmap.

## Siguiente paso

Implementar controlador + rutas + portado de vistas, validar con `php artisan route:list` y una revisión visual en navegador (login real → `/ventas/venta_pasajes` y `/ventas/crear_ruta`), luego decidir con el usuario si se avanza a Fase 4 (Caja).
