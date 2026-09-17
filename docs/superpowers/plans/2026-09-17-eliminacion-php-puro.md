# Eliminación de PHP puro — cierre de la migración a Laravel

**Fecha:** 2026-09-17
**Pedido por el usuario:** "no quiero nada puro PHP" — reescribir a Eloquent las 17 clases que los controladores migrados (Fases 3-8) todavía reutilizaban tal cual, y eliminar el puente de sesión nativa (`SessionManager`).

## Qué se hizo

### 1. Bug crítico encontrado y corregido: CSRF roto en todas las rutas POST migradas
El middleware CSRF nativo de Laravel (activo por defecto en el grupo `web`) ya estaba rechazando con 419 **todas** las peticiones POST de las Fases 3-8 antes de llegar al controlador, porque las vistas enviaban el token en un campo `csrf_token` (heredado del legacy) en vez del `_token`/`X-CSRF-TOKEN` que Laravel reconoce. Ningún test lo detectó porque `php artisan test` desactiva la verificación CSRF automáticamente. Se corrigió con `App\Http\Middleware\VerifyCsrfToken` (acepta también `csrf_token`) y se quitaron las ~16 validaciones manuales redundantes (y ahora incorrectas) de los controladores.

### 2. Bug visual encontrado y corregido: layout duplicado
7 vistas (Encomiendas ×3, Backup, Reportes ×2, Configuración) incluían su propio header/sidebar/footer legacy además del layout nuevo, duplicando la interfaz (reportado por vos con una captura). Corregido quitando los `require_once` redundantes.

### 3. Las 17 clases → Eloquent
18 modelos Eloquent nuevos (`app/Models/`: Ruta, Viaje, Boleto, Cliente, CajaSesion, MovimientoCaja, Vehiculo, Bus, AsignacionBus, Personal, Encomienda, DetalleEncomienda, EncomiendaTipo, TarifaEncomienda, TipoBus, SerieBoleto, ConfiguracionSistema, RutaParada, TarifaTramo) + 15 clases de servicio (`app/Services/`) que reescriben la lógica exacta de las 17 clases PHP puras originales, incluida `RutaService::registrarVentaTransaccion()` (el flujo de dinero real, con bloqueo de fila `lockForUpdate()` preservado). También se reescribió `DashboardModel` (Fase 2, no estaba en la lista pero es la misma clase de deuda).

### 4. `SessionManager` eliminado
`AuthController`/`LogoutController` ahora usan solo `Auth::login()`/`Auth::logout()` nativos de Laravel. Sin sesión nativa paralela.

### 5. Scripts sueltos del bridge: portados o borrados
`print_ticket.php` → `BoletosController::printPublico()`. `ajax_info_viaje.php`/`ajax_mapa.php` (código muerto: apuntaban a tablas/columnas que no existen en el esquema actual) y `clear_cache.php`/`diagnostico_*.php`/`logout*.php` (utilidades de debug o ya superadas) se borraron en vez de portarse.

### 6. `EmailHelper` → `Mail` nativo de Laravel
**Hallazgo de seguridad:** `legacy/app/core/EmailHelper.php` tenía credenciales SMTP de Gmail (usuario + contraseña de aplicación) **en texto plano dentro del código**, ya comprometidas en el historial de git. Se reemplazó por `App\Mail\CredencialesGeneradas` (Mailable nativo) leyendo la configuración desde `.env` (no versionado). **Recomiendo rotar esa contraseña de aplicación de Gmail** — seguir en el repositorio no la hace más segura, solo evita un *nuevo* leak.

### 7. `legacy/app/config/config.php` → config nativa de Laravel
`URLROOT`/`SITENAME` (usadas en ~35 vistas) ahora se derivan de `config('app.url')`/`config('app.name')` (`.env`), no de un `require_once` a `legacy/`.

### 8. FPDF → Dompdf
`VentasController::imprimirManifiesto()` generaba el PDF con FPDF (librería copiada a mano en `legacy/app/libraries/`). Reescrito con Dompdf + HTML, la misma librería que ya usan los exports de Caja.

## Verificación

- `php -l` en cada archivo de `app/` (sin errores).
- `php artisan route:list`: 114 rutas.
- `php artisan view:cache`: compila sin errores.
- `php artisan test`: 8/8 en verde.
- curl contra Apache real a las 12 pantallas principales: 302 a login sin sesión (igual que antes, confirma que nada quedó roto a nivel de arranque/DI).

No se pudo probar en el navegador con una sesión real (no tengo credenciales); recomiendo probar especialmente: venta de pasajes completa (crea un boleto real), abrir/cerrar caja, y el envío de credenciales por email al crear personal nuevo (usa el Gmail configurado).

## Qué queda (honesto, no es "cero" al 100%)

- **`grep -rn "legacy" app/`** ya no encuentra ninguna instanciación de clase legacy, solo comentarios explicativos y el propio `LegacyBridgeController` (el mecanismo *strangler fig* en sí — HTTP passthrough, no lógica de negocio) más 2 rutas genuinamente muertas (`Boletos::procesar_nuevo`/`gestion_reserva`, sin ningún enlace real, documentado desde la Fase 3).
- **`legacy/` como carpeta sigue existiendo** con el código viejo completo (controladores, modelos, vistas originales) — nada lo ejecuta ya, pero no se borró la carpeta. Borrarla es el paso 9 ("Cierre") del roadmap original; recomiendo hacerlo en una sesión aparte, revisando antes que nada relevante quede solo ahí (backups viejos, notas, etc.).
- **Migraciones correctivas de BD** (fusión `buses`+`vehiculos`, `personal`+`usuarios`, colación, índice único en `boletos`) — descritas en `docs/superpowers/plans/2026-09-17-auditoria-normalizacion-bd.md`, **no ejecutadas**. Son cambios de esquema irreversibles sin backup; no los ejecuté sin tu confirmación explícita dado que tocan directamente los datos reales.
