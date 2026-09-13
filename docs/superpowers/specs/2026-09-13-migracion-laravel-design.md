# Diseño: Migración a Laravel del sistema de venta de pasajes

**Proyecto:** `venta-pasajes` (BD: `sistema_transportes`)
**Fecha:** 2026-09-13
**Estado:** Aprobado por el usuario para pasar a plan de implementación
**Referencia previa:** `INFORME_TECNICO_ARQUITECTURA_Y_MIGRACION_LARAVEL.md` (auditoría del sistema actual)

---

## 1. Objetivo

Reescribir el sistema actual (PHP puro con MVC casero) en **Laravel**, cumpliendo tres restricciones no negociables del usuario:

1. **Un solo proyecto**: todo ocurre dentro de `C:\xampp\htdocs\venta-pasajes`. No se crea ningún proyecto/carpeta Laravel separado (p. ej. un `pasajes-laravel` aparte).
2. **Una sola base de datos**: se reutiliza `sistema_transportes` tal cual existe. No se crea una base de datos nueva.
3. **Todas las funcionalidades actuales se preservan**: ventas, caja, admin (personal/usuarios/roles/buses/rutas/terminales/series), encomiendas, reportes, backups, RBAC.

El sistema debe seguir operativo (venta de pasajes en curso) durante toda la migración — no hay ventana de mantenimiento.

## 2. Restricciones y decisiones ya tomadas (por el usuario)

| Decisión | Elegido |
|---|---|
| Estructura de carpetas | Reestructurar el directorio actual: el sistema viejo se mueve completo a `legacy/`; Laravel se instala en la raíz con su estructura estándar |
| Continuidad del servicio | Migración incremental módulo por módulo; el sistema viejo sigue como respaldo hasta que cada módulo nuevo esté validado |
| Esquema de base de datos | Se permite corregir con migraciones de Laravel (ALTER, nuevas columnas, backfill), siempre sobre `sistema_transportes`, preservando los datos reales |
| Front-end | Portar las vistas actuales (Bootstrap 5 + AdminLTE + SweetAlert2) a Blade tal cual, sin rediseño visual |

## 3. Arquitectura: patrón strangler fig en un solo directorio

### 3.1 Reorganización de carpetas

Antes de instalar Laravel, todo el árbol actual se mueve a `legacy/`, preservando su estructura interna intacta (para que el dispatcher viejo siga funcionando sin cambios de rutas relativas):

```
C:\xampp\htdocs\venta-pasajes\
├── legacy/
│   ├── app/            (controllers, models, views, core, config, libraries, sql)
│   ├── public/         (assets, UI/, css/, js/, scripts sueltos como print_ticket.php, etc.)
│   ├── ajax_info_viaje.php, ajax_mapa.php, login.php, logout.php, ...
│   ├── conexion.php
│   └── vendor/         (composer viejo: dompdf, phpspreadsheet, phpword — se reemplaza por el vendor/ nuevo fusionado, ver 3.3)
├── app/                 (Laravel: Http/Controllers, Models, Providers, Policies...)
├── routes/              (web.php, api.php)
├── resources/views/     (Blade, incluye el layout AdminLTE portado)
├── database/migrations, database/seeders
├── bootstrap/, config/, storage/
├── public/              (nuevo front controller único: index.php de Laravel + assets copiados de legacy)
├── composer.json         (fusionado)
└── .env                  (credenciales de BD y app, fuera de git/exposición pública)
```

Los 29 scripts de debug/diagnóstico y los backups sueltos (`backups/*.sql`, `*.zip`) **no se mueven al árbol activo**: se archivan aparte (fuera de `public/`) y se documentan como pendientes de borrado definitivo al cerrar la migración.

### 3.2 Enrutamiento puente (dispatcher legacy embebido)

`public/index.php` pasa a ser el único front controller (Laravel). El flujo de una request:

```
Apache (.htaccess raíz sin cambios de fondo)
  → public/index.php (Laravel)
  → bootstrap/app.php → Kernel HTTP → Router de Laravel
      ├─ ruta ya migrada  → Controller\* de Laravel (Eloquent, Blade, middleware auth/CSRF)
      └─ ruta NO migrada  → Route::fallback() → LegacyBridgeController
                              → require legacy/app/bootstrap.php + legacy/app/core/App.php
                              → dispatcha exactamente como hoy (mismo controlador/modelo/vista legacy)
```

`LegacyBridgeController` es el único componente nuevo que "sabe" del sistema viejo: toma la URI restante, la pasa al router legacy (`App.php`) tal cual la interpreta hoy, y devuelve su salida. Esto permite apagar el bridge módulo por módulo sin tocar el código legacy de los módulos aún no migrados.

Ambos lados (Laravel y legacy) comparten:
- **La misma sesión PHP** (incompatibilidad a resolver en la Fase 0 de implementación: `SessionManager` legacy vs `Illuminate\Session` — ver Riesgos, sección 7).
- **La misma conexión a `sistema_transportes`** vía `.env` → `legacy/app/config/config.php` se reduce a leer las mismas variables de entorno (no credenciales duplicadas).

### 3.3 Composer y dependencias

`composer.json` fusiona lo que ya existe (`dompdf/dompdf`, `phpoffice/phpspreadsheet`, `phpoffice/phpword`) con `laravel/laravel`, `laravel/breeze`, `spatie/laravel-permission`, `barryvdh/laravel-dompdf`, `maatwebsite/excel`. Un solo `vendor/` para todo el proyecto (el legacy usa las mismas libs vía autoload de Composer en vez de sus copias manuales de FPDF/PHPMailer, que se reemplazan por las capacidades nativas de Laravel cuando se migra ese módulo).

## 4. Base de datos: una sola, con migraciones correctivas

### 4.1 Baseline (sin alterar nada)

Primera tanda de migraciones: reflejan el esquema **actual** de `sistema_transportes` tal cual existe (30 tablas + 2 vistas), sin modificarlo, solo para que Eloquent tenga modelos funcionales desde el día 1 y las migraciones queden versionadas en git (reemplazando los dumps `.sql` sueltos como fuente de verdad del esquema).

### 4.2 Migraciones correctivas (posteriores, incrementales, reversibles)

Cada una ataca un hallazgo puntual de la auditoría, en su propio módulo de implementación (no todas de una vez):

1. **Fusión `buses` + `vehiculos`** → una sola tabla `buses` con la ficha técnica completa. Script de backfill que copia filas de `vehiculos` a `buses` antes de repuntar las FKs de `viajes.bus_id`. Ejecuta junto con el módulo Admin (paso 5 del roadmap).
2. **Fusión `personal` + `usuarios`** → tabla `personas` única; `usuarios` (auth) pasa a tener `persona_id` opcional 1:1. Backfill que concilia los choferes duplicados y elimina los usuarios "fantasma" (`chofer_13`, etc.), corrigiendo también el rol mal asignado ("Supervisor" en vez de "Chofer").
3. **Normalización de `viajes.estado`** → un solo casing/enum limpio, con migración de datos que mapea los valores duplicados actuales a los canónicos.
4. **Índice único `boletos(viaje_id, numero_asiento)`** para estados activos → evita doble venta de asiento (condición de carrera). Se agrega solo después de verificar que no existen ya filas duplicadas (script de detección previo).
5. **Colación uniforme** `utf8mb4_unicode_ci` en todas las tablas.
6. **Ubigeo y ciudades por FK** en vez de texto libre (departamento/provincia/distrito en `usuarios`/`personal`, origen/destino en `rutas`) — se hace de último por ser la de menor riesgo/urgencia.

Cada migración correctiva va precedida de un backup `.sql` (reutilizando el mecanismo de backup ya existente) y de un conteo de filas antes/después para verificar que no se perdieron datos.

## 5. Orden de migración de módulos (roadmap de implementación)

Cada punto es una entrega verificable e independiente; el módulo se "apaga" del legacy (se quita del fallback) solo cuando su versión Laravel está validada:

1. **Núcleo + Auth + RBAC** — Laravel Auth (Breeze) + `spatie/laravel-permission` mapeado a los 9 roles/13 permisos ya existentes en BD. Cierra el hallazgo crítico de controladores sin `requireAuth()`, porque el middleware exige autenticación por defecto en toda ruta migrada.
2. **Layout base** — AdminLTE/Bootstrap portado a `resources/views/layouts/app.blade.php`, reutilizado por el resto de módulos.
3. **Ventas** — selección de viaje, mapa de asientos, datos de cliente, boleto/ticket (térmico/PDF).
4. **Caja** — apertura/cierre de sesión de caja, movimientos, arqueo, reporte Z.
5. **Admin** — personal, usuarios, roles/permisos, buses, tipos de bus, plantillas de asientos, rutas/paradas, terminales, series de boletos. Aquí se ejecutan las migraciones correctivas 4.2.1 y 4.2.2.
6. **Encomiendas** — registro, tipos, tarifas por ruta, recibo.
7. **Reportes** — financiero, pasajeros, manifiesto de viaje, migrando a `barryvdh/laravel-dompdf` y `maatwebsite/excel` (reutilizando la lógica de negocio, no las libs manuales).
8. **Backup** — reubicado fuera del docroot, solo accesible por rol admin autenticado.
9. **Cierre** — cuando ningún request cae en el `LegacyBridgeController`, se elimina `legacy/`, los 29 scripts de debug y los backups sueltos del árbol público.

## 6. Seguridad (resuelta como efecto colateral de cada migración, no como fase aparte)

- Los controladores hoy sin `requireAuth()` (`Admin`, `Backup`, `Dashboard`, `Encomiendas`, `Reportes`, `Series`, `Vehiculos`) quedan protegidos automáticamente por middleware `auth` + Policies en el momento en que se migran (pasos 1 y 5-8 del roadmap).
- CSRF transversal nativo de Laravel reemplaza la verificación parcial actual.
- Credenciales de BD pasan de `app/config/config.php` en texto plano a `.env` (fuera de git).
- Los scripts de debug no se migran ni se copian al nuevo `public/`.
- `display_errors` se desactiva en producción desde el primer paso (config de Laravel por entorno).

## 7. Riesgos y mitigación

| Riesgo | Mitigación |
|---|---|
| Conflicto de sesión (SessionManager legacy vs sesión de Laravel) mientras ambos coexisten | Se resuelve en el paso 1 (Núcleo+Auth): Laravel gestiona la sesión única; el bridge legacy lee el estado de sesión desde Laravel en vez de crear la suya, evitando doble sesión. Detalle técnico se define en el plan de implementación. |
| Migración correctiva de datos pierde/corrompe filas reales (148 clientes, 76 boletos, caja) | Backup `.sql` obligatorio antes de cada migración estructural + conteo de filas antes/después + migraciones reversibles (`down()`). |
| Un módulo migrado rompe algo que el legacy aún necesita (tablas compartidas) | Orden de roadmap pensado para migrar primero lo que menos comparte estructura (Ventas/Caja) y dejar las fusiones de tablas (buses/vehiculos, personal/usuarios) para cuando ya no dependan de código legacy que las use directamente. |
| El bridge legacy queda "pegado" y nunca se termina de apagar | Cada módulo migrado se marca explícitamente como cerrado (checklist) antes de continuar al siguiente; el cierre final (paso 9) es una entrega propia del roadmap, no un "algún día". |

## 8. Pruebas y validación

- **Por módulo migrado:** checklist manual de las acciones críticas (vender pasaje, abrir/cerrar caja, emitir boleto, generar reporte) comparando contra el comportamiento legacy antes de apagar ese módulo del bridge.
- **Tests automatizados (Feature tests de Laravel):** obligatorios para los flujos de dinero — venta de pasaje y caja — por ser los de mayor impacto si fallan.
- **Integridad de datos:** conteo de filas y checksums puntuales antes/después de cada migración correctiva de BD.

## 9. Fuera de alcance de este spec

- Rediseño visual del UI (se descartó explícitamente; se porta tal cual).
- Nuevas funcionalidades no existentes hoy en el sistema.
- Decisión de si el `LegacyBridgeController` usa un mecanismo distinto a `require` directo (p. ej. proceso separado) — se define en el plan de implementación si aparece como necesario.

---

*Este documento es la base para el plan de implementación (siguiente paso: skill `writing-plans`), que desglosará el roadmap de la sección 5 en tareas concretas, empezando por el paso 1 (Núcleo + Auth + RBAC).*
