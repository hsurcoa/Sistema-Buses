<?php

use App\Models\Usuario;
use App\Services\LegacyRbacSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RBAC legacy -> spatie (Fase 1, Tarea 9). Re-ejecutar despues de editar
// roles/permisos desde el modulo Admin legacy.
Artisan::command('rbac:sync {--verify : Compara usuario x permiso contra rol_permiso sin sincronizar}', function (LegacyRbacSync $sync) {
    if (! $this->option('verify')) {
        $resumen = $sync->syncAll();
        $this->info('RBAC sincronizado: '.json_encode($resumen));
    }

    // Criterio de aceptacion: can() == logica legacy para cada usuario y permiso.
    $legacy = DB::table('usuarios as u')
        ->join('roles as r', 'r.id', '=', 'u.rol_id')
        ->join('rol_permiso as rp', 'rp.rol_id', '=', 'r.id')
        ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
        ->where('u.estado', 'activo')->where('r.activo', 1)->where('p.activo', 1)
        ->select('u.id', 'p.clave')->get()
        ->map(fn ($row) => $row->id.'|'.$row->clave)->flip();

    $claves = DB::table('permisos')->pluck('clave');
    $diferencias = [];
    $comprobaciones = 0;
    foreach (Usuario::with('rol')->get() as $usuario) {
        foreach ($claves as $clave) {
            $comprobaciones++;
            $esperado = $legacy->has($usuario->id.'|'.$clave);
            if ($usuario->can($clave) !== $esperado) {
                $diferencias[] = [$usuario->id, $usuario->email, $usuario->rol?->nombre, $clave, $esperado ? 'si' : 'no'];
            }
        }
    }

    if ($diferencias) {
        $this->error(count($diferencias)." diferencias en {$comprobaciones} comprobaciones:");
        $this->table(['id', 'email', 'rol', 'permiso', 'legacy'], $diferencias);

        return 1;
    }

    $this->info("OK: {$comprobaciones} comprobaciones usuario x permiso identicas a rol_permiso.");

    return 0;
})->purpose('Sincroniza roles/permisos legacy hacia spatie y verifica paridad');

// Reemplaza public/check_db.php (script suelto sin auth, borrado en el
// cierre de la migracion a Laravel: exponia conteos de tablas por URL).
Artisan::command('db:reporte', function () {
    $tablas = [
        'Terminales' => 'terminales',
        'Tipos de Buses' => 'tipos_buses',
        'Vehiculos (Buses)' => 'vehiculos',
        'Personal (Choferes)' => 'personal',
        'Rutas' => 'rutas',
        'Clientes (Pasajeros)' => 'clientes',
        'Viajes (Rutas Programadas)' => 'viajes',
        'Boletos (todos los estados)' => 'boletos',
        'Encomiendas' => 'encomiendas',
    ];

    $filas = [];
    foreach ($tablas as $etiqueta => $tabla) {
        $filas[] = [$etiqueta, DB::table($tabla)->count()];
    }

    $this->table(['Tabla', 'Registros'], $filas);
})->purpose('Muestra conteos de las tablas principales de la base de datos');

// Reemplaza public/run_seed.php (script suelto sin auth, borrado en el
// cierre de la migracion a Laravel: permitia re-sembrar la BD sin login
// solo con conocer la URL).
Artisan::command('db:seed-pando', function () {
    $this->call('db:seed', ['--class' => \Database\Seeders\PandoSeeder::class, '--force' => true]);
    $this->info('Datos de Pando insertados: terminales, rutas, buses de dos pisos, minibuses, choferes bolivianos y clientes.');
})->purpose('Siembra los datos de ejemplo de Pando (equivalente a PandoSeeder)');

// Datos de prueba end-to-end (5 por modulo): completa lo que PandoSeeder deja
// en 0 -viajes, boletos vendidos, encomiendas- usando los servicios reales de
// venta, no inserts crudos. Requiere haber corrido db:seed-pando antes.
Artisan::command('db:seed-pruebas', function () {
    $this->call('db:seed', ['--class' => \Database\Seeders\PandoSeeder::class, '--force' => true]);
    $this->call('db:seed', ['--class' => \Database\Seeders\TestDataSeeder::class, '--force' => true]);
    $this->info('Datos de prueba listos: 5 buses, 5 viajes programados, boletos vendidos y encomiendas por viaje, choferes asignados.');
})->purpose('Siembra datos de prueba completos (rutas, viajes, boletos, encomiendas, asignaciones) para probar el sistema de punta a punta');
