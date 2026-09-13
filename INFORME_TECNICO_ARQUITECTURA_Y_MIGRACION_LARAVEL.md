# Informe Técnico — Sistema de Venta de Pasajes

**Proyecto:** `venta-pasajes` (BD: `sistema_transportes`)
**Fecha:** 2026-09-13
**Autor del análisis:** Claude Code
**Objetivo:** Analizar la aplicación actual y proponer una reconstrucción en **Laravel + MySQL** con una base de datos relacional bien normalizada.

---

## 1. Resumen ejecutivo

Es un sistema de **venta de pasajes y encomiendas para transporte terrestre** (buses), construido en **PHP puro con un mini-framework MVC casero**, corriendo sobre **XAMPP (Apache + MariaDB 11.3)**. Funciona, tiene módulos ricos (venta, caja, buses, rutas, encomiendas, reportes, backups), pero arrastra **deuda técnica alta** y **problemas de seguridad graves**.

**Veredicto:** el modelo de negocio y el esquema de datos son una base sólida y reaprovechable. La **capa de aplicación conviene reescribirla en Laravel**; la base de datos conviene **rediseñarla y re-normalizarla** (hay duplicaciones estructurales importantes), migrando los datos existentes.

| Dimensión | Estado actual | Riesgo |
|---|---|---|
| Arquitectura | MVC casero, sin router real, sin ORM | Medio |
| Seguridad | **Sin control de acceso en varios controladores; credenciales en texto; scripts de debug expuestos** | **Crítico** |
| Base de datos | Relacional con FKs, pero con tablas duplicadas y datos no normalizados | Alto |
| Calidad de código | ~7.900 líneas en controllers/models; 76 docs .md sueltos; 29 scripts de debug en la raíz | Alto |
| Mantenibilidad | Baja (lógica mezclada, vistas de 2.000+ líneas) | Alto |

---

## 2. Cómo está construida la aplicación

### 2.1 Stack

- **Lenguaje:** PHP (sin framework; MVC propio en `app/core/`).
- **Servidor:** Apache (XAMPP) en Windows, con `mod_rewrite`.
- **Base de datos:** MariaDB **11.3.2**, motor **InnoDB**, charset `utf8mb4` (mezcla de `unicode_ci` y `general_ci`).
- **Front-end:** Bootstrap 5 + plantilla **AdminLTE**, jQuery, **SweetAlert2**, Font Awesome (todo por CDN).
- **Librerías (Composer):** `dompdf/dompdf`, `phpoffice/phpspreadsheet`, `phpoffice/phpword`. Además, incrustadas a mano: **FPDF** y **PHPMailer** dentro de `app/libraries/`.

### 2.2 Arquitectura MVC casera

Flujo de una petición:

```
Apache (.htaccess) → public/index.php → app/bootstrap.php → App (router)
   → Controlador → Model (extiende PDO wrapper Database) → View (require de .php)
```

- **`app/core/App.php`** — "router". Parte la URL en `controlador/metodo/parametros` y hace `call_user_func_array`. **Sin rutas declaradas** (todo por convención de nombres de archivo/método).
- **`app/core/Controller.php`** — base; métodos `model()` y `view()` que hacen `require_once` directo.
- **`app/core/Database.php`** — wrapper PDO con sentencias preparadas (`query/bind/execute`). **Bien:** usa prepared statements → no se detectó SQL injection por interpolación.
- **`app/core/SessionManager.php`** — Singleton de sesión. **Bien hecho:** cookie `httponly`/`samesite=Lax`, timeout por inactividad, regeneración de ID, y genera **token CSRF**.
- **Controladores** (`app/controllers/`): `Admin, Backup, Boletos, Caja, Configuracion, ControladorTransacciones, Dashboard, Encomiendas, Reportes, Series, Vehiculos, Ventas`.
- **Modelos** (`app/models/`): `Asiento, Asignacion, Caja, Configuracion, Dashboard, Encomienda, Personal, Reporte, RolPermiso, Ruta, SerieBoleto, Terminal, TipoBus, Usuario, Vehiculo`.

### 2.3 Módulos funcionales

1. **Autenticación** — `login.php` (email + `password_verify` con bcrypt). Logout en `logout.php`.
2. **Ventas de pasajes** — selección de viaje, mapa de asientos dinámico, cliente, precio por parada, boleto + ticket (térmico/PDF).
3. **Caja** — apertura/cierre de sesión de caja, movimientos, arqueo, reporte Z.
4. **Administración** — personal, usuarios, roles y permisos, buses, tipos de bus, plantillas de asientos, rutas y paradas, terminales, series de boletos.
5. **Encomiendas** — registro, tipos, tarifas por ruta, recibo.
6. **Reportes** — financiero, pasajeros, manifiesto de viaje (PDF/Excel/Word).
7. **Backups** — genera dumps `.sql` y `.zip` (ya hay 5 pares en `/backups`).
8. **Roles y permisos** — modelo RBAC con tablas `roles`, `permisos`, `rol_permiso` (9 roles, 13 permisos, 24 asignaciones) + vistas `v_permisos_por_rol`, `v_resumen_roles`.

### 2.4 Estado real de la base de datos (en vivo)

30 tablas + 2 vistas. Datos representativos:

| Tabla | Filas | Tabla | Filas |
|---|---|---|---|
| clientes | 148 | boletos | 76 |
| movimientos_caja | 62 | rutas_paradas | 31 |
| usuarios | 22 | personal | 21 |
| viajes | 15 | tipos_buses | 12 |
| vehiculos | 10 | rutas | 7 |
| distritos | 333 | provincias | 112 |

Hay **datos reales en producción** (148 clientes, 76 boletos, caja con 62 movimientos): **cualquier migración debe preservarlos**.

---

## 3. Hallazgos de seguridad (prioridad máxima)

> Ordenados por gravedad. Los tres primeros son **críticos**.

### 3.1 🔴 CRÍTICO — Controladores sin control de acceso
Varios controladores **instancian** `SessionManager` pero **nunca llaman** `requireAuth()` ni verifican autenticación/rol. Confirmado en: `Admin`, `Backup`, `Dashboard`, `Encomiendas`, `Reportes`, `Series`, `Vehiculos`.

Consecuencia: **el panel de administración** (alta de personal, gestión de usuarios, roles, buses) y **los backups de toda la base** son accesibles **sin iniciar sesión**, escribiendo la URL directamente (ej. `/admin`, `/backup`). El RBAC existe en la BD pero **no se aplica** de forma sistemática en el código.

### 3.2 🔴 CRÍTICO — 29 scripts de debug/diagnóstico expuestos en la raíz web
Archivos como `diagnostico_sistema.php`, `debug_asignaciones.php`, `inject_demo_data.php`, `fix_phantom_users.php`, `sync_drivers_users.php`, `test_transaction_endpoint.php` están en la raíz pública y muchos ejecutan consultas o modificaciones **sin autenticación**. Además `debug_errors.log` y `email_errors.log` quedan accesibles.

### 3.3 🔴 CRÍTICO — Credenciales y configuración en el repositorio
`app/config/config.php` tiene `DB_USER=root`, `DB_PASS=''` en claro. Los **dumps completos de la BD** (`backups/*.sql`, con hashes de usuarios y 148 clientes) y los `.zip` del sitio están dentro del árbol accesible por web.

### 3.4 🟠 ALTO — `display_errors` activo
`login.php` (y otros) hacen `ini_set('display_errors', 1)` y en error de PDO muestran el mensaje al usuario → fuga de detalles internos.

### 3.5 🟠 ALTO — CSRF parcial
El token CSRF se genera y algunos controladores lo verifican (`Admin`, `Caja`, `ControladorTransacciones`, `Ventas`), pero **no es transversal**: los demás endpoints POST no lo comprueban.

### 3.6 🟡 MEDIO — Cookies sin `Secure` / sin HTTPS
`SessionManager` fija `secure=false` (correcto en local, pero debe forzarse `true` + HSTS en producción).

**Lo que SÍ está bien:** bcrypt para contraseñas, PDO con prepared statements (sin SQLi por concatenación), y una gestión de sesión razonable.

---

## 4. Análisis de la base de datos y normalización

El esquema **tiene claves foráneas** (buena señal), pero hay problemas de diseño serios:

### 4.1 🔴 Duplicación estructural: `buses` vs `vehiculos`
Existen **dos tablas para lo mismo**:
- `buses` (placa, marca, plantilla_id, tipo_bus_id, chofer_default_id) — 5 filas.
- `vehiculos` (placa, marca, ~25 columnas de ficha técnica, tipo_servicio, asientos) — 10 filas.

Y encima **incoherencia de FKs**: `viajes.bus_id` → **`vehiculos`**, pero `buses.tipo_bus_id`/`plantilla_id` viven en `buses`. Es la fuente probable de los innumerables documentos de "bus no disponible / bus doble piso" que hay en el repo. **Debe unificarse en una sola entidad `buses`.**

### 4.2 🔴 Duplicación de identidad: `personal` vs `usuarios`
Los choferes están **duplicados** en `personal` (21 filas) y en `usuarios` (con usuarios "fantasma" `chofer_13`, `chofer_18@sistema.temp`, etc.), y hay scripts `sync_drivers_users.php` / `fix_phantom_users.php` para "sincronizarlos" a mano. Además las FKs apuntan a ambos lados: `asignaciones_buses.chofer_id` → `personal`, pero `viajes.chofer_id` → `usuarios`. **Debe existir un único modelo de persona/empleado**, con la cuenta de acceso como extensión opcional.

### 4.3 🟠 Roles mal asignados en datos
Todos los choferes reales están grabados con rol **"Supervisor"** en `usuarios` (rol_id=3), no con el rol Chofer. Hay inconsistencia entre el catálogo de roles y su uso real.

### 4.4 🟠 Datos no normalizados (1FN/3FN)
- `usuarios` y `personal` guardan `departamento/provincia/distrito` como **texto libre**, existiendo ya tablas `departamentos`/`provincias`/`distritos` (ubigeo). No se referencian por FK.
- `rutas.origen`/`destino` son texto, existiendo tabla `ciudades`/`terminales`.
- `viajes.estado` es un `enum` con **valores duplicados por mayúsculas** (`'Activo','Programado','Inactivo','programado','abordando'...`) → caos de estados.
- `viajes.servicios_incluidos` es **JSON dentro de un TEXT** (mezcla relacional/documental).
- Colaciones mezcladas (`utf8mb4_general_ci` vs `unicode_ci`) → riesgo de errores en JOINs por comparación de strings.

### 4.5 🟡 Falta integridad en puntos clave
`boletos` no tiene índice único sobre `(viaje_id, numero_asiento)` para estados activos → **riesgo de vender el mismo asiento dos veces** (condición de carrera).

---

## 5. Propuesta: reconstrucción en Laravel

### 5.1 Stack objetivo

| Capa | Tecnología |
|---|---|
| Framework | **Laravel 11** (PHP 8.3+) |
| ORM | **Eloquent** + migraciones + seeders |
| Auth | **Laravel Breeze/Fortify** + **Policies/Gates** para RBAC |
| Permisos | `spatie/laravel-permission` (roles y permisos ya existen en la BD) |
| Front | Blade + Bootstrap/AdminLTE (rápido de portar) o Livewire/Inertia (más moderno) |
| PDF/Excel | `barryvdh/laravel-dompdf`, `maatwebsite/excel` |
| Correo | Mailer nativo de Laravel (reemplaza PHPMailer manual) |
| BD | MySQL 8 / MariaDB, InnoDB, `utf8mb4_unicode_ci` **uniforme** |

### 5.2 Modelo de datos re-normalizado (propuesto)

Entidades núcleo (Eloquent models):

```
ubigeo:        departamentos → provincias → distritos   (FK reales)
personas:      personas (datos personales, ubigeo por FK)
usuarios:      users (auth) → persona_id (1:1 opcional), rol vía spatie
rbac:          roles, permissions, model_has_roles, role_has_permissions
flota:         buses (UNIFICA buses+vehiculos) → tipo_bus_id, plantilla_id
asientos:      plantillas_bus → asientos_plantilla
geografía:     ciudades, terminales
rutas:         rutas (origen_ciudad_id, destino_ciudad_id) → rutas_paradas (precios)
operación:     viajes (ruta_id, bus_id, chofer_id, estado ENUM limpio)
asignaciones:  asignaciones_buses (bus, chofer, copiloto → personas)
ventas:        clientes, boletos (UNIQUE viaje+asiento activo)
caja:          cajas_sesiones → movimientos_caja
encomiendas:   encomienda_tipos, tarifas_encomienda, encomiendas → detalles
config:        configuracion_sistema, series_boletos
```

Correcciones clave respecto al esquema actual:
1. **Una sola tabla de flota** (`buses`), fusionando la ficha técnica de `vehiculos`.
2. **Una sola identidad de persona** (`personas`), con `users` como cuenta opcional 1:1.
3. **Ubigeo y ciudades por FK** en lugar de texto libre (3FN).
4. **`viajes.estado`** como ENUM/estados normalizados (un solo casing) o tabla de estados.
5. **Restricción única** `boletos (viaje_id, numero_asiento)` para asientos vendidos/reservados.
6. **Colación única** `utf8mb4_unicode_ci` en todas las tablas.
7. Migrar `servicios_incluidos` a tabla `viaje_servicios` (o `cast` a JSON explícito si se prefiere documental).

### 5.3 Plan de migración por fases

**Fase 0 — Contención de seguridad (1-2 días, sobre el sistema ACTUAL):**
- Añadir `requireAuth()` + verificación de rol en **todos** los controladores.
- Sacar los 29 scripts de debug de la raíz web (moverlos fuera del docroot o borrarlos).
- Mover `/backups` y `config.php` fuera del directorio público; poner clave a MySQL.
- `display_errors=0` en producción; loggear a archivo protegido.

**Fase 1 — Andamiaje Laravel (3-5 días):**
- `laravel new`, configurar `.env` (credenciales fuera del repo), instalar Breeze + spatie/permission.
- Generar migraciones del **esquema re-normalizado**.

**Fase 2 — Migración de datos (2-4 días):**
- Scripts de ETL para pasar los datos reales (148 clientes, 76 boletos, caja, viajes) al nuevo esquema, resolviendo la fusión `buses+vehiculos` y `personal+usuarios`, y mapeando ubigeo texto→FK.

**Fase 3 — Reescritura de módulos (iterativa):**
- Orden sugerido por valor/uso: Auth+RBAC → Ventas → Caja → Admin (flota/rutas) → Encomiendas → Reportes → Backups.
- Portar vistas AdminLTE a Blade (reutilizables casi tal cual).

**Fase 4 — Endurecimiento y despliegue:**
- HTTPS + cookies `Secure` + HSTS, CSRF nativo de Laravel (transversal), validación de formularios con Form Requests, tests de funcionalidad crítica (venta y caja), y pipeline de backups fuera del docroot.

### 5.4 Ganancias esperadas

- **Seguridad por defecto**: middleware de auth, CSRF global, validación, hashing y sesiones gestionadas por el framework.
- **RBAC real y transversal** con `spatie/laravel-permission` (aprovecha tus 9 roles / 13 permisos).
- **Integridad de datos** con FKs coherentes y restricciones únicas → se acaban los "bus no disponible" y la doble venta de asiento.
- **Mantenibilidad**: fin de las vistas de 2.000+ líneas, de los 29 scripts de parche y de los 76 .md de diagnóstico.
- **Migraciones/seeders versionados** en git en lugar de dumps `.sql` sueltos.

---

## 6. Recomendaciones inmediatas (aunque no migres aún)

1. **Poner contraseña a MySQL** y sacarla del código (variable de entorno).
2. **Cerrar el acceso sin login** al panel admin y a `/backup` (Fase 0).
3. **Eliminar/mover** los 29 scripts de debug y los backups del directorio público.
4. **Corregir la duplicación buses/vehiculos** o al menos documentar cuál es la tabla "verdad".
5. **Unificar el casing de `viajes.estado`** para estabilizar la operación.

---

*Notas: no es posible recuperar contraseñas en texto (bcrypt, irreversible). El administrador del sistema es el usuario id=1, `hsurcoa@gmail.com`. Para acceder sin conocer la clave, la vía correcta es resetear el hash a una contraseña temporal.*
