# Plan de implementación — Fase 1: Núcleo + Auth + RBAC

**Proyecto:** `venta-pasajes` (BD: `sistema_transportes`)
**Fecha:** 2026-09-13
**Spec de referencia:** `docs/superpowers/specs/2026-09-13-migracion-laravel-design.md` (aprobado)
**Alcance de este plan:** Roadmap punto 1 únicamente — instalar Laravel en la raíz, mover el sistema actual a `legacy/`, levantar el puente de enrutamiento, y migrar Auth + RBAC a Laravel. Ningún otro módulo (Ventas, Caja, Admin, etc.) se toca todavía: siguen sirviéndose 100% desde `legacy/` vía el bridge.

---

## 0. Verificación de hechos usados en este plan

Confirmado contra el código real antes de escribir las tareas (para que el plan no se base en supuestos del spec):

| Hecho | Evidencia |
|---|---|
| PHP 8.2.12 (XAMPP) | compatible con Laravel 11.x |
| `.htaccess` raíz ya reenvía todo a `public/` | no requiere cambios |
| `public/.htaccess` reescribe a `index.php?url=$1` | el bridge debe reproducir esta variable `$_GET['url']` |
| Passwords ya usan `password_hash(..., PASSWORD_BCRYPT)` / `password_verify()` | `Hash::check()` de Laravel funciona sin migrar contraseñas |
| Sesión legacy: `SessionManager` (singleton) usa `session_name('SISTEMA_TRANSPORTES_SESSION')`, `session_start()`, lifetime 28800s, y **ya comprueba `session_status() === PHP_SESSION_ACTIVE` antes de re-iniciar** | esto es lo que hace viable compartir sesión con Laravel sin tocar el código legacy (ver tarea 6) |
| RBAC actual: `usuarios.rol_id` (FK simple, un rol por usuario) → `roles` → `rol_permiso` (pivote) → `permisos` | **no** es multi-rol por usuario; no calza 1:1 con el modelo de `spatie/laravel-permission` (que asume `model_has_roles` polimórfico multi-rol). Ver tarea 9 para cómo se concilia sin romper la decisión ya aprobada de usar spatie. |
| `mysqldump` disponible en `C:\xampp\mysql\bin\mysqldump.exe` | usarlo para el backup de la tarea 1 |
| **El proyecto no tiene repositorio git** (`Is a git repository: false`) | riesgo crítico dado que este plan mueve/reescribe cientos de archivos de un sistema en producción — tarea 1 lo resuelve primero |

---

## Tareas

Cada tarea se hace y se verifica antes de pasar a la siguiente. El sistema debe quedar operativo (ventas en curso) después de cada una.

### Tarea 1 — Red de seguridad: git + backup de BD
- `git init` en la raíz del proyecto, `.gitignore` inicial (`vendor/`, `node_modules/`, `storage/`, `.env`, `backups/*.sql`), primer commit "estado antes de migración a Laravel".
- Backup manual de `sistema_transportes` con `mysqldump` (mecanismo ya existente en `Backup.php`) guardado fuera del árbol que se va a mover.
- **Criterio de aceptación:** `git log` muestra el commit inicial; el `.sql` de backup existe y su tamaño es coherente con los backups previos en `backups/`.

### Tarea 2 — Mover el árbol actual a `legacy/`
- Mover todo lo existente (excepto `.git`, `.htaccess` raíz, y los `.md`/`.sql` de documentación que no son parte del árbol servido) a `legacy/`, preservando su estructura interna intacta, tal como describe la sección 3.1 del spec.
- **No tocar** `.htaccess` raíz (ya reenvía a `public/`, y `public/` pasará a ser el de Laravel).
- Commit: "Fase 1: mover sistema actual a legacy/".
- **Criterio de aceptación:** abriendo el sitio tal cual (todavía sin Laravel instalado) el sistema **no** funcionará — eso es esperado y temporal; se restaura el servicio en la Tarea 4. Este paso se hace en una ventana corta y se sigue directo con la instalación de Laravel.

### Tarea 3 — Instalar Laravel en la raíz (sin `laravel new` directo)
- Crear un Laravel nuevo en una carpeta temporal (`composer create-project laravel/laravel tmp_laravel "11.*"`), luego copiar a la raíz solo lo que la raíz todavía no tiene: `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `artisan`, `public/index.php` + assets base, `.env.example`.
- Fusionar `composer.json`: agregar el `require` de Laravel (framework, breeze si se decide usarlo — ver Tarea 8) al `composer.json` existente que ya tiene `dompdf/dompdf`, `phpoffice/phpspreadsheet`, `phpoffice/phpword`. Un solo `composer install` en la raíz genera un único `vendor/`.
- Borrar `tmp_laravel/`.
- **Criterio de aceptación:** `php artisan --version` funciona desde la raíz; `composer.json` tiene un único bloque `require` con todas las dependencias.

### Tarea 4 — `.env` y conexión a la BD existente
- `.env` con `DB_CONNECTION=mysql`, `DB_DATABASE=sistema_transportes`, `DB_USERNAME=root`, `DB_PASSWORD=` (vacío, igual que `legacy/app/config/config.php`), `APP_URL=http://localhost/venta-pasajes`.
- `legacy/app/config/config.php` pasa a leer las mismas variables vía `getenv()`/`$_ENV` en vez de tener las credenciales hardcodeadas por segunda vez (evita divergencia de credenciales entre los dos lados).
- **Criterio de aceptación:** `php artisan tinker` → `DB::select('select count(*) from usuarios')` devuelve el conteo real de usuarios.

### Tarea 5 — Migraciones baseline (reflejo del esquema actual, sin alterarlo)
- Generar migraciones baseline de las 30 tablas + 2 vistas actuales (vía introspección, p. ej. `php artisan schema:dump` sobre la BD ya poblada, o migraciones escritas a mano tabla por tabla) — **sin** `Schema::create` que borre datos; deben quedar marcadas como ya "aplicadas" (`php artisan migrate:install` + insertar registros en `migrations` o usar `--pretend` para solo versionar el esquema sin ejecutar contra una BD con datos).
- **Criterio de aceptación:** `php artisan migrate:status` no intenta recrear tablas existentes; conteo de filas de `usuarios`, `boletos`, `viajes` antes/después es idéntico.

### Tarea 6 — Puente de sesión (el punto más delicado de la fase)
- `config/session.php`: `'cookie' => 'SISTEMA_TRANSPORTES_SESSION'`, `'lifetime' => 480` (28800s), `'path' => '/'`, `'same_site' => 'lax'`, driver `file`.
- Confirmar que el middleware `StartSession` de Laravel corre **antes** que `LegacyBridgeController` en el pipeline (middleware group `web`, que ya lo incluye por defecto) — esto deja la sesión PHP nativa ya iniciada con ese nombre cuando el bridge llama a `legacy/app/bootstrap.php`.
- Gracias a que `SessionManager::initializeSession()` ya comprueba `session_status() === PHP_SESSION_ACTIVE` antes de reiniciar (ver Tarea 0), el legacy reutilizará la sesión de Laravel automáticamente **sin modificar `SessionManager.php`**. Esto se valida, no se asume.
- **Criterio de aceptación (prueba manual):** loguearse por una vista legacy (temporalmente, antes de la Tarea 8) y confirmar en `dd(session()->all())` desde una ruta Laravel de prueba que ve las mismas claves de sesión que puso el legacy, y viceversa.

### Tarea 7 — `LegacyBridgeController` (fallback)
- `routes/web.php`: `Route::fallback([LegacyBridgeController::class, 'handle'])`.
- El controller: reconstruye `$_GET['url']` a partir de `$request->path()` (replicando lo que hacía `public/.htaccess` del legacy), cambia el directorio de trabajo o usa rutas absolutas basadas en `__DIR__` para requerir `legacy/app/bootstrap.php` y `legacy/app/core/App.php`, y captura la salida con `ob_start()`/`ob_get_clean()` para devolverla como `Response` de Laravel en vez de dejar que el legacy imprima directo.
- Ajustar dentro de `legacy/` cualquier `require`/`include` con ruta relativa (no basada en `__DIR__`) que dependiera de que el script de entrada fuera `legacy/public/index.php` — el bootstrap actual (`app/bootstrap.php`) usa `require_once 'config/config.php'` relativo; verificar que siga resolviendo bien invocado desde el bridge y corregir a `__DIR__ . '/config/config.php'` si no.
- **Criterio de aceptación:** con Laravel ya instalado y sin ninguna ruta propia definida todavía, visitar `/dashboard`, `/ventas`, `/caja`, `/login` (URLs que hoy existen) debe comportarse exactamente igual que antes de la Tarea 2.

### Tarea 8 — Auth de Laravel
- **Decisión de implementación (dentro de lo ya aprobado):** no se usa el scaffolding completo de `laravel/breeze` (trae registro público y reseteo de contraseña por email, que no existen hoy y están fuera de alcance del spec — sección 9). Se usa el paquete solo por su capa de Auth, con un `LoginController` propio y una vista `resources/views/auth/login.blade.php` que replica el formulario legacy actual.
- Modelo `App\Models\Usuario` (`Authenticatable`) apuntando a la tabla `usuarios` existente (`$table='usuarios'`, sin `remember_token` si esa columna no existe, `getAuthPassword()` devolviendo la columna `password`).
- `config/auth.php`: `providers.users.model` → `App\Models\Usuario`.
- Login: `Auth::attempt()` usa `Hash::check()` internamente, compatible sin cambios con los hashes bcrypt ya guardados (confirmado en la Tarea 0).
- **Criterio de aceptación:** login/logout vía Laravel funcionan contra usuarios reales existentes; la sesión resultante es la misma que consume el legacy (Tarea 6), permitiendo que rutas aún no migradas (todo, en esta fase) sigan reconociendo al usuario logueado.

### Tarea 9 — RBAC con `spatie/laravel-permission` sobre el esquema existente
- Se mantiene la decisión ya aprobada de usar `spatie/laravel-permission`, conciliando que hoy es "un rol por usuario" (no multi-rol):
  - Migraciones propias de spatie crean sus tablas (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) — **como el nombre `roles` choca con la tabla legacy `roles`**, se publican con prefijo (config `table_names`) para no pisar la tabla existente, p. ej. `spatie_roles`, `spatie_permissions`, etc.
  - Un seeder (`RbacSyncSeeder`) lee `roles` + `permisos` + `rol_permiso` (fuente de verdad, sin tocarla) y sincroniza hacia las tablas de spatie: crea un `Spatie\Permission\Models\Role` por cada fila de `roles.nombre`, un `Permission` por cada `permisos.clave`, y los vincula igual que `rol_permiso`. Se corre una vez y se puede re-correr (idempotente) si el admin edita roles/permisos desde el módulo Admin legacy (que sigue siendo la única UI de administración de roles hasta la Fase 5 del roadmap).
  - Al loguear (`LoginController`), además de `Auth::login()`, se asigna al usuario autenticado el rol de spatie correspondiente a su `usuarios.rol_id` (`$user->syncRoles([$rolNombre])`), manteniendo el modelo "un rol por usuario" tal cual existe hoy aunque spatie soporte más.
- Middleware `role:` / `permission:` de spatie disponible para las rutas Laravel que se migren desde la Fase 3 en adelante (en esta fase no hay rutas de negocio propias todavía, solo login).
- **Criterio de aceptación:** para cada uno de los 9 roles reales, `auth()->user()->can('<clave_permiso>')` devuelve el mismo resultado que la lógica de permisos legacy para ese rol (comparar contra `rol_permiso` directamente en un script de verificación).

### Tarea 10 — Cierre de fase
- Checklist manual: login, logout, navegación a cada módulo existente (ventas, caja, admin, encomiendas, reportes, backup) sigue funcionando igual que antes de esta fase (todo sirviéndose vía `LegacyBridgeController`).
- Commit final de la fase; documentar en este plan qué quedó pendiente para la Fase 2 (Layout base AdminLTE → Blade).
- **Criterio de aceptación:** cero regresiones detectadas en el checklist; el único cambio visible para un usuario final es que login/logout ahora corren en código Laravel.

---

## Estado de ejecución (actualizado 2026-09-13)

| Tarea | Estado |
|---|---|
| 1–5 | Hechas (ver commits) |
| 6 — Puente de sesión | Hecha con otro mecanismo: Laravel NO comparte su motor de sesión con el legacy. `AuthController` abre la sesión nativa `SISTEMA_TRANSPORTES_SESSION` con la propia clase `SessionManager` y escribe las mismas claves que `legacy/login.php`. La sesión de Laravel (`venta_pasajes_laravel_session`) solo se usa para CSRF/flash del login. |
| 7 — `LegacyBridgeController` | Hecha, **en producción** (el `.htaccess` raíz apunta a `public/` de Laravel). Cambió respecto al diseño: en vez de `require` en el mismo proceso (falla silenciosa con body vacío bajo Apache/mod_php en Windows) hace una petición HTTP loopback a `/__legacy/...`. Detalles abajo. |
| 8 — Auth Laravel | Hecha (`routes/auth.php`, modelos `Usuario`/`Rol`/`Permiso`). |
| 9 — spatie RBAC | Hecha: tablas `spatie_*` (prefijo, las legacy `roles`/`permisos` no se tocan), `App\Services\LegacyRbacSync`, comando `php artisan rbac:sync [--verify]` (286 comprobaciones usuario × permiso idénticas a `rol_permiso`), sincronización del usuario en cada login. Backup previo: `backups/backup_db_2026-09-13_20-03_pre_tarea9_spatie.sql`. |
| 10 — Cierre | Navegación verificada automáticamente (paridad bridge vs legacy, anónimo y con sesión). Falta la prueba manual de un login real exitoso con credenciales de un usuario. |

### Detalles del puente (Tarea 7) que no estaban en el diseño

- **Catch-all de todos los métodos** (`routes/legacy.php`, `Route::any`), no `Route::fallback`: `fallback` solo acepta GET/HEAD y dejaba **todos los POST** (ventas, caja, AJAX) respondiendo 405.
- **Fuera del grupo `web`** (registrado con `then:` en `bootstrap/app.php`): sin CSRF de Laravel (el legacy trae el suyo), sin sesión de Laravel y sin cifrado de cookies, para que `SISTEMA_TRANSPORTES_SESSION` viaje intacta en ambos sentidos.
- **Cuerpo crudo**: JSON/form se reenvían tal cual (el legacy lee `php://input` en Ventas, Boletos, Admin, ControladorTransacciones). Multipart se reconstruye desde `$_POST`/archivos (PHP no expone el cuerpo crudo multipart).
- **Loopback fijo a 127.0.0.1** (configurable con `LEGACY_BRIDGE_URL`), timeout `LEGACY_BRIDGE_TIMEOUT` (300 s por defecto) y 504 si el legacy no responde.
- **Tests**: `tests/Feature/LegacyBridgeTest.php` (Http::fake, sin BD).

### Endurecimiento hecho al pasar a producción

- `.htaccess` raíz: TODO request externo va a `public/` (antes `.env`, `composer.json`, `config/`, `routes/`, `database/`, `legacy/` y los `.md` de la raíz se podían descargar). `/__legacy` solo se acepta desde loopback.
- `legacy/public/.htaccess`: rechaza (403) el acceso directo externo; solo se llega por la reescritura interna.
- `.env`: `APP_ENV=production`, `APP_DEBUG=false`; `APP_KEY` regenerada porque había estado expuesta.
- `public/.htaccess`: quitada la redirección de barra final de Laravel (mandaba `/ventas/` a `/public/ventas`).
- `public/assets/js/impresion-ticket.js` copiado (vivía en `legacy/assets/`, fuera de todo docroot desde la Tarea 2).
- Token CSRF vencido en el login → vuelve al login con aviso en vez de la página 419.
- `PDO::ATTR_PERSISTENT` desactivado en `legacy/app/core/Database.php`.

### Rollback

`git show 69b1c90:.htaccess > .htaccess` vuelve a servir 100% desde `legacy/public/` (la reescritura es interna, así que el bloqueo de acceso directo en `legacy/public/.htaccess` no la afecta).

### Pendientes conocidos

- Logout legacy (`/admin/logout`) destruye la sesión nativa pero no la del guard de Laravel (`Auth::login`). Hoy no importa (ninguna ruta usa `auth`), pero debe resolverse antes de proteger rutas migradas con middleware `auth` (Fase 2/3): migrar el logout a Laravel o hacer que el guard valide contra la sesión nativa.
- Paridad heredada, no introducida: `/dashboard`, `/ventas`, `/admin` y otros controladores legacy responden sin login (hallazgo de la auditoría; se cierra al migrar cada módulo con `auth`).
- Scripts de diagnóstico (`public/clear_cache.php`, `diagnostico_caja.php`, `diagnostico_rutas.php`) siguen accesibles por paridad; retirarlos cuando se confirme que nadie los usa.
- MySQL `root` sin contraseña (heredado de `legacy/app/config/config.php`).

---

## Riesgos específicos de esta fase (más allá de los del spec)

| Riesgo | Mitigación |
|---|---|
| Rutas relativas dentro de `legacy/app/*.php` que asumían ser invocadas desde `legacy/public/index.php` | Tarea 7: auditoría y corrección puntual a `__DIR__`-based antes de dar la fase por cerrada |
| Colisión de nombre de tabla `roles`/`permisos` entre legacy y spatie | Tarea 9: tablas de spatie con prefijo propio vía config, nunca se tocan las tablas legacy |
| Admin edita roles/permisos desde el módulo legacy y la sincronización a spatie queda desactualizada | Tarea 9: seeder de sync re-ejecutable; documentar como limitación conocida hasta que el módulo Admin se migre (Fase 5) y spatie pase a ser la única fuente de verdad |
| Sin repo git antes de empezar (proyecto en producción) | Tarea 1 lo resuelve primero, antes de mover un solo archivo |

---

*Siguiente paso tras validar esta fase: planificar la Fase 2 (Layout base AdminLTE portado a `resources/views/layouts/app.blade.php`) como documento separado.*
