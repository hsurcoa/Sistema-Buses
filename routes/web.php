<?php


use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BoletosController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ControladorTransaccionesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EncomiendasController;
use App\Http\Controllers\LibelulaWebhookController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\VehiculosController;
use App\Http\Controllers\VentasController;
use Illuminate\Support\Facades\Route;

// Rutas ya migradas a Laravel van aqui arriba.
require __DIR__.'/auth.php';

// Fase 2 (layout base propio + dashboard): ver
// docs/superpowers/plans/2026-09-16-migracion-laravel-fase2-plan.md.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('active')->name('dashboard');
    Route::post('/logout', LogoutController::class)->name('logout');

    // Fase 3 (Ventas): ver
    // docs/superpowers/plans/2026-09-17-migracion-laravel-fase3-plan.md.
    // Mismas rutas que el router legacy (el JS de las vistas portadas las
    // llama tal cual, sin cambios), ahora resueltas por Laravel.
    Route::middleware('active')->group(function () {
        Route::get('/ventas/venta_pasajes', [VentasController::class, 'ventaPasajes'])->name('ventas.venta_pasajes');
        Route::get('/ventas/crear_ruta', [VentasController::class, 'crearRuta'])->name('ventas.crear_ruta');
        Route::post('/ventas/guardar_ruta_viaje', [VentasController::class, 'guardarRutaViaje'])->name('ventas.guardar_ruta_viaje');
        Route::get('/ventas/obtener_ruta_viaje/{id}', [VentasController::class, 'obtenerRutaViaje'])->name('ventas.obtener_ruta_viaje');
        Route::post('/ventas/obtener_buses_tipo', [VentasController::class, 'obtenerBusesTipo'])->name('ventas.obtener_buses_tipo');
        Route::post('/ventas/obtener_tripulacion_bus', [VentasController::class, 'obtenerTripulacionBus'])->name('ventas.obtener_tripulacion_bus');
        Route::post('/ventas/despachar_ruta/{id}', [VentasController::class, 'despacharRuta'])->name('ventas.despachar_ruta');
        Route::post('/ventas/eliminar_ruta_viaje/{id}', [VentasController::class, 'eliminarRutaViaje'])->name('ventas.eliminar_ruta_viaje');
        Route::post('/ventas/procesar_venta', [VentasController::class, 'procesarVenta'])->name('ventas.procesar_venta');
        Route::get('/ventas/obtener_datos_reserva/{id}', [VentasController::class, 'obtenerDatosReserva'])->name('ventas.obtener_datos_reserva');
        Route::get('/ventas/obtener_manifiesto/{id}', [VentasController::class, 'obtenerManifiesto'])->name('ventas.obtener_manifiesto');
        Route::get('/ventas/listar_manifiesto/{id}', [VentasController::class, 'listarManifiesto'])->name('ventas.listar_manifiesto');
        Route::post('/ventas/cancelar_boleto/{id}', [VentasController::class, 'cancelarBoleto'])->name('ventas.cancelar_boleto');
        Route::get('/ventas/estado_cancelacion/{id}', [VentasController::class, 'estadoCancelacion'])->name('ventas.estado_cancelacion');
        Route::get('/ventas/imprimir_manifiesto/{id}', [VentasController::class, 'imprimirManifiesto'])->name('ventas.imprimir_manifiesto');
        Route::get('/ventas/imprimir_manifiesto_html/{id}', [VentasController::class, 'imprimirManifiestoHtml'])->name('ventas.imprimir_manifiesto_html');
        Route::get('/ventas/estado_cobro/{id}', [VentasController::class, 'estadoCobro'])->name('ventas.estado_cobro');
        Route::get('/ventas/pantalla_qr/{id}', [VentasController::class, 'pantallaQr'])->name('ventas.pantalla_qr');
        Route::post('/ventas/gestion_boleto', [VentasController::class, 'gestionBoleto'])->name('ventas.gestion_boleto');
        Route::get('/ventas/obtener_conteo_asientos/{id}', [VentasController::class, 'obtenerConteoAsientos'])->name('ventas.obtener_conteo_asientos');

        Route::get('/boletos/imprimir_ticket/{id}', [BoletosController::class, 'imprimirTicket'])->name('boletos.imprimir_ticket');
        Route::get('/public/print_ticket.php', [BoletosController::class, 'printPublico'])->name('boletos.print_publico');

        Route::post('/controladortransacciones/index', [ControladorTransaccionesController::class, 'index'])->name('controladortransacciones.index');
        Route::get('/controladortransacciones/ticket_termico/{id}', [ControladorTransaccionesController::class, 'ticketTermico'])->name('controladortransacciones.ticket_termico');

        // Fase 4 (Caja): ver
        // docs/superpowers/plans/2026-09-17-migracion-laravel-fase4-plan.md.
        Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
        Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
        Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
        Route::post('/caja/registrar_gasto_ajax', [CajaController::class, 'registrarGastoAjax'])->name('caja.registrar_gasto_ajax');
        Route::post('/caja/obtener_ingresos_ajax', [CajaController::class, 'obtenerIngresosAjax'])->name('caja.obtener_ingresos_ajax');
        Route::post('/caja/obtener_movimientos_sesion_ajax', [CajaController::class, 'obtenerMovimientosSesionAjax'])->name('caja.obtener_movimientos_sesion_ajax');
        Route::get('/caja/reporte/{id}', [CajaController::class, 'reporte'])->name('caja.reporte');
        Route::get('/caja/reporte_z/{id}', [CajaController::class, 'reporteZ'])->name('caja.reporte_z');
        Route::post('/caja/obtener_reportes_cierre_ajax', [CajaController::class, 'obtenerReportesCierreAjax'])->name('caja.obtener_reportes_cierre_ajax');
        Route::post('/caja/exportar_excel', [CajaController::class, 'exportarExcel'])->name('caja.exportar_excel');
        Route::post('/caja/exportar_word', [CajaController::class, 'exportarWord'])->name('caja.exportar_word');
        Route::post('/caja/exportar_pdf/{tamano?}', [CajaController::class, 'exportarPdf'])->name('caja.exportar_pdf');

        // Fase 5 (Admin): ver
        // docs/superpowers/plans/2026-09-17-migracion-laravel-fase5-plan.md.
        Route::get('/admin/registrar_personal', [AdminController::class, 'registrarPersonal'])->name('admin.registrar_personal');
        Route::post('/admin/guardar_personal', [AdminController::class, 'guardarPersonal'])->name('admin.guardar_personal');
        Route::post('/admin/eliminar_personal/{id}', [AdminController::class, 'eliminarPersonal'])->name('admin.eliminar_personal');
        Route::get('/admin/cambiar_estado/{id}', [AdminController::class, 'cambiarEstado'])->name('admin.cambiar_estado');
        Route::get('/admin/get_personal/{id}', [AdminController::class, 'getPersonal'])->name('admin.get_personal');

        Route::get('/admin/usuarios', [AdminController::class, 'usuarios'])->name('admin.usuarios');
        Route::post('/admin/guardar_usuario', [AdminController::class, 'guardarUsuario'])->name('admin.guardar_usuario');
        Route::post('/admin/estado_usuario', [AdminController::class, 'estadoUsuario'])->name('admin.estado_usuario');
        Route::post('/admin/eliminar_usuario', [AdminController::class, 'eliminarUsuario'])->name('admin.eliminar_usuario');

        Route::get('/admin/roles_permisos', [AdminController::class, 'rolesPermisos'])->name('admin.roles_permisos');
        Route::get('/admin/get_permisos_rol/{rol_id}', [AdminController::class, 'getPermisosRol'])->name('admin.get_permisos_rol');
        Route::post('/admin/guardar_permisos', [AdminController::class, 'guardarPermisos'])->name('admin.guardar_permisos');
        Route::post('/admin/crear_rol', [AdminController::class, 'crearRol'])->name('admin.crear_rol');

        Route::get('/admin/registrar_buses', [AdminController::class, 'registrarBuses'])->name('admin.registrar_buses');
        Route::get('/admin/asignar_buses', [AdminController::class, 'asignarBuses'])->name('admin.asignar_buses');
        Route::post('/admin/guardar_chofer_rapido', [AdminController::class, 'guardarChoferRapido'])->name('admin.guardar_chofer_rapido');
        Route::post('/admin/guardar_asignacion', [AdminController::class, 'guardarAsignacion'])->name('admin.guardar_asignacion');
        Route::get('/admin/eliminar_asignacion/{id}', [AdminController::class, 'eliminarAsignacion'])->name('admin.eliminar_asignacion');
        Route::post('/admin/obtener_asignacion', [AdminController::class, 'obtenerAsignacion'])->name('admin.obtener_asignacion');
        Route::post('/admin/editar_asignacion', [AdminController::class, 'editarAsignacion'])->name('admin.editar_asignacion');

        Route::get('/admin/registrar_terminal', [AdminController::class, 'registrarTerminal'])->name('admin.registrar_terminal');
        Route::post('/admin/guardar_terminal', [AdminController::class, 'guardarTerminal'])->name('admin.guardar_terminal');
        Route::get('/admin/cambiar_estado_terminal/{id}', [AdminController::class, 'cambiarEstadoTerminal'])->name('admin.cambiar_estado_terminal');
        Route::get('/admin/eliminar_terminal/{id}', [AdminController::class, 'eliminarTerminal'])->name('admin.eliminar_terminal');

        Route::get('/admin/rutas_paradas', [AdminController::class, 'rutasParadas'])->name('admin.rutas_paradas');
        Route::post('/admin/guardar_paradas', [AdminController::class, 'guardarParadas'])->name('admin.guardar_paradas');
        Route::post('/admin/guardar_tarifas', [AdminController::class, 'guardarTarifas'])->name('admin.guardar_tarifas');
        Route::post('/admin/guardar_ruta', [AdminController::class, 'guardarRuta'])->name('admin.guardar_ruta');
        Route::get('/admin/cambiar_estado_ruta/{id}', [AdminController::class, 'cambiarEstadoRuta'])->name('admin.cambiar_estado_ruta');
        Route::get('/admin/eliminar_ruta/{id}', [AdminController::class, 'eliminarRuta'])->name('admin.eliminar_ruta');

        Route::get('/admin/tipos_buses', [AdminController::class, 'tiposBuses'])->name('admin.tipos_buses');
        Route::post('/admin/guardar_tipo_bus', [AdminController::class, 'guardarTipoBus'])->name('admin.guardar_tipo_bus');
        Route::get('/admin/cambiar_estado_tipo_bus/{id}', [AdminController::class, 'cambiarEstadoTipoBus'])->name('admin.cambiar_estado_tipo_bus');
        Route::post('/admin/eliminar_tipo_bus', [AdminController::class, 'eliminarTipoBus'])->name('admin.eliminar_tipo_bus');

        Route::get('/admin/mapa_asientos/{tipoBusId?}', [AdminController::class, 'mapaAsientos'])->name('admin.mapa_asientos');
        Route::get('/admin/obtener_asientos_disponibles', [AdminController::class, 'obtenerAsientosDisponibles'])->name('admin.obtener_asientos_disponibles');
        Route::post('/admin/reservar_asientos', [AdminController::class, 'reservarAsientos'])->name('admin.reservar_asientos');
        Route::get('/admin/generar_asientos/{tipoBusId?}', [AdminController::class, 'generarAsientos'])->name('admin.generar_asientos');
        Route::get('/admin/limpiar_reservas_expiradas', [AdminController::class, 'limpiarReservasExpiradas'])->name('admin.limpiar_reservas_expiradas');
        Route::get('/admin/estadisticas_asientos/{tipoBusId}', [AdminController::class, 'estadisticasAsientos'])->name('admin.estadisticas_asientos');

        Route::get('/admin/obtener_info_ruta_json/{rutaId}', [AdminController::class, 'obtenerInfoRutaJson'])->name('admin.obtener_info_ruta_json');
        Route::post('/admin/cotizar_envio', [AdminController::class, 'cotizarEnvio'])->name('admin.cotizar_envio');

        Route::post('/vehiculos/guardar', [VehiculosController::class, 'guardar'])->name('vehiculos.guardar');
        Route::get('/vehiculos/generarPDF', [VehiculosController::class, 'generarPdf'])->name('vehiculos.generarPDF');
        Route::post('/vehiculos/obtener_bus', [VehiculosController::class, 'obtenerBus'])->name('vehiculos.obtener_bus');
        Route::post('/vehiculos/editar_bus', [VehiculosController::class, 'editarBus'])->name('vehiculos.editar_bus');
        Route::post('/vehiculos/eliminar_bus', [VehiculosController::class, 'eliminarBus'])->name('vehiculos.eliminar_bus');

        Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
        Route::post('/series/guardar', [SeriesController::class, 'guardar'])->name('series.guardar');
        Route::get('/series/cambiar_estado/{id}', [SeriesController::class, 'cambiarEstado'])->name('series.cambiar_estado');

        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('/configuracion/guardar_qr', [ConfiguracionController::class, 'guardarQr'])->name('configuracion.guardar_qr');
        Route::post('/configuracion/guardar_alertas_flota', [ConfiguracionController::class, 'guardarAlertasFlota'])->name('configuracion.guardar_alertas_flota');
        Route::post('/configuracion/guardar', [ConfiguracionController::class, 'guardar'])->name('configuracion.guardar');
        Route::post('/configuracion/guardar_libelula', [ConfiguracionController::class, 'guardarLibelula'])->name('configuracion.guardar_libelula');
        Route::post('/configuracion/verificar_pagos_libelula', [ConfiguracionController::class, 'verificarPagosLibelula'])->name('configuracion.verificar_pagos_libelula');

        // Fase 6 (Encomiendas): ver
        // docs/superpowers/plans/2026-09-17-migracion-laravel-fase6-plan.md.
        Route::get('/encomiendas', [EncomiendasController::class, 'index'])->name('encomiendas.index');
        Route::get('/encomiendas/crear', [EncomiendasController::class, 'crear'])->name('encomiendas.crear');
        Route::get('/encomiendas/obtener_viajes/{rutaId}', [EncomiendasController::class, 'obtenerViajes'])->name('encomiendas.obtener_viajes');
        Route::post('/encomiendas/guardar', [EncomiendasController::class, 'guardar'])->name('encomiendas.guardar');
        Route::get('/encomiendas/recibo/{id}', [EncomiendasController::class, 'recibo'])->name('encomiendas.recibo');

        // Fase 7 (Reportes): ver
        // docs/superpowers/plans/2026-09-17-migracion-laravel-fase7-plan.md.
        Route::get('/reportes', [ReportesController::class, 'pasajeros'])->name('reportes.index');
        Route::get('/reportes/pasajeros', [ReportesController::class, 'pasajeros'])->name('reportes.pasajeros');
        Route::post('/reportes/buscar_viajes_ajax', [ReportesController::class, 'buscarViajesAjax'])->name('reportes.buscar_viajes_ajax');
        Route::get('/reportes/obtener_manifiesto_ajax/{viaje_id}', [ReportesController::class, 'obtenerManifiestoAjax'])->name('reportes.obtener_manifiesto_ajax');
        Route::post('/reportes/buscar_historico_ajax', [ReportesController::class, 'buscarHistoricoAjax'])->name('reportes.buscar_historico_ajax');
        Route::post('/reportes/buscar_persona_ajax', [ReportesController::class, 'buscarPersonaAjax'])->name('reportes.buscar_persona_ajax');
        Route::get('/reportes/imprimir_manifiesto/{viaje_id}', [ReportesController::class, 'imprimirManifiesto'])->name('reportes.imprimir_manifiesto');
        Route::get('/reportes/financiero', [ReportesController::class, 'financiero'])->name('reportes.financiero');
        Route::post('/reportes/financiero_ajax', [ReportesController::class, 'financieroAjax'])->name('reportes.financiero_ajax');
        Route::get('/reportes/cancelaciones', [ReportesController::class, 'cancelaciones'])->name('reportes.cancelaciones');
        Route::post('/reportes/cancelaciones_ajax', [ReportesController::class, 'cancelacionesAjax'])->name('reportes.cancelaciones_ajax');
        Route::post('/reportes/procesar_devolucion', [ReportesController::class, 'procesarDevolucionAjax'])->name('reportes.procesar_devolucion');

        // Fase 8 (Backup, ultima del roadmap original): ver
        // docs/superpowers/plans/2026-09-17-migracion-laravel-fase8-plan.md.
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
        Route::get('/backup/delete/{filename}', [BackupController::class, 'delete'])->name('backup.delete');
        Route::get('/backup/generate', [BackupController::class, 'generate'])->name('backup.generate');
    });
});

// Callback público de Libélula: lo invocan sus servidores (sin sesión ni
// CSRF nuestro) cuando un pago se confirma. Ver LibelulaWebhookController.
Route::any('/pagos/libelula/callback', [LibelulaWebhookController::class, 'confirmar'])->name('pagos.libelula.callback');

