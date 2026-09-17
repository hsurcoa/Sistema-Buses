# Auditoría de normalización — `sistema_transportes`

**Fecha:** 2026-09-17 (actualizado el mismo día tras ejecutar las correctivas de bajo riesgo)
**Tipo original:** Solo lectura. Confirma y actualiza los hallazgos de la auditoría original citada en el spec (`INFORME_TECNICO_ARQUITECTURA_Y_MIGRACION_LARAVEL.md`, sección 4.2 del spec de migración).

## Estado de cada hallazgo (actualizado)

| # | Hallazgo | Estado |
|---|---|---|
| 1 | `buses` duplicaba `vehiculos` | ✅ Resuelto — tabla `buses` eliminada (`2026_09_17_100200_elimina_tabla_buses_huerfana.php`), estaba vacía y huérfana |
| 2 | `personal`/`usuarios` duplicadas | ⏳ Pendiente — toca login/auth, ver sección al final |
| 3 | Ubigeo sin FK | ⏳ Pendiente — depende del mismo trabajo que el punto 2 (mismas tablas) |
| 4 | Colación mixta | ✅ Resuelto — 11 tablas convertidas a `utf8mb4_unicode_ci` (`2026_09_17_100000_normaliza_colacion_utf8mb4_unicode.php`) |
| 5 | `boletos` sin índice único anti-doble-venta | ✅ Resuelto — índice único sobre columna generada (`2026_09_17_100100_indice_unico_boletos_asiento_activo.php`) |
| 6 | `viajes.estado` casing inconsistente | ✅ Resuelto — `RutaService::listarViajesProgramados()` normalizado a `LOWER()`, igual que el resto de comparaciones |
| 7 | RBAC duplicado (spatie + legacy) | Intencional, sin cambios — se colapsa en el cierre final |

Las 3 migraciones correctivas de bajo riesgo se ejecutaron con backup previo (`backups/backup_pre_migraciones_correctivas_*.sql`) y verificación de conteo de filas antes/después (sin cambios: la instancia sigue prácticamente vacía). `legacy/` y el puente (`LegacyBridgeController`) también se eliminaron — ver `docs/superpowers/plans/2026-09-17-eliminacion-php-puro.md`.

## Estado actual de los datos (importante para dimensionar el riesgo)

La instancia local (`sistema_transportes`) está prácticamente vacía: `usuarios` tiene **1 fila** (la cuenta del propio dueño, `hsurcoa@gmail.com`); `viajes`, `boletos`, `clientes`, `rutas`, `terminales`, `personal`, `buses`, `vehiculos`, `cajas_sesiones`, `movimientos_caja`, `encomiendas` tienen **0 filas cada una** (con `AUTO_INCREMENT` en 1, es decir nunca se insertó nada — no es un borrado). Las tablas de referencia sí están sembradas (`roles`, `permisos`, `departamentos`, `provincias`, `distritos`, `configuracion_sistema`).

Esto reduce mucho el riesgo de ejecutar las migraciones correctivas del spec (4.2) **en esta instancia**: no hay boletos ni clientes reales que backfillear o perder. Si el despliegue real (producción) tiene datos, todo lo de abajo sigue aplicando pero con la disciplina de backup + conteo de filas que ya exige el spec.

## Hallazgos confirmados

### 1. `buses` y `vehiculos`: dos tablas para el mismo concepto, y la app usa la que no es

Ambas existen con columnas propias de "bus" (placa, tipo_bus_id, capacidad/asientos, estado). Pero las FK reales de negocio apuntan a `vehiculos`, no a `buses`:

- `viajes.bus_id -> vehiculos.id`
- `asignaciones_buses.bus_id -> vehiculos.id`
- Todo `RutaModel` (venta, mapa de asientos, tripulación) hace `JOIN vehiculos`, nunca `JOIN buses`.

`buses` parece una tabla huérfana (nada le apunta desde `viajes`/`asignaciones_buses`) y además tiene un bug propio: `buses.chofer_default_id -> usuarios.id`, cuando los choferes viven en `personal` (confirmado por `viajes.chofer_id -> personal.id` y `asignaciones_buses.chofer_id -> personal.id`). Si algo llegara a usar `buses.chofer_default_id` apuntaría a la tabla equivocada.

**Corrección (spec 4.2.1):** fusionar en una sola tabla `buses` con la ficha técnica completa de `vehiculos`, backfill de filas, y repuntar `viajes.bus_id`/`asignaciones_buses.bus_id`. Como ambas están vacías en esta instancia, es un buen momento para hacerlo sin riesgo de backfill.

### 2. `personal` y `usuarios`: dos tablas de personas con tipos inconsistentes entre sí

Comparten semántica (nombres, apellidos, documento, género, fecha de nacimiento, celular, dirección, ubigeo, foto, estado) pero con tipos distintos para lo mismo:

| Columna | `usuarios` | `personal` |
|---|---|---|
| `estado` | `enum('activo','inactivo')` | `tinyint(1)` |
| `tipo_documento` | `enum('DNI','CI','Pasaporte')` | `varchar(20)` libre |
| `genero` | `enum('M','F')` | `varchar(20)` libre |

`usuarios` es cuentas de acceso al sistema (login); `personal` es la ficha de choferes/staff operativo — pero un chofer que también tiene login (caso típico) queda representado en dos filas separadas sin relación formal entre sí (no hay `personal.usuario_id` ni `usuarios.persona_id`), lo que ya el spec original documentó como origen de "usuarios fantasma" (`chofer_13`, etc.) y de roles mal asignados.

**Corrección (spec 4.2.2):** tabla `personas` única; `usuarios` (auth) con `persona_id` opcional 1:1. Backfill que concilia duplicados.

### 3. Ubigeo como texto libre, no como catálogo con FK, a pesar de que el catálogo ya existe

`departamentos` (9 filas) → `provincias` (112, con FK a `departamentos`) → `distritos` (333, con FK a `provincias`) ya están correctamente normalizadas entre sí. Pero **nadie las referencia**: `usuarios.departamento/provincia/distrito` y `personal.departamento/provincia/distrito` son `varchar` de texto libre, independientes del catálogo (de hecho el único usuario real tiene literalmente `"departamento": "Seleccione"` — el placeholder del `<select>` quedó guardado como dato).

**Corrección (spec 4.2.6):** agregar FKs `distrito_id` en `usuarios`/`personal` hacia `distritos`, con backfill por coincidencia de texto donde sea posible y limpieza manual del resto.

### 4. Colación mixta entre tablas que se cruzan en JOINs

12 tablas quedaron en `utf8mb4_general_ci` mientras el resto (incluida `usuarios`) está en `utf8mb4_unicode_ci`: `detalles_encomienda`, `encomienda_tipos`, `encomiendas`, `personal`, `rutas`, `rutas_paradas`, `series_boletos`, `tarifas_encomienda`, `tarifas_tramo`, `terminales`, `vehiculos`. Varias de estas se comparan/ordenan junto a tablas `unicode_ci` en las consultas de `RutaModel` (p. ej. `personal` con `usuarios`/`viajes`), lo que puede producir errores "Illegal mix of collations" en comparaciones de texto o diferencias silenciosas de orden/mayúsculas.

**Corrección (spec 4.2.5):** `ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` en las 12 tablas listadas.

### 5. `boletos`: sin índice único que impida la doble venta de asiento a nivel de BD

Índices actuales: `PRIMARY(id)`, `UNIQUE(codigo_boleto)`, más índices simples (no únicos) en `viaje_id`, `cliente_id`, `usuario_vendedor_id`, `sesion_caja_id`, `sucursal_id`. **No hay ningún índice único sobre `(viaje_id, numero_asiento)`.**

Hoy la prevención de doble venta es solo a nivel de aplicación: `RutaModel::registrarVentaTransaccion()` hace `SELECT ... FOR UPDATE` dentro de una transacción antes de insertar. Funciona mientras **todo** el código pase por ese método — pero es una garantía de aplicación, no de esquema; cualquier inserción futura que no pase por ahí (script de backfill, corrección manual, otro endpoint) podría crear una doble venta sin que la BD lo impida.

**Corrección (spec 4.2.4):** índice único filtrado a estados activos (`vendido`/`reservado`) sobre `(viaje_id, numero_asiento)`, previa verificación de que no existan ya filas duplicadas (hoy no puede haberlas: la tabla está vacía).

### 6. `viajes.estado`: el propio código no es consistente sobre su casing

No se pudo observar en datos (tabla vacía), pero el código sí muestra la inconsistencia que el spec 4.2.3 describe: `RutaModel::listarViajesProgramados()` compara `v.estado NOT IN ('Finalizado', 'Cancelado')` (capitalizado), mientras que `RutaModel::guardarRutaViaje()` (chequeo de choque de horario del chofer) compara `LOWER(v.estado) NOT IN ('finalizado', 'cancelado', 'inactivo')`. Si en algún momento se guarda `estado` en minúsculas, el primer filtro deja de funcionar silenciosamente.

**Corrección (spec 4.2.3):** normalizar a un enum/casing único y actualizar todas las comparaciones.

### 7. RBAC duplicado — intencional, no es un hallazgo nuevo

`roles`/`permisos`/`rol_permiso` (el que usa `usuarios.rol_id`, activo hoy) coexiste con `spatie_roles`/`spatie_permissions`/`spatie_model_has_roles`/`spatie_role_has_permissions` (paquete `spatie/laravel-permission`, mantenido sincronizado por `App\Services\LegacyRbacSync` desde el login — ver Fase 1). Es la duplicación deliberada del período de transición; se colapsa en el paso 9 del roadmap (cierre) cuando ninguna ruta dependa ya de las tablas legacy de roles.

## Qué falta: fusión `personal` + `usuarios` (y el ubigeo, que depende de ella)

No ejecutada — a diferencia de las otras 3, esta **toca la tabla `usuarios` que usa el login** (`AuthController`, guard de Laravel, sincronización RBAC con spatie) y cascada por al menos 6 archivos ya escritos hoy (`Personal`/`Usuario` Eloquent, `PersonalService`, `UsuarioAdminService`, `RutaService` y `AsignacionService` — ambos resuelven choferes vía `Personal` —, y las vistas `personal_form`/`usuarios`/`asignar_buses`). Es un cambio de esquema real (no aditivo como las 3 ya hechas), y no tengo forma de probar el login resultante en un navegador real yo mismo. Se preguntó explícitamente si continuar y no hubo respuesta a tiempo — se deja documentado en vez de asumir.

**Plan concreto para cuando se retome** (con el usuario disponible para probar login apenas termine):
1. Migración: tabla `personas` única (columnas de `personal` + las que le faltan de `usuarios` que no sean de auth: ninguna extra en la práctica, `usuarios` ya es subconjunto). `usuarios` pasa a tener `persona_id` FK opcional 1:1; se le quitan las columnas de datos personales duplicadas (nombres/apellidos/genero/fecha_nacimiento/celular/direccion/foto/departamento/provincia/distrito quedan solo en `personas`).
2. Backfill: como ambas tablas están vacías en esta instancia, no hay conciliación de duplicados que hacer aquí — pero el código debe funcionar igual si el despliegue real sí tiene datos, así que el backfill se escribe y prueba igual.
3. Actualizar `Personal`/`Usuario` Eloquent (relación 1:1), `PersonalService`, `UsuarioAdminService`, `RutaService::listarChoferes()`/`guardarRutaViaje()`, `AsignacionService` (choferes/copilotos), y las 3 vistas.
4. Ubigeo por FK: recién con `personas` como tabla única, agregar `distrito_id` ahí (no duplicarlo en dos tablas) con FK a `distritos`.
5. Probar en el navegador: login, registrar personal, asignar chofer a bus — antes de dar el cambio por cerrado.

## Historial de correcciones ya aplicadas

1. Colación e índice único de `boletos`, bajo riesgo, aplicadas primero.
2. `buses` huérfana eliminada.
3. `RutaService::listarViajesProgramados()` normalizado a `LOWER()`.
4. `legacy/` y el puente (`LegacyBridgeController`) eliminados por completo — ver `docs/superpowers/plans/2026-09-17-eliminacion-php-puro.md`.
4. Cierre: borrar `legacy/`, los 29 scripts de debug y los backups sueltos.
