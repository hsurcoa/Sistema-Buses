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

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configurar DB_* en .env (MySQL/MariaDB) y APP_TIMEZONE según el entorno
php artisan migrate --seed
php artisan serve
```

> `APP_TIMEZONE` debe fijarse a mano en cada entorno nuevo (no viene seteado
> por defecto en `.env.example`).

## Tests

```bash
php artisan test
```
