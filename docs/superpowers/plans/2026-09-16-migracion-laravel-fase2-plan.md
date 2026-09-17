# Plan de implementación — Fase 2: Layout base propio (sin AdminLTE) + Dashboard

**Proyecto:** `venta-pasajes` (BD: `sistema_transportes`)
**Fecha:** 2026-09-16
**Spec de referencia:** `docs/superpowers/specs/2026-09-13-migracion-laravel-design.md` (aprobado)
**Plan anterior:** `docs/superpowers/plans/2026-09-13-migracion-laravel-fase1-plan.md` (Núcleo + Auth + RBAC, hecho)

## Contexto

El spec original (sección 2/9) decía "portar AdminLTE tal cual, sin rediseño visual". Eso ya no aplica: entre la Fase 1 y hoy, `legacy/app/views/layouts/{header,footer,sidebar}.php` fueron reescritos (commits `b74478e`, `7e19c67`) con un diseño propio (`legacy/public/css/custom.css`, Bootstrap 5 standalone, sin AdminLTE). Esta fase porta **ese diseño propio** —no AdminLTE— a Blade, y migra el Dashboard a Laravel como primer consumidor del layout nuevo.

Alcance completo del roadmap (Fases 2–9) documentado en el plan maestro aprobado por el usuario (sesión 2026-09-16); este documento cubre solo la Fase 2.

## Hecho

### Tarea 1 — Assets estáticos
Ya estaban copiados a `public/css/custom.css`, `public/js/bus-renderer.js` y `public/assets/js/impresion-ticket.js` (idénticos byte a byte a sus originales en `legacy/`, verificado con `diff`), de un trabajo de endurecimiento anterior. Nada que hacer.

### Tarea 2 — `resources/views/layouts/app.blade.php`
Header + sidebar + footer legacy portados a un único layout Blade con `@yield('content')`. Cambios respecto al original:
- `$_SESSION['usuario']/['rol']/['sucursal_nombre']` → `auth()->user()` con las relaciones `rol()` y `sucursal()` (nueva, agregada a `App\Models\Usuario` → `App\Models\Terminal`, tabla `terminales`). Fresco en cada petición vía Eloquent, sin necesitar el refresco manual que hace `legacy/public/index.php` con `$_SESSION`.
- `URLROOT`/`SITENAME`: se mantienen como las mismas constantes legacy (siguen siendo la fuente de las URLs a paginas aun no migradas). Se definen una vez por request en `AppServiceProvider::boot()` (`require_once legacy/app/config/config.php`, sin efectos secundarios) para que cualquier vista Laravel-nativa las tenga disponibles.
- Logout: pasa de link a `legacy/admin/logout` a un formulario POST a la ruta Laravel `logout` (Tarea 4).
- Menú del sidebar: copiado tal cual (mismos links a rutas legacy vía el bridge) — no se reescribieron URLs de negocio en esta fase.

### Tarea 3 — Dashboard en Laravel
`App\Http\Controllers\DashboardController`: reutiliza tal cual la clase legacy `DashboardModel` (9 consultas SQL agregadas ya probadas en producción, cargada con `require_once` directo, sin el autoload legacy que usa rutas relativas y no funciona invocado desde Laravel). El helper legacy `Sucursal` (basado en `$_SESSION`) se reemplaza por su equivalente con `auth()->user()`, más simple porque Eloquent ya es fresco en cada petición.

Vista `resources/views/dashboard/index.blade.php`: puerto casi 1:1 de `dashboard/index.php` (mismo `$data`, mismo Chart.js). Protegida con `auth` + `active` (nuevo middleware, cierra el hallazgo de que Dashboard no requería login).

### Tarea 4 — Logout unificado
`App\Http\Controllers\Auth\LogoutController`: cierra la sesión nativa (`SessionManager::destroy()`, mismo mecanismo que `AuthController` usa para abrirla) **y** el guard de Laravel (`Auth::logout()`), resolviendo el pendiente de la Fase 1 ("logout legacy no cerraba el guard de Laravel").

### Tarea 5 — Cierre
`php artisan test`: 9/9 en verde (incluye `LegacyBridgeTest`, sin regresiones). `php artisan route:list` confirma `dashboard`, `login`/`login.php`, `logout`. Verificado por curl: `/dashboard` sin sesión redirige a `/login.php` (302); resto de módulos (`/ventas`, `/caja`, `/admin`, etc.) no se tocaron, siguen 100% en `legacy/` vía el bridge.

### Pulido visual (pedido por el usuario en la misma sesión, sobre lo ya migrado)
Alcance decidido explícitamente: solo layout + dashboard (lo ya migrado), no las ~30 vistas legacy todavía sin migrar — eso se hace en cada fase futura cuando le toque.
- Iconos del sidebar con color propio por ítem + animación al hover/activo (escala + brillo), en `public/css/custom.css` (el de Laravel — el `legacy/public/css/custom.css` que sigue usando el legacy no se tocó).
- Sidebar móvil: tap target de la hamburguesa a 44px, bloqueo de scroll de fondo mientras está abierto, fade del backdrop, sombra en el panel.
- Tarjetas 3D con degradado en los 4 KPI del dashboard (ingresos, boletos, ocupación, encomiendas), adaptadas del snippet `Tarjetas-3D-Degradado.docx` del usuario (inclinación 3D siguiendo el cursor + brillo radial, un color de degradado por indicador). Se desactiva solo en `prefers-reduced-motion` y sin puntero fino (igual que el snippet original).
- SweetAlert2: login (antes mostraba errores en un `<div class="alert">` plano) ahora los muestra con `Swal.fire`; logout ahora pide confirmación con `Swal.fire` antes de enviar el formulario.

## Pendientes para fases futuras (pedidos en esta misma sesión, aún no implementados)

Estos tres pedidos tocan módulos que siguen siendo 100% legacy (Admin y Ventas, Fases 5 y 3 del roadmap) — por decisión explícita del usuario, el pulido visual/funcional de esta sesión se limitó a lo ya migrado. Quedan anotados aquí para no perderlos cuando les toque:

1. **Fase 3 (Ventas)**: "optimizar al máximo crear rutas pero que encaje bien con el módulo de encomiendas" — revisar `legacy/app/controllers/Ventas.php::crear_ruta` y su vista, y cómo `Encomiendas` reutiliza rutas/tramos, para que ambos módulos compartan el mismo flujo de creación en vez de duplicarlo.
2. **Fase 5 (Admin)**: rediseñar `admin/rutas_paradas` (`legacy/app/views/admin/registrar_rutas_paradas.php`) como una interfaz más gráfica, tipo timeline, para crear rutas con sus paradas.
3. **Fase 5 (Admin)**: rediseñar `admin/registrar_buses` (más simple, menos abrumador) y crear un módulo propio de **registro de choferes** (hoy solo existe registro de buses/características, no un flujo dedicado a personal-chofer) — esto es funcionalidad nueva, no solo estética, así que necesita su propia sesión de diseño (brainstorming) antes de implementar.

## Siguiente paso

Validar visualmente esta fase (login real + `/dashboard` en el navegador) y decidir con el usuario el orden: seguir el roadmap (Fase 3, Ventas) o adelantar alguno de los tres pendientes de arriba.
