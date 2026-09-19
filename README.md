# Sistema de Venta de Pasajes (Sistema-Buses)

Sistema de gestión para una empresa de transporte terrestre en **Laravel 11**:
venta de pasajes con mapa de asientos, caja, administración de flota/rutas/
personal, encomiendas (con arancel por tipo de paquete y cobro por kg
excedente) y reportes (PDF/Excel/Word).

## Estado: migración a Laravel completada

El proyecto migró de forma incremental (patrón *strangler fig*) desde un MVC
casero en PHP puro hacia Laravel 11. Esa migración ya **terminó**: no queda
carpeta `legacy/`, ni `LegacyBridgeController`, ni rutas apuntando a código
PHP plano. Toda la aplicación corre sobre Eloquent, rutas declaradas y
autenticación nativa de Laravel.

## Stack

- **Laravel 11** (PHP ^8.2)
- **spatie/laravel-permission** — roles y permisos (RBAC)
- **dompdf** — generación de PDF (manifiestos, recibos, reportes)
- **phpoffice/phpspreadsheet** — exportación a Excel
- **phpoffice/phpword** — exportación a Word
- **MariaDB/MySQL** vía Eloquent
- Frontend: Blade + Bootstrap 5, jQuery, SweetAlert2

## Módulos principales

- **Ventas** (`VentasController`) — venta de pasajes con mapa de asientos por viaje/tramo.
- **Caja** (`CajaController`) — apertura/cierre de sesión de caja y movimientos.
- **Encomiendas** (`EncomiendasController`) — registro y seguimiento de envíos, arancel de tipos de paquete con cargo por kg excedente (`AdminController::tiposEncomienda`).
- **Administración** (`AdminController`) — flota (`Vehiculo`, `TipoBus`), rutas y tramos (`Ruta`, `RutaParada`, `TarifaTramo`), personal (`Personal`, `Usuario`), configuración del sistema.
- **Pasarela de pagos Libélula** (`LibelulaService`, `LibelulaWebhookController`) — cobros y confirmación por webhook.
- **Reportes** (`ReportesController`) — ventas, financiero, cancelaciones, encomiendas; exportables a PDF/Excel/Word.
- **Backup** (`BackupController`) — respaldo de la base de datos.

## Instalación en un equipo nuevo

No hace falta ningún dump `.sql`: las 67 migraciones recrean el esquema
completo (tablas, índices, vistas) y `php artisan migrate --seed` deja una
instancia usable desde cero, con datos base y un usuario para entrar.

```bash
composer install
cp .env.example .env
php artisan key:generate
# Crear la base vacia (mismo nombre que DB_DATABASE en .env.example: sistema_transportes)
php artisan migrate --seed
php artisan serve
```

`.env.example` ya trae `DB_CONNECTION=mysql`, `APP_TIMEZONE=America/La_Paz` y
`APP_LOCALE=es` listos para este proyecto — solo hace falta ajustar
`DB_USERNAME`/`DB_PASSWORD` si el MySQL local no usa `root` sin contraseña
(el default de XAMPP).

El seed (`DatabaseSeeder`) crea:

- Rol **Administrador** y un usuario para loguearse: `admin@sistema.local` /
  `Admin12345!` — **cambiar la contraseña después del primer login**.
- Catálogo base (`PandoSeeder`): terminales, rutas, tipos de bus, vehículos,
  personal y clientes de ejemplo.

Para además ver viajes/boletos/encomiendas de prueba (datos transaccionales,
no catálogo), correr aparte — asume una caja ya abierta del usuario 1:

```bash
php artisan db:seed --class=TestDataSeeder
```

## Tests

```bash
php artisan test
```
